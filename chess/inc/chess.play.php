<?php
/**
 * File: chess.play.php
 * Path: plugins/chess/inc/chess.play.php
 * Mode "play": a single game (?e=chess&m=play&id=N). Only the two players may open it.
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
$cxGid = (int) cot_import('id', 'G', 'INT');

if ($cxUid > 0) {
	$cxGame = $cxGid > 0
		? Cot::$db->query('SELECT white_id, black_id FROM ' . Cot::$db_x . 'chess_games WHERE game_id = ?', [$cxGid])->fetch()
		: false;
	if (!$cxGame || chess_color($cxGame, $cxUid) === '') {
		cot_message(Cot::$L['chess_err_no_game'], 'error');
		cot_redirect(chess_url('home', [], true));
	}
}

Cot::$out['subtitle'] = Cot::$L['chess_title'] . ' #' . $cxGid;

$t = new XTemplate(cot_tplfile(['chess', 'play'], 'plug'));
$t->assign(chess_tpl_vars('play', ['gameId' => $cxGid]));
$t->assign('CX_GAME_ID', $cxGid);

cot_display_messages($t);

