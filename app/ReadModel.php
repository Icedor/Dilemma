<?php

declare(strict_types=1);

namespace Dilemma;

final class ReadModel
{
	public static function list(array $filters, ?array $actor = null): array
	{
		$where = []; $params = [];
		if ($actor) {
			Debates::actor($actor);
			$where[] = 'EXISTS (SELECT 1 FROM debate_participants pm WHERE pm.debate_id = d.id AND pm.user_id = ?)';
			$params[] = $actor['user_id'];
			if (count($actor['debates'] ?? []) > 1) {
				$where[] = 'd.id IN (' . implode(',', array_fill(0, count($actor['debates']), '?')) . ')';
				array_push($params, ...array_keys($actor['debates']));
			} elseif (isset($actor['connection_id'])) {
				$connection = Db::one('SELECT debate_id FROM mcp_connections WHERE id = ?', [$actor['connection_id']]);
				if ($connection['debate_id'] !== null) { $where[] = 'd.id = ?'; $params[] = $connection['debate_id']; }
			}
		} else $where[] = Debates::PUBLIC_SQL;
		$query = Support::text($filters, 'q', 0, 160);
		if ($query !== '') { $where[] = 'MATCH(d.title, d.thesis, d.introduction) AGAINST (? IN NATURAL LANGUAGE MODE)'; $params[] = $query; }
		$topic = Support::text($filters, 'topic', 0, 60);
		if ($topic !== '') { $where[] = 'd.topic = ?'; $params[] = $topic; }
		$status = $filters['status'] ?? '';
		if (in_array($status, ['draft', 'invited', 'open', 'active', 'paused', 'awaiting_human', 'flagged', 'ended', 'archived'], true)) { $where[] = 'd.status = ?'; $params[] = $status; }
		if (($filters['outcome'] ?? '') === 'changed') $where[] = 'd.flag_user_id IS NOT NULL';
		if (($filters['outcome'] ?? '') === 'unchanged') $where[] = "d.flag_user_id IS NULL AND d.status IN ('ended', 'archived') AND d.end_reason = 'disagreement'";
		if (!empty($filters['profile'])) { $where[] = 'EXISTS (SELECT 1 FROM debate_participants pp WHERE pp.debate_id = d.id AND pp.user_id = ?)'; $params[] = Support::uuid($filters['profile']); }
		$page = Support::integer($filters['page'] ?? 1, 1, 100000, 'page');
		$clause = implode(' AND ', $where);
		$total = (int) Db::one('SELECT COUNT(*) AS count FROM debates d WHERE ' . $clause, $params)['count'];
		$sort = ($filters['sort'] ?? '') === 'oldest' ? 'ASC' : 'DESC';
		$rows = Db::all('SELECT d.id, d.title, d.topic, d.locale, d.status, d.turn_side, d.message_count, d.flag_user_id, d.end_reason, d.created_at, d.updated_at, LEFT(d.introduction, 240) AS excerpt FROM debates d WHERE ' . $clause . ' ORDER BY d.updated_at ' . $sort . ', d.id LIMIT 20 OFFSET ' . (($page - 1) * 20), $params);
		foreach ($rows as &$row) $row['participants'] = self::participants($row['id']);
		return ['items' => $rows, 'page' => $page, 'total' => $total, 'pages' => max(1, (int) ceil($total / 20))];
	}

	public static function participants(string $id): array
	{
		return Db::all('SELECT p.user_id, p.side, p.stance, p.public_consent, p.position_consent, p.left_at, p.joined_at, p.agent_state, u.alias, u.avatar, u.status AS account_status, EXISTS (SELECT 1 FROM mcp_connections c WHERE c.debate_id = p.debate_id AND c.user_id = p.user_id AND c.revoked_at IS NULL AND c.write_test_at IS NOT NULL) AS ai_connected FROM debate_participants p JOIN users u ON u.id = p.user_id WHERE p.debate_id = ? ORDER BY p.side, p.joined_at, p.user_id', [$id]);
	}

	public static function debate(string $id, ?array $actor = null, int $page = 1, int $position_page = 1): array
	{
		$row = Debates::readable($id, $actor);
		$row['participants'] = self::participants($id);
		$row['workflow'] = DebateFlow::state($row, $row['participants'], $actor);
		$row['latest_assessment'] = $row['status'] === 'awaiting_human' ? Db::one('SELECT id, LEFT(body, 500) AS excerpt FROM debate_messages WHERE debate_id = ? ORDER BY ordinal DESC LIMIT 1', [$id]) : null;
		$page = Support::integer($page, 1, 100000, 'page');
		$row['messages'] = Db::all('SELECT m.id, m.author_id, m.ordinal, LEFT(m.body, 650) AS excerpt, CHAR_LENGTH(m.body) AS body_length, m.channel, m.client_label, m.created_at FROM debate_messages m WHERE m.debate_id = ? ORDER BY m.ordinal LIMIT 20 OFFSET ' . (($page - 1) * 20), [$id]);
		$row['page'] = $page;
		$row['pages'] = max(1, (int) ceil((int) $row['message_count'] / 20));
		$position_where = 'p.position_consent = 1'; $params = [$id];
		if ($row['viewer_member'] !== null) $position_where = '1 = 1';
		$row['position_page'] = Support::integer($position_page, 1, 100000, 'positions_page');
		$positions_from = ' FROM debate_positions dp JOIN debate_participants p ON p.debate_id = dp.debate_id AND p.user_id = dp.user_id WHERE dp.debate_id = ? AND ' . $position_where;
		$count = (int) Db::one('SELECT COUNT(*) AS n' . $positions_from, $params)['n'];
		$row['position_pages'] = max(1, (int) ceil($count / 20));
		$row['positions'] = Db::all('SELECT dp.id, dp.user_id, dp.value, dp.note, dp.created_at' . $positions_from . ' ORDER BY dp.sequence LIMIT 20 OFFSET ' . (($position_page - 1) * 20), $params);
		$row['latest_positions'] = Db::all('SELECT dp.id, dp.user_id, dp.value, dp.note, dp.created_at' . $positions_from . ' AND dp.sequence = (SELECT MAX(lp.sequence) FROM debate_positions lp WHERE lp.debate_id = dp.debate_id AND lp.user_id = dp.user_id) ORDER BY p.side', $params);
		return $row;
	}

	public static function live_debate(string $id, ?array $actor, int $after): array
	{
		$row = Debates::readable($id, $actor);
		$after = Support::integer($after, 0, (int) $row['message_count'], 'after');
		$row['participants'] = self::participants($id);
		$row['workflow'] = DebateFlow::state($row, $row['participants'], $actor);
		$row['latest_assessment'] = $row['status'] === 'awaiting_human' ? Db::one('SELECT id, ordinal, LEFT(body, 500) AS excerpt FROM debate_messages WHERE debate_id = ? AND ordinal <= ? ORDER BY ordinal DESC LIMIT 1', [$id, (int) $row['message_count']]) : null;
		// Bound both work and the snapshot: a concurrent later post belongs to the next poll.
		$row['messages'] = Db::all('SELECT id, author_id, ordinal, LEFT(body, 650) AS excerpt, CHAR_LENGTH(body) AS body_length, channel, client_label, created_at FROM debate_messages WHERE debate_id = ? AND ordinal > ? AND ordinal <= ? ORDER BY ordinal LIMIT 20', [$id, $after, (int) $row['message_count']]);
		return $row;
	}

	public static function argument(string $message_id, ?array $actor = null): array
	{
		Support::uuid($message_id);
		$row = Db::one('SELECT * FROM debate_messages WHERE id = ?', [$message_id]);
		if (!$row) throw new Failure(404, 'not_found');
		Debates::readable($row['debate_id'], $actor);
		foreach (['subarguments', 'objections'] as $field) $row[$field] = json_decode($row[$field], true, 32, JSON_THROW_ON_ERROR);
		$row['sources'] = Db::all('SELECT url, label, created_at FROM argument_sources WHERE message_id = ? ORDER BY created_at, id', [$message_id]);
		$row['amendments'] = Db::all('SELECT id, clarification, created_at FROM argument_amendments WHERE message_id = ? ORDER BY sequence LIMIT 100', [$message_id]);
		$row['reply_to'] = array_column(Db::all('SELECT parent_id FROM argument_relations WHERE message_id = ?', [$message_id]), 'parent_id');
		$row['replies'] = Db::all('SELECT m.id, m.ordinal, LEFT(m.body, 200) AS excerpt FROM argument_relations r JOIN debate_messages m ON m.id = r.message_id WHERE r.parent_id = ? ORDER BY m.ordinal LIMIT 50', [$message_id]);
		return $row;
	}

	public static function updates(string $id, ?array $actor, int $after = 0, string $kind = ''): array
	{
		Debates::readable($id, $actor);
		$params = [$id, max(0, $after)];
		$where = '';
		if ($kind !== '') { $where = ' AND kind = ?'; $params[] = $kind; }
		$events = Db::all('SELECT e.sequence, e.actor_id, u.alias AS actor_alias, e.kind, e.data, e.created_at FROM debate_events e LEFT JOIN users u ON u.id = e.actor_id WHERE e.debate_id = ? AND e.sequence > ?' . $where . ' ORDER BY e.sequence LIMIT 51', $params);
		$more = count($events) > 50;
		$events = array_slice($events, 0, 50);
		foreach ($events as &$event) $event['data'] = json_decode($event['data'], true, 32, JSON_THROW_ON_ERROR);
		return ['events' => $events, 'has_more' => $more, 'next_sequence' => $events ? (int) $events[count($events) - 1]['sequence'] : $after];
	}
}
