<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=ajax
[END_COT_EXT]
==================== */

/**
 *  File: chess.ajax.php
 *  Path: plugins/chess/chess.ajax.php
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

require_once cot_incfile('chess', 'plug');
chess_lang_load();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (Cot::$usr['id'] == 0) {
    echo json_encode(['success' => false, 'message' => Cot::$L['chess_err_auth']]);
    exit;
}

$x = cot_import('x', 'P', 'ALP');
if ($x === null || ($x !== Cot::$sys['xk'] && (!isset(Cot::$sys['xk_prev']) || $x !== Cot::$sys['xk_prev']))) {
    echo json_encode(['success' => false, 'message' => 'CSRF token mismatch']);
    exit;
}

$action = (string) cot_import('action', 'P', 'ALP');
$gid    = (int) cot_import('game_id', 'P', 'INT');
$uid    = (int) Cot::$usr['id'];

try {
    switch ($action) {
        case 'lobby':
            $data = chess_lobby($uid);
            break;
        case 'create':
            $data = chess_create_game(
                $uid,
                (string) cot_import('mode', 'P', 'ALP'),
                (string) cot_import('color', 'P', 'ALP'),
                isset($_POST['tc']) ? preg_replace('/[^0-9+]/', '', (string) $_POST['tc']) : '0+0',
                (string) cot_import('level', 'P', 'ALP')
            );
            break;
        case 'join':
            $data = chess_join_game($uid, $gid);
            break;
        case 'move':
            $data = chess_make_move(
                $uid,
                $gid,
                (string) cot_import('from', 'P', 'ALP'),
                (string) cot_import('to', 'P', 'ALP'),
                (string) cot_import('promo', 'P', 'ALP')
            );
            break;
        case 'state':
            $data = chess_get_state($uid, $gid, (int) cot_import('rev', 'P', 'INT'));
            break;
        case 'resign':
            $data = chess_resign($uid, $gid);
            break;
        case 'draw':
            $data = chess_draw($uid, $gid, (string) cot_import('do', 'P', 'ALP'));
            break;
        case 'chatsend':
            $data = chess_chat_send($uid, $gid, isset($_POST['msg']) ? (string) $_POST['msg'] : '');
            break;
        case 'chatget':
            $data = chess_chat_get($uid, $gid, (int) cot_import('after', 'P', 'INT'));
            break;
        default:
            $data = ['success' => false, 'message' => Cot::$L['chess_err_unknown']];
    }
    echo json_encode($data);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => Cot::$L['chess_err_unknown']]);
}
exit;
