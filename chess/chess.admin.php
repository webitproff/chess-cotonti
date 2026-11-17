<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=tools
[END_COT_EXT]
==================== */

/**
 *  File: chess.admin.php
 *  Path: plugins/chess/chess.admin.php
 *  Admin tools (Admin -> Other -> Chess): games list with filter, delete a game,
 *  purge finished games, top players.
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

list($usr['auth_read'], $usr['auth_write'], $usr['isadmin']) = cot_auth('plug', 'chess', 'RWA');
cot_block($usr['isadmin']);

require_once cot_incfile('chess', 'plug');
chess_lang_load();

$db   = Cot::$db;
$db_x = Cot::$db_x;
$L    = Cot::$L;

$status = (string) cot_import('status', 'G', 'ALP');
if (!in_array($status, ['waiting', 'active', 'finished'], true)) {
	$status = '';
}
$a  = cot_import('a', 'G', 'ALP');
$id = (int) cot_import('id', 'G', 'INT');

$base = 'm=other&p=chess';

// Delete one game (and its chat)
if ($a === 'delete' && $id > 0) {
	cot_check_xg();
	$db->delete($db_x . 'chess_games', 'game_id = ?', [$id]);
	chess_chat_purge($id);
	cot_message($L['chess_admin_deleted'], 'ok');
	cot_redirect(cot_url('admin', $base . ($status ? '&status=' . $status : ''), '', true));
}

// Purge all finished games
if ($a === 'purge_finished') {
	cot_check_xg();
	$n = $db->delete($db_x . 'chess_games', "status = 'finished'");
	chess_chat_sweep();
	cot_message($L['chess_admin_purged'] . ': ' . (int) $n, 'ok');
	cot_redirect(cot_url('admin', $base, '', true));
}

Cot::$out['subtitle'] = $L['chess_admin_title'];

$where = $status ? ('WHERE g.status = ' . $db->quote($status)) : '';

$totalGames = (int) $db->query("SELECT COUNT(*) FROM {$db_x}chess_games g $where")->fetchColumn();
$cnt = ['waiting' => 0, 'active' => 0, 'finished' => 0];
foreach ($db->query("SELECT status, COUNT(*) AS n FROM {$db_x}chess_games GROUP BY status")->fetchAll() as $r) {
	$cnt[$r['status']] = (int) $r['n'];
}

$perPage = 30;
list($pn, $d, $d_url) = cot_import_pagenav('d', $perPage);

$ut = '`' . $db_x . 'users`';
$games = $db->query(
	"SELECT g.*, u1.user_name AS n1, u2.user_name AS n2
	 FROM {$db_x}chess_games g
	 LEFT JOIN {$ut} u1 ON u1.user_id = g.white_id
	 LEFT JOIN {$ut} u2 ON u2.user_id = g.black_id
	 $where
	 ORDER BY g.updated_at DESC LIMIT " . (int) $d . ', ' . (int) $perPage
)->fetchAll();

$pagenav = cot_pagenav('admin', $base . ($status ? '&status=' . $status : ''), $d, $totalGames, $perPage);

$top = $db->query(
	"SELECT s.*, u.user_name FROM {$db_x}chess_stats s
	 LEFT JOIN {$ut} u ON u.user_id = s.user_id
	 ORDER BY s.rating DESC, s.wins DESC LIMIT 15"
)->fetchAll();

$t = new XTemplate(cot_tplfile(['chess', 'admin'], 'plug'));

$t->assign([
	'ADMIN_TITLE'           => $L['chess_admin_title'],
	'FILTER_ALL_URL'        => cot_url('admin', $base),
	'FILTER_WAITING_URL'    => cot_url('admin', $base . '&status=waiting'),
	'FILTER_ACTIVE_URL'     => cot_url('admin', $base . '&status=active'),
	'FILTER_FINISHED_URL'   => cot_url('admin', $base . '&status=finished'),
	'FILTER_ALL_CLASS'      => $status === '' ? 'active' : '',
	'FILTER_WAITING_CLASS'  => $status === 'waiting' ? 'active' : '',
	'FILTER_ACTIVE_CLASS'   => $status === 'active' ? 'active' : '',
	'FILTER_FINISHED_CLASS' => $status === 'finished' ? 'active' : '',
	'PURGE_URL'             => cot_url('admin', $base . '&a=purge_finished&x=' . Cot::$sys['xk']),
	'CNT_ALL'               => array_sum($cnt),
	'CNT_WAITING'           => $cnt['waiting'],
	'CNT_ACTIVE'            => $cnt['active'],
	'CNT_FINISHED'          => $cnt['finished'],
	'PAGENAV_PAGES'         => $pagenav['main'] ?? '',
	'PAGENAV_PREV'          => $pagenav['prev'] ?? '',
	'PAGENAV_NEXT'          => $pagenav['next'] ?? '',
	'FILTER_STATUS'         => $status,
]);

$statusL = [
	'waiting'  => $L['chess_admin_st_waiting'],
	'active'   => $L['chess_admin_st_active'],
	'finished' => $L['chess_admin_st_finished'],
];

foreach ($games as $g) {
	$isAi = (int) $g['is_ai'] === 1;
	$name = static function ($id, $nm) use ($L): string {
		if ($id === null) {
			return '—';
		}
		return (int) $id === 0 ? $L['chess_ai_name'] : htmlspecialchars((string) ($nm ?: ('#' . $id)));
	};
	$res = $g['result'];
	$t->assign([
		'GAME_ID'         => (int) $g['game_id'],
		'GAME_MODE'       => $isAi ? 'AI' : 'PvP',
		'GAME_STATUS'     => $statusL[$g['status']] ?? $g['status'],
		'GAME_WHITE'      => $name($g['white_id'], $g['n1']),
		'GAME_BLACK'      => $name($g['black_id'], $g['n2']),
		'GAME_TC'         => (int) $g['tc_base'] > 0 ? chess_tc_code((int) $g['tc_base'], (int) $g['tc_inc']) : '—',
		'GAME_MOVES'      => count(chess_decode($g['moves'])),
		'GAME_RESULT'     => $res !== '' ? $res : '—',
		'GAME_REASON'     => $g['finish_reason'] !== '' ? ($L['chess_reason_' . $g['finish_reason']] ?? $g['finish_reason']) : '—',
		'GAME_UPDATED'    => $g['updated_at'],
		'GAME_DELETE_URL' => cot_url('admin', $base . '&a=delete&id=' . (int) $g['game_id'] . '&status=' . $status . '&x=' . Cot::$sys['xk']),
	]);
	$t->parse('MAIN.GAME_ROW');
}
if (!$games) {
	$t->parse('MAIN.EMPTY');
}

foreach ($top as $row) {
	$t->assign([
		'TOP_NAME'   => htmlspecialchars((string) ($row['user_name'] ?: ('#' . $row['user_id']))),
		'TOP_PVP'    => (int) $row['wins'] . ' / ' . (int) $row['draws'] . ' / ' . (int) $row['losses'],
		'TOP_AI'     => (int) $row['ai_wins'] . ' / ' . (int) $row['ai_draws'] . ' / ' . (int) $row['ai_losses'],
		'TOP_RATING' => (int) $row['rating'],
		'TOP_UID'    => (int) $row['user_id'],
	]);
	$t->parse('MAIN.TOP_ROW');
}
if (!$top) {
	$t->parse('MAIN.TOP_EMPTY');
}

cot_display_messages($t);
$t->parse('MAIN');
$pluginBody = $t->text('MAIN');
