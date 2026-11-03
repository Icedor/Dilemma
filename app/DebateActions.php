<?php

declare(strict_types=1);

namespace Dilemma;

final class DebateActions
{
	public static function apply(array $actor, string $id, array $input): array
	{
		Debates::human($actor);
		$input['debate_id'] = $id;
		return Db::transaction(static function () use ($actor, $id, $input): array {
			Debates::actor($actor, true, $id);
			$row = Debates::row($id, true);
			$member = Debates::member($id, $actor['user_id']);
			if ($receipt = Idempotency::replay($actor['user_id'], 'action', $input)) return $receipt;
			Debates::revision($row, $input);
			$action = Support::text($input, 'action', 1, 30);
			if ($member['left_at'] !== null && !in_array($action, ['consent', 'notifications'], true)) throw new Failure(403, 'access_denied');
			if ($row['hidden'] && !in_array($action, ['consent', 'notifications'], true)) throw new Failure(409, 'invalid_transition');
			$status = $row['status'];
			$notify = null;
			$data = [];
			switch ($action) {
				case 'leave_seat':
					self::state($status, ['active', 'paused', 'awaiting_human']);
					if (($input['confirm'] ?? false) !== true) throw new Failure(422, 'confirmation_required');
					if (!Debates::readable($id, $actor)['is_public']) throw new Failure(422, 'continuation_consent_required');
					Db::run("UPDATE debate_participants SET left_at = ?, active_side = NULL, notify = 0 WHERE debate_id = ? AND user_id = ?", [Support::now(), $id, $actor['user_id']]);
					Db::run("UPDATE debate_participants SET agent_state = 'arguing' WHERE debate_id = ?", [$id]);
					Db::run("UPDATE debates SET opponent_mode = 'open', turn_side = ?, end_reason = NULL, end_proposed_by = NULL WHERE id = ?", [$member['side'], $id]);
					$status = 'open'; $kind = 'seat_opened'; $notify = 'update';
					$data = ['side' => (int) $member['side']];
					break;
				case 'open_listing':
				case 'close_listing':
					if ($row['hidden']) throw new Failure(403, 'access_denied');
					self::state($status, $action === 'open_listing' ? ['draft', 'invited'] : ['open']);
					if ($action === 'open_listing' && ($input['public_consent'] ?? false) !== true) throw new Failure(422, 'open_consent_required');
					$member = Debates::member($id, $actor['user_id']);
					Debates::consent($id, $actor['user_id'], $action === 'open_listing', (bool) $member['position_consent']);
					if ($action === 'open_listing' && !Db::one('SELECT d.id FROM debates d WHERE d.id = ? AND ' . Debates::CONSENT_SQL, [$id])) throw new Failure(422, 'continuation_consent_required');
					Db::run("UPDATE debate_invitations SET status = 'superseded', encrypted_token = NULL WHERE debate_id = ? AND status = 'pending'", [$id]);
					Db::run('UPDATE debates SET opponent_mode = ? WHERE id = ?', [$action === 'open_listing' ? 'open' : 'invite', $id]);
					$status = $action === 'open_listing' ? 'open' : 'draft';
					$kind = $action === 'open_listing' ? 'listing_opened' : 'listing_closed';
					break;
				case 'notifications':
					Db::run('UPDATE debate_participants SET notify = ? WHERE debate_id = ? AND user_id = ?', [(int) (($input['notify'] ?? false) === true), $id, $actor['user_id']]);
					$kind = 'notifications_updated';
					break;
				case 'withdraw':
					self::state($status, ['active', 'paused', 'awaiting_human']);
					if (($input['confirm'] ?? false) !== true) throw new Failure(422, 'confirmation_required');
					$status = 'ended'; $kind = 'ended'; $notify = 'ended'; $data = ['reason' => 'withdrawn'];
					Db::run("UPDATE debates SET end_reason = 'withdrawn', end_proposed_by = NULL WHERE id = ?", [$id]);
					break;
				case 'position':
					self::state($status, ['active', 'paused', 'awaiting_human']);
					Debates::position($id, $actor['user_id'], $input);
					$kind = 'human_reconsidered';
					break;
				case 'stand':
					self::state($status, ['active', 'paused', 'awaiting_human']);
					$kind = 'human_stood_firm';
					break;
				case 'consent':
					Debates::consent($id, $actor['user_id'], ($input['public_consent'] ?? false) === true, ($input['position_consent'] ?? false) === true);
					if ($status === 'open' && ($input['public_consent'] ?? false) !== true) {
						$status = 'draft';
						Db::run("UPDATE debates SET opponent_mode = 'invite' WHERE id = ?", [$id]);
					}
					$kind = 'consent_updated';
					break;
				case 'pause':
					self::state($status, ['active']);
					$status = 'paused'; $kind = 'paused';
					break;
				case 'resume':
					self::state($status, ['paused']);
					$status = 'active'; $kind = 'resumed'; $notify = 'resumed';
					break;
				case 'flag':
					self::state($status, ['active', 'paused', 'awaiting_human']);
					if (($input['confirm'] ?? false) !== true) throw new Failure(422, 'confirmation_required');
					$status = 'flagged'; $kind = 'white_flag'; $notify = 'flag';
					Db::run("UPDATE debates SET flag_user_id = ?, end_reason = 'white_flag', end_proposed_by = NULL WHERE id = ?", [$actor['user_id'], $id]);
					break;
				case 'propose_end':
					self::state($status, ['active', 'paused', 'awaiting_human']);
					if ($row['end_proposed_by'] !== null) throw new Failure(409, 'end_already_proposed');
					$reason = Support::text($input, 'reason', 1, 32);
					if (!in_array($reason, ['agreement', 'partial_agreement', 'draw', 'disagreement'], true)) throw new Failure(422, 'invalid_field', ['field' => 'reason']);
					Db::run('UPDATE debates SET end_proposed_by = ?, end_reason = ? WHERE id = ?', [$actor['user_id'], $reason, $id]);
					$kind = 'end_proposed'; $notify = 'update'; $data = ['reason' => $reason];
					break;
				case 'accept_end':
					self::state($status, ['active', 'paused', 'awaiting_human']);
					if ($row['end_proposed_by'] === null || $row['end_proposed_by'] === $actor['user_id']) throw new Failure(409, 'invalid_transition');
					if (($input['confirm'] ?? false) !== true) throw new Failure(422, 'confirmation_required');
					$status = 'ended'; $kind = 'ended'; $notify = 'ended'; $data = ['reason' => $row['end_reason']];
					Db::run('UPDATE debates SET end_proposed_by = NULL WHERE id = ?', [$id]);
					break;
				case 'cancel_end':
					self::state($status, ['active', 'paused', 'awaiting_human']);
					if ($row['end_proposed_by'] === null) throw new Failure(409, 'invalid_transition');
					Db::run('UPDATE debates SET end_proposed_by = NULL, end_reason = NULL WHERE id = ?', [$id]);
					$kind = 'end_cancelled';
					break;
				case 'archive':
					self::state($status, ['flagged', 'ended']);
					$status = 'archived'; $kind = 'archived';
					break;
				default:
					throw new Failure(422, 'invalid_action');
			}
			Db::run('UPDATE debates SET status = ?, revision = revision + 1, updated_at = ? WHERE id = ?', [$status, Support::now(), $id]);
			$sequence = Debates::event($id, $actor['user_id'], $kind, $data);
			if ($notify !== null) Mail::notify($id, $actor['user_id'], $notify, $sequence);
			return Idempotency::save($actor['user_id'], 'action', $input, ['debate_id' => $id, 'status' => $status, 'revision' => (int) $row['revision'] + 1, 'event_sequence' => $sequence, 'stored_at' => Support::now()]);
		});
	}

	private static function state(string $current, array $allowed): void
	{
		if (!in_array($current, $allowed, true)) throw new Failure(409, 'invalid_transition');
	}
}
