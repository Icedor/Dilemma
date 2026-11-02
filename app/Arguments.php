<?php

declare(strict_types=1);

namespace Dilemma;

final class Arguments
{
	private static function text_list(array $input, string $key): array
	{
		$list = $input[$key] ?? [];
		if (!is_array($list) || !array_is_list($list) || count($list) > 12) throw new Failure(422, 'invalid_field', ['field' => $key]);
		return array_map(static fn ($value) => Support::text(['value' => $value], 'value', 1, 2000), $list);
	}

	private static function sources(string $message_id, array $input): void
	{
		$sources = $input['sources'] ?? [];
		if (!is_array($sources) || !array_is_list($sources) || count($sources) > 12) throw new Failure(422, 'invalid_field', ['field' => 'sources']);
		$total = (int) Db::one('SELECT COUNT(*) AS count FROM argument_sources WHERE message_id = ?', [$message_id])['count'];
		if ($total + count($sources) > 60) throw new Failure(422, 'source_limit');
		foreach ($sources as $source) {
			if (!is_array($source)) throw new Failure(422, 'invalid_field', ['field' => 'sources']);
			Db::run('INSERT INTO argument_sources (id, message_id, url, label, created_at) VALUES (?, ?, ?, ?, ?)', [Support::id(), $message_id, Support::url(Support::text($source, 'url', 8, 2000)), Support::text($source, 'label', 1, 240), Support::now()]);
		}
	}

	public static function submit(array $actor, string $id, array $input): array
	{
		$input['debate_id'] = $id;
		return Db::transaction(static function () use ($actor, $id, $input): array {
			Debates::actor($actor, true, $id);
			$row = Debates::row($id, true);
			$member = Debates::member($id, $actor['user_id']);
			if ($receipt = Idempotency::replay($actor['user_id'], 'argument', $input)) return $receipt;
			Debates::revision($row, $input);
			if ($row['status'] !== 'active' || (int) $row['hidden'] === 1) throw new Failure(409, 'debate_not_active');
			if ((int) $row['turn_side'] !== (int) $member['side']) throw new Failure(409, 'not_your_turn');
			Support::rate('argument:' . $actor['user_id'], 30, 3600);
			$parents = $input['reply_to'] ?? [];
			if (!is_array($parents) || !array_is_list($parents) || count($parents) > 12) throw new Failure(422, 'invalid_field', ['field' => 'reply_to']);
			foreach ($parents as $parent) {
				if (!is_string($parent) || !Db::one('SELECT id FROM debate_messages WHERE id = ? AND debate_id = ?', [$parent, $id])) throw new Failure(422, 'invalid_reference');
			}
			$message_id = Support::id();
			$assessment = Support::text($input, 'agent_assessment', 0, 4000);
			$client = 'Manual';
			if (isset($actor['connection_id'])) $client = Db::one('SELECT label FROM mcp_connections WHERE id = ?', [$actor['connection_id']])['label'];
			Db::run('INSERT INTO debate_messages (id, debate_id, author_id, ordinal, body, channel, client_label, subarguments, objections, rationale, conclusion, agent_assessment, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$message_id, $id, $actor['user_id'], (int) $row['message_count'] + 1, Support::text($input, 'body', 5, 20000), isset($actor['connection_id']) ? 'mcp' : 'manual', $client, Support::json(self::text_list($input, 'subarguments')), Support::json(self::text_list($input, 'objections')), Support::text($input, 'rationale', 0, 4000), Support::text($input, 'conclusion', 0, 4000), $assessment, Support::now()]);
			foreach (array_unique($parents) as $parent) Db::run('INSERT INTO argument_relations (debate_id, message_id, parent_id) VALUES (?, ?, ?)', [$id, $message_id, $parent]);
			self::sources($message_id, $input);
			$event = Debates::event($id, $actor['user_id'], 'argument_published', ['message_id' => $message_id, 'ordinal' => (int) $row['message_count'] + 1, 'reply_to' => array_values(array_unique($parents))]);
			if ($assessment !== '') Debates::event($id, $actor['user_id'], 'agent_assessment', ['message_id' => $message_id]);
			Db::run('UPDATE debates SET turn_side = ?, message_count = message_count + 1, revision = revision + 1, updated_at = ? WHERE id = ?', [1 - (int) $member['side'], Support::now(), $id]);
			Mail::notify($id, $actor['user_id'], 'argument', $event);
			$stored = Db::one('SELECT created_at FROM debate_messages WHERE id = ? AND debate_id = ?', [$message_id, $id]);
			if (!$stored) throw new \RuntimeException('Publication postcondition failed');
			return Idempotency::save($actor['user_id'], 'argument', $input, ['message_id' => $message_id, 'debate_id' => $id, 'event_sequence' => $event, 'revision' => (int) $row['revision'] + 1, 'stored_at' => $stored['created_at']]);
		});
	}

	public static function amend(array $actor, string $message_id, array $input): array
	{
		$input['message_id'] = Support::uuid($message_id);
		return Db::transaction(static function () use ($actor, $message_id, $input): array {
			Debates::actor($actor, true);
			$message = Db::one('SELECT * FROM debate_messages WHERE id = ?', [$message_id]);
			if (!$message) throw new Failure(404, 'not_found');
			Debates::actor($actor, true, $message['debate_id']);
			$row = Debates::row($message['debate_id'], true);
			Debates::member($row['id'], $actor['user_id']);
			if ($message['author_id'] !== $actor['user_id']) throw new Failure(403, 'access_denied');
			if ($receipt = Idempotency::replay($actor['user_id'], 'amend', $input)) return $receipt;
			Debates::revision($row, $input);
			if ((int) $row['hidden'] === 1 || $row['status'] === 'archived') throw new Failure(409, 'invalid_transition');
			if ((int) Db::one('SELECT COUNT(*) AS n FROM argument_amendments WHERE message_id = ?', [$message_id])['n'] >= 100) throw new Failure(422, 'amendment_limit');
			Support::rate('amend:' . $actor['user_id'], 30, 3600);
			$clarification = Support::text($input, 'clarification', 0, 4000);
			if ($clarification === '' && empty($input['sources'])) throw new Failure(422, 'empty_amendment');
			$amendment_id = Support::id();
			Db::run('INSERT INTO argument_amendments (id, message_id, author_id, clarification, created_at) VALUES (?, ?, ?, ?, ?)', [$amendment_id, $message_id, $actor['user_id'], $clarification, Support::now()]);
			self::sources($message_id, $input);
			$event = Debates::event($row['id'], $actor['user_id'], 'argument_amended', ['message_id' => $message_id, 'amendment_id' => $amendment_id]);
			Db::run('UPDATE debates SET revision = revision + 1, updated_at = ? WHERE id = ?', [Support::now(), $row['id']]);
			return Idempotency::save($actor['user_id'], 'amend', $input, ['message_id' => $message_id, 'amendment_id' => $amendment_id, 'event_sequence' => $event, 'revision' => (int) $row['revision'] + 1, 'stored_at' => Support::now()]);
		});
	}
}
