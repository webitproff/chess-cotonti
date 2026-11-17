<?php
/**
 *  File: chess.my.php
 *  Path: plugins/chess/inc/chess.my.php
 *  Mode "my": my games - list, continue, cancel / resign. Rendered on the server (no JS needed). 
 *
 *
 *  Web Application (Plugin) for CMF Cotonti v.1.0 compatible with PHP 8.5+, MySQL 8.4+
 *  Description=Chess: vs computer and PvP with clocks, chat and sounds
 *  Version=1.1.1
 *  Date=2026-Nov-17
 *  Author=webitproff
 *  Copyright=webitproff https://github.com/webitproff/chess-cotonti
 *  License: BSD
 */
 
defined('COT_CODE') or die('Wrong URL.');

$cxUid = (int) Cot::$usr['id'];
$cxX   = Cot::$db_x;

Cot::$out['subtitle'] = Cot::$L['chess_title'] . ' - ' . Cot::$L['chess_nav_my'];

// Cancel (waiting game) / resign (active game)
if ($cxUid > 0) {
	$cxAct = cot_import('a', 'G', 'ALP');
	$cxId  = (int) cot_import('id', 'G', 'INT');
	if ($cxAct === 'cancel' && $cxId > 0) {
		cot_check_xg();
		try {
			$cxRes = chess_resign($cxUid, $cxId);
		} catch (Throwable $e) {
			$cxRes = ['success' => false, 'message' => Cot::$L['chess_err_unknown']];
		}
		cot_message($cxRes['success'] ? Cot::$L['chess_my_cancelled'] : $cxRes['message'], $cxRes['success'] ? 'ok' : 'error');
		cot_redirect(chess_url('my', [], true));
	}
}

$t = new XTemplate(cot_tplfile(['chess', 'my'], 'plug'));
$t->assign(chess_tpl_vars('my'));

if ($cxUid > 0) {
	chess_cleanup();

	$cxUsers = '`' . $cxX . 'users`';
	$cxRows = Cot::$db->query(
		"SELECT g.*, u1.user_name AS n1, u2.user_name AS n2
		 FROM {$cxX}chess_games g
		 LEFT JOIN {$cxUsers} u1 ON u1.user_id = g.white_id
		 LEFT JOIN {$cxUsers} u2 ON u2.user_id = g.black_id
		 WHERE g.white_id = ? OR g.black_id = ?
		 ORDER BY g.updated_at DESC LIMIT 60",
		[$cxUid, $cxUid]
	)->fetchAll();

	$cxActive = 0;
	$cxDone   = 0;
	foreach ($cxRows as $g) {
		$color = chess_color($g, $cxUid);
		$isAi  = (int) $g['is_ai'] === 1;
		$oppId = $color === 'w' ? $g['black_id'] : $g['white_id'];
		$oppNm = $color === 'w' ? $g['n2'] : $g['n1'];
		if ($isAi) {
			$opp = Cot::$L['chess_ai_name'];
		} elseif ($oppId === null) {
			$opp = Cot::$L['chess_wait_opp'];
		} else {
			$opp = (string) $oppNm;
		}
		$mode = $isAi ? Cot::$L['chess_my_mode_ai'] : Cot::$L['chess_my_mode_pvp'];
		$tc   = ((int) $g['tc_base'] > 0) ? ((int) ($g['tc_base'] / 60) . '+' . (int) $g['tc_inc']) : Cot::$L['chess_tc_none'];
		$col  = $color === 'w' ? Cot::$L['chess_white'] : Cot::$L['chess_black'];

		if ($g['status'] !== 'finished') {
			$myTurn = $g['status'] === 'active' && chess_turn_of($g) === $color;
			$t->assign([
				'ROW_ID'           => (int) $g['game_id'],
				'ROW_MODE'         => $mode,
				'ROW_COLOR'        => $col,
				'ROW_TC'           => $tc,
				'ROW_OPP'          => htmlspecialchars($opp),
				'ROW_STATUS'       => $g['status'] === 'waiting' ? Cot::$L['chess_state_waiting'] : ($myTurn ? Cot::$L['chess_state_your_turn'] : Cot::$L['chess_state_enemy_turn']),
				'ROW_STATUS_CLASS' => $g['status'] === 'waiting' ? 'warning' : ($myTurn ? 'success' : 'secondary'),
				'ROW_UPDATED'      => $g['updated_at'],
				'ROW_PLAY_URL'     => chess_url('play', ['id' => (int) $g['game_id']]),
				'ROW_CANCEL_URL'   => chess_url('my', ['a' => 'cancel', 'id' => (int) $g['game_id'], 'x' => Cot::$sys['xk']]),
				'ROW_CANCEL_LABEL' => ($g['status'] === 'active') ? Cot::$L['chess_btn_resign'] : Cot::$L['chess_btn_cancel_game'],
			]);
			$t->parse('MAIN.ACTIVE_ROW');
			$cxActive++;
		} elseif ($cxDone < 15) {
			$res = $g['result'];
			if ($res === '1/2-1/2') {
				$txt = Cot::$L['chess_my_res_draw'];
				$cls = 'secondary';
			} elseif ($res === '') {
				$txt = Cot::$L['chess_my_res_none'];
				$cls = 'secondary';
			} elseif (($res === '1-0') === ($color === 'w')) {
				$txt = Cot::$L['chess_result_win'];
				$cls = 'success';
			} else {
				$txt = Cot::$L['chess_result_lose'];
				$cls = 'danger';
			}
			$t->assign([
				'FROW_ID'           => (int) $g['game_id'],
				'FROW_MODE'         => $mode,
				'FROW_COLOR'        => $col,
				'FROW_OPP'          => htmlspecialchars($opp),
				'FROW_RESULT'       => $txt,
				'FROW_RESULT_CLASS' => $cls,
				'FROW_REASON'       => Cot::$L['chess_reason_' . ($g['finish_reason'] ?: 'abandon')] ?? '',
				'FROW_UPDATED'      => $g['updated_at'],
			]);
			$t->parse('MAIN.FINISHED_ROW');
			$cxDone++;
		}
	}
	if (!$cxActive) {
		$t->parse('MAIN.ACTIVE_EMPTY');
	}
	if (!$cxDone) {
		$t->parse('MAIN.FINISHED_EMPTY');
	}
}

cot_display_messages($t);

