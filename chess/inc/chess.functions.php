<?php
/**
 *  File: chess.functions.php
 *  Path: plugins/chess/inc/chess.functions.php
 *
 *  Game flow on the server: create/join, moves, clocks, draws, resign, lobby, statistics, chat.
 *
 *  Conventions:
 *  - Colours are 'w' / 'b'. white_id / black_id hold user ids; 0 = computer, NULL = seat is free.
 *  - The position is stored as FEN; `hist` keeps position keys for repetition; `moves` is the move log (JSON).
 *  - Clocks: clock_w / clock_b are milliseconds left as of clock_ts. The clock of a side does not run
 *    until both sides have made their first move.
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

require_once __DIR__ . '/chess.engine.php';
require_once __DIR__ . '/chess.ai.php';

/* ---------- helpers ---------- */

function chess_tc_list(): array
{
    return ['0+0', '1+0', '3+2', '5+0', '10+0', '15+10', '30+0'];
}

/** Time control code like "5+3" (minutes + increment seconds) from stored seconds. */
function chess_tc_code(int $baseSec, int $inc): string
{
    return intdiv($baseSec, 60) . '+' . $inc;
}

function chess_cfg(string $key, $default)
{
    $v = Cot::$cfg['plugin']['chess'][$key] ?? null;
    return ($v === null || $v === '') ? $default : $v;
}

function chess_lang_load(): void
{
    if (!isset(Cot::$L['chess_title'])) {
        $L = [];
        require cot_langfile('chess', 'plug');
        Cot::$L = array_merge(Cot::$L, $L);
    }
}

function chess_msg(string $key): string
{
    return Cot::$L['chess_' . $key] ?? $key;
}

function chess_fail(string $key): array
{
    return ['success' => false, 'message' => chess_msg($key)];
}

function chess_now(): string
{
    return date('Y-m-d H:i:s');
}

function chess_now_ms(): int
{
    return (int) round(microtime(true) * 1000);
}

function chess_user_name(int $id): string
{
    if ($id <= 0) {
        return chess_msg('ai_name');
    }
    $name = Cot::$db->query('SELECT user_name FROM `' . Cot::$db_x . 'users` WHERE user_id = ?', [$id])->fetchColumn();
    return $name !== false ? (string) $name : ('#' . $id);
}

function chess_decode(?string $json): array
{
    $v = json_decode((string) $json, true);
    return is_array($v) ? $v : [];
}

/** 'w', 'b' or '' for a user in a game row. */
function chess_color(array $g, int $uid): string
{
    if ($uid <= 0) {
        return '';
    }
    if ($g['white_id'] !== null && (int) $g['white_id'] === $uid) {
        return 'w';
    }
    if ($g['black_id'] !== null && (int) $g['black_id'] === $uid) {
        return 'b';
    }
    return '';
}

function chess_turn_of(array $g): string
{
    $p = explode(' ', (string) $g['fen']);
    return ($p[1] ?? 'w') === 'b' ? 'b' : 'w';
}

/* ---------- transactions / persistence ---------- */

function chess_transaction(callable $fn): array
{
    $db = Cot::$db;
    $db->beginTransaction();
    try {
        $res = $fn();
        $db->commit();
        return $res;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}

function chess_load_locked(int $gid): ?array
{
    $row = Cot::$db->query('SELECT * FROM ' . Cot::$db_x . 'chess_games WHERE game_id = ? FOR UPDATE', [$gid])->fetch();
    return $row ?: null;
}

function chess_save(array &$g): void
{
    $g['rev'] = (int) $g['rev'] + 1;
    $g['updated_at'] = chess_now();
    Cot::$db->update(Cot::$db_x . 'chess_games', [
        'white_id'      => $g['white_id'] === null ? null : (int) $g['white_id'],
        'black_id'      => $g['black_id'] === null ? null : (int) $g['black_id'],
        'fen'           => $g['fen'],
        'hist'          => $g['hist'],
        'moves'         => $g['moves'],
        'status'        => $g['status'],
        'result'        => $g['result'],
        'finish_reason' => $g['finish_reason'],
        'winner_id'     => $g['winner_id'] === null ? null : (int) $g['winner_id'],
        'clock_w'       => (int) $g['clock_w'],
        'clock_b'       => (int) $g['clock_b'],
        'clock_ts'      => (int) $g['clock_ts'],
        'draw_offer'    => (int) $g['draw_offer'],
        'rev'           => $g['rev'],
        'updated_at'    => $g['updated_at'],
    ], 'game_id = ?', [(int) $g['game_id']]);
}

function chess_cleanup(): void
{
    $hours = max(1, (int) chess_cfg('abandon_hours', 72));
    $limit = date('Y-m-d H:i:s', time() - $hours * 3600);
    Cot::$db->query(
        "UPDATE " . Cot::$db_x . "chess_games SET status = 'finished', finish_reason = 'abandon', draw_offer = 0, rev = rev + 1
         WHERE status IN ('waiting','active') AND updated_at < ?",
        [$limit]
    );
    chess_chat_sweep();
}

/* ---------- clocks ---------- */

function chess_ai_color(array $g): string
{
    if ((int) $g['is_ai'] !== 1) {
        return '';
    }
    return ($g['white_id'] !== null && (int) $g['white_id'] === 0) ? 'w' : 'b';
}

/** @return array{w:int,b:int,run:string} milliseconds left now and which clock is running */
function chess_clock_view(array $g, int $ply, int $nowMs): array
{
    $w = (int) $g['clock_w'];
    $b = (int) $g['clock_b'];
    $run = '';
    if ($g['status'] === 'active' && (int) $g['tc_base'] > 0 && $ply >= 2 && (int) $g['clock_ts'] > 0) {
        $turn = chess_turn_of($g);
        if ($turn !== chess_ai_color($g)) {
            $run = $turn;
            $elapsed = max(0, $nowMs - (int) $g['clock_ts']);
            if ($turn === 'w') {
                $w = max(0, $w - $elapsed);
            } else {
                $b = max(0, $b - $elapsed);
            }
        }
    }
    return ['w' => $w, 'b' => $b, 'run' => $run];
}

/** If the running clock has expired, ends the game. Must be called on a locked row. Returns true if it did. */
function chess_check_flag(array &$g): bool
{
    if ($g['status'] !== 'active') {
        return false;
    }
    $moves = chess_decode($g['moves']);
    $cv = chess_clock_view($g, count($moves), chess_now_ms());
    if ($cv['run'] === '' || $cv[$cv['run']] > 0) {
        return false;
    }
    $loser = $cv['run'];
    $s = chess_fen_parse($g['fen']);
    $winnerColor = $loser === 'w' ? -1 : 1;
    if ($s && !chess_can_mate($s['b'], $winnerColor)) {
        $result = '1/2-1/2';
    } else {
        $result = $loser === 'w' ? '0-1' : '1-0';
    }
    $g['clock_' . $loser] = 0;
    chess_finish($g, $result, 'timeout');
    return true;
}

/* ---------- finishing / statistics ---------- */

function chess_finish(array &$g, string $result, string $reason): void
{
    chess_chat_purge((int) $g['game_id']);   // the chat lives only while the game is active
    $g['status'] = 'finished';
    $g['result'] = $result;
    $g['finish_reason'] = $reason;
    $g['draw_offer'] = 0;
    $g['winner_id'] = $result === '1-0' ? $g['white_id'] : ($result === '0-1' ? $g['black_id'] : null);
    if ($reason !== 'abandon') {
        chess_record_result($g, $result);
    }
}

function chess_record_result(array $g, string $result): void
{
    $db = Cot::$db;
    $x  = Cot::$db_x;
    $isAi = (int) $g['is_ai'] === 1;
    $ids = ['w' => (int) $g['white_id'], 'b' => (int) $g['black_id']];
    $score = ['w' => $result === '1-0' ? 1.0 : ($result === '0-1' ? 0.0 : 0.5)];
    $score['b'] = 1.0 - $score['w'];

    $rating = ['w' => 1000, 'b' => 1000];
    if (!$isAi) {
        foreach (['w', 'b'] as $c) {
            $db->query("INSERT IGNORE INTO {$x}chess_stats (user_id) VALUES (?)", [$ids[$c]]);
            $rating[$c] = (int) $db->query("SELECT rating FROM {$x}chess_stats WHERE user_id = ?", [$ids[$c]])->fetchColumn();
        }
    }
    foreach (['w', 'b'] as $c) {
        $uid = $ids[$c];
        if ($uid <= 0) {
            continue;
        }
        $db->query("INSERT IGNORE INTO {$x}chess_stats (user_id) VALUES (?)", [$uid]);
        $kind = $score[$c] === 1.0 ? 'wins' : ($score[$c] === 0.0 ? 'losses' : 'draws');
        if ($isAi) {
            $col = 'ai_' . $kind;
            $db->query("UPDATE {$x}chess_stats SET {$col} = {$col} + 1 WHERE user_id = ?", [$uid]);
        } else {
            $opp = $c === 'w' ? 'b' : 'w';
            $expected = 1 / (1 + pow(10, ($rating[$opp] - $rating[$c]) / 400));
            $delta = (int) round(24 * ($score[$c] - $expected));
            $db->query("UPDATE {$x}chess_stats SET {$kind} = {$kind} + 1, rating = GREATEST(0, rating + ?) WHERE user_id = ?", [$delta, $uid]);
        }
    }
}

function chess_user_stats(int $uid): array
{
    $row = Cot::$db->query(
        'SELECT wins, losses, draws, ai_wins, ai_losses, ai_draws, rating FROM ' . Cot::$db_x . 'chess_stats WHERE user_id = ?',
        [$uid]
    )->fetch();
    $row = $row ?: ['wins' => 0, 'losses' => 0, 'draws' => 0, 'ai_wins' => 0, 'ai_losses' => 0, 'ai_draws' => 0, 'rating' => 1000];
    return array_map('intval', $row);
}

/* ---------- moves ---------- */

/**
 * Applies a legal move to the state, appends the move record and the position key.
 *
 * @return array the move record
 */
function chess_play(array &$s, array $mv, array &$moves, array &$hist): array
{
    $legal = chess_legal($s);
    $san = chess_san($s, $mv, $legal);
    $by = $s['t'] === 1 ? 'w' : 'b';
    $piece = abs($s['b'][$mv[0]]);
    $captured = $mv[3] === 1 ? 1 : abs($s['b'][$mv[1]]);
    $kind = $mv[3] === 2 ? 'ck' : ($mv[3] === 3 ? 'cq' : ($mv[3] === 1 ? 'ep' : ($captured ? 'cap' : '')));
    $rec = [
        'by' => $by,
        'f'  => chess_sq_name($mv[0]),
        't'  => chess_sq_name($mv[1]),
        's'  => $san,
        'p'  => chess_piece_letter($piece),
        'c'  => $captured ? chess_piece_letter($captured) : '',
        'k'  => $kind,
        'pr' => $mv[2] ? chess_piece_letter($mv[2]) : '',
        'ch' => substr($san, -1) === '#' ? 2 : (substr($san, -1) === '+' ? 1 : 0),
    ];
    chess_make($s, $mv);
    $moves[] = $rec;
    $hist[] = chess_pos_key($s);
    return $rec;
}

/** Re-evaluates the position after a move; finishes the game if it is over. */
function chess_after_move(array &$g, array &$s, array $hist): array
{
    $go = chess_game_over($s, $hist);
    $g['fen'] = chess_fen_build($s);
    if ($go['over']) {
        chess_finish($g, $go['result'], $go['reason']);
    }
    return $go;
}

function chess_ai_reply(array &$g, array &$s, array &$moves, array &$hist): void
{
    $level = in_array($g['ai_level'], ['easy', 'normal', 'hard'], true) ? $g['ai_level'] : 'normal';
    $mv = chess_ai_pick($s, $level);
    if ($mv === null) {
        return;
    }
    chess_play($s, $mv, $moves, $hist);
    chess_after_move($g, $s, $hist);
}

/* ---------- payload ---------- */

function chess_payload(array $g, int $uid): array
{
    $color = chess_color($g, $uid);
    $moves = chess_decode($g['moves']);
    $nowMs = chess_now_ms();
    $cv = chess_clock_view($g, count($moves), $nowMs);
    $isAi = (int) $g['is_ai'] === 1;
    $oppColor = $color === 'w' ? 'b' : 'w';
    $oppId = $g[$oppColor === 'w' ? 'white_id' : 'black_id'];
    $turn = chess_turn_of($g);

    $legal = [];
    $promo = [];
    $check = '';
    $s = chess_fen_parse($g['fen']);
    if ($s) {
        $leg = chess_legal($s);
        if (chess_in_check($s, $s['t'])) {
            $check = chess_sq_name($s['t'] === 1 ? $s['kw'] : $s['kb']);
        }
        if ($g['status'] === 'active' && $turn === $color) {
            foreach ($leg as $m) {
                $f = chess_sq_name($m[0]);
                $t = chess_sq_name($m[1]);
                $legal[$f][$t] = $t;
                if ($m[2]) {
                    $promo[$f . $t] = 1;
                }
            }
            foreach ($legal as $f => $list) {
                $legal[$f] = array_values($list);
            }
        }
    }

    return [
        'success'    => true,
        'game_id'    => (int) $g['game_id'],
        'rev'        => (int) $g['rev'],
        'status'     => $g['status'],
        'side'       => $color,
        'turn'       => $turn,
        'is_ai'      => $isAi,
        'ai_level'   => $g['ai_level'],
        'fen'        => $g['fen'],
        'moves'      => $moves,
        'result'     => $g['result'],
        'reason'     => $g['finish_reason'],
        'me'         => chess_user_name($uid),
        'opp'        => $oppId === null ? '' : chess_user_name((int) $oppId),
        'opp_joined' => $oppId !== null,
        'legal'      => $legal ?: new stdClass(),
        'promo'      => $promo ?: new stdClass(),
        'check'      => $check,
        'clock'      => [
            'base' => (int) $g['tc_base'],
            'inc'  => (int) $g['tc_inc'],
            'w'    => $cv['w'],
            'b'    => $cv['b'],
            'run'  => $cv['run'],
        ],
        'draw_offer' => (int) $g['draw_offer'] === 1 ? 'w' : ((int) $g['draw_offer'] === 2 ? 'b' : ''),
        'chat'       => !$isAi && chess_chat_enabled(),
    ];
}

function chess_get_state(int $uid, int $gid, int $knownRev): array
{
    $g = Cot::$db->query('SELECT * FROM ' . Cot::$db_x . 'chess_games WHERE game_id = ?', [$gid])->fetch();
    if (!$g) {
        return chess_fail('err_no_game');
    }
    if (chess_color($g, $uid) === '') {
        return chess_fail('err_access');
    }
    // a flag may have fallen: resolve it on a locked row
    if ($g['status'] === 'active' && (int) $g['tc_base'] > 0) {
        $cv = chess_clock_view($g, count(chess_decode($g['moves'])), chess_now_ms());
        if ($cv['run'] !== '' && $cv[$cv['run']] <= 0) {
            return chess_transaction(function () use ($uid, $gid) {
                $g = chess_load_locked($gid);
                if ($g && chess_check_flag($g)) {
                    chess_save($g);
                }
                return $g ? chess_payload($g, $uid) : chess_fail('err_no_game');
            });
        }
    }
    // clocks run without changing rev: always resend while a clock runs, otherwise honour rev
    if ($knownRev > 0 && $knownRev === (int) $g['rev'] && !((int) $g['tc_base'] > 0 && $g['status'] === 'active')) {
        return ['success' => true, 'unchanged' => true, 'status' => $g['status']];
    }
    if ($knownRev > 0 && $knownRev === (int) $g['rev']) {
        $moves = chess_decode($g['moves']);
        $cv = chess_clock_view($g, count($moves), chess_now_ms());
        return ['success' => true, 'unchanged' => true, 'status' => $g['status'], 'clock' => [
            'base' => (int) $g['tc_base'], 'inc' => (int) $g['tc_inc'], 'w' => $cv['w'], 'b' => $cv['b'], 'run' => $cv['run'],
        ]];
    }
    return chess_payload($g, $uid);
}

/* ---------- game flow ---------- */

function chess_active_count(int $uid): int
{
    return (int) Cot::$db->query(
        "SELECT COUNT(*) FROM " . Cot::$db_x . "chess_games
         WHERE (white_id = ? OR black_id = ?) AND status IN ('waiting','active')",
        [$uid, $uid]
    )->fetchColumn();
}

function chess_create_game(int $uid, string $mode, string $color, string $tc, string $level): array
{
    if ($mode !== 'ai' && $mode !== 'pvp') {
        return chess_fail('err_unknown');
    }
    if (!in_array($tc, chess_tc_list(), true)) {
        $tc = '0+0';
    }
    if (!in_array($level, ['easy', 'normal', 'hard'], true)) {
        $level = (string) chess_cfg('ai_level', 'normal');
    }
    if ($color !== 'w' && $color !== 'b') {
        $color = random_int(0, 1) ? 'w' : 'b';
    }
    chess_cleanup();
    if (chess_active_count($uid) >= max(1, (int) chess_cfg('max_active_games', 3))) {
        return chess_fail('err_limit');
    }

    [$base, $inc] = array_map('intval', explode('+', $tc));
    $base *= 60;   // minutes -> seconds
    $isAi = $mode === 'ai';
    $s = chess_fen_parse(chess_start_fen());
    $moves = [];
    $hist = [chess_pos_key($s)];
    $now = chess_now();

    $g = [
        'game_id' => 0, 'white_id' => null, 'black_id' => null, 'is_ai' => $isAi ? 1 : 0,
        'ai_level' => $isAi ? $level : '', 'fen' => chess_fen_build($s), 'status' => $isAi ? 'active' : 'waiting',
        'result' => '', 'finish_reason' => '', 'winner_id' => null, 'draw_offer' => 0,
        'tc_base' => $base, 'tc_inc' => $inc, 'clock_w' => $base * 1000, 'clock_b' => $base * 1000, 'clock_ts' => 0,
    ];
    $g[$color === 'w' ? 'white_id' : 'black_id'] = $uid;
    if ($isAi) {
        $g[$color === 'w' ? 'black_id' : 'white_id'] = 0;
        if ($color === 'b') {
            chess_ai_reply($g, $s, $moves, $hist);   // the computer plays white and moves first
        }
    }
    Cot::$db->insert(Cot::$db_x . 'chess_games', [
        'white_id'      => $g['white_id'],
        'black_id'      => $g['black_id'],
        'is_ai'         => $g['is_ai'],
        'ai_level'      => $g['ai_level'],
        'fen'           => $g['fen'],
        'hist'          => json_encode($hist),
        'moves'         => json_encode($moves),
        'status'        => $g['status'],
        'result'        => '',
        'finish_reason' => '',
        'tc_base'       => $base,
        'tc_inc'        => $inc,
        'clock_w'       => $base * 1000,
        'clock_b'       => $base * 1000,
        'clock_ts'      => 0,
        'draw_offer'    => 0,
        'rev'           => 1,
        'created_at'    => $now,
        'updated_at'    => $now,
    ]);
    return ['success' => true, 'game_id' => (int) Cot::$db->lastInsertId()];
}

function chess_join_game(int $uid, int $gid): array
{
    chess_cleanup();
    if (chess_active_count($uid) >= max(1, (int) chess_cfg('max_active_games', 3))) {
        return chess_fail('err_limit');
    }
    return chess_transaction(function () use ($uid, $gid) {
        $g = chess_load_locked($gid);
        if (!$g || $g['status'] !== 'waiting' || (int) $g['is_ai'] === 1) {
            return chess_fail('err_no_game');
        }
        if (chess_color($g, $uid) !== '') {
            return chess_fail('err_own_game');
        }
        $g[$g['white_id'] === null ? 'white_id' : 'black_id'] = $uid;
        $g['status'] = 'active';
        chess_save($g);
        return ['success' => true, 'game_id' => $gid];
    });
}

function chess_make_move(int $uid, int $gid, string $from, string $to, string $promo): array
{
    $f = chess_sq_index($from);
    $t = chess_sq_index($to);
    if ($f < 0 || $t < 0) {
        return chess_fail('err_coords');
    }
    $pr = ['q' => 5, 'r' => 4, 'b' => 3, 'n' => 2][strtolower($promo)] ?? 0;

    return chess_transaction(function () use ($uid, $gid, $f, $t, $pr) {
        $g = chess_load_locked($gid);
        if (!$g || $g['status'] !== 'active') {
            return chess_fail('err_no_game');
        }
        $color = chess_color($g, $uid);
        if ($color === '') {
            return chess_fail('err_access');
        }
        if (chess_check_flag($g)) {
            chess_save($g);
            return chess_payload($g, $uid);
        }
        if (chess_turn_of($g) !== $color) {
            return chess_fail('err_not_your_turn');
        }
        $s = chess_fen_parse($g['fen']);
        if (!$s) {
            return chess_fail('err_unknown');
        }
        $mv = null;
        foreach (chess_legal($s) as $m) {
            if ($m[0] === $f && $m[1] === $t && ($m[2] === 0 || $m[2] === $pr)) {
                $mv = $m;
                break;
            }
        }
        if ($mv === null) {
            return chess_fail('err_illegal');
        }
        if ($mv[2] !== 0 && $pr === 0) {
            return chess_fail('err_promo');
        }

        $moves = chess_decode($g['moves']);
        $hist = chess_decode($g['hist']);
        $ply = count($moves);
        $nowMs = chess_now_ms();

        // clock of the mover
        if ((int) $g['tc_base'] > 0 && $ply >= 2 && (int) $g['clock_ts'] > 0) {
            $key = 'clock_' . $color;
            $g[$key] = max(1, (int) $g[$key] - max(0, $nowMs - (int) $g['clock_ts'])) + (int) $g['tc_inc'] * 1000;
        }
        $g['draw_offer'] = 0;

        chess_play($s, $mv, $moves, $hist);
        $go = chess_after_move($g, $s, $hist);
        if (!$go['over'] && (int) $g['is_ai'] === 1) {
            chess_ai_reply($g, $s, $moves, $hist);
        }
        $g['moves'] = json_encode($moves);
        $g['hist'] = json_encode($hist);
        $g['clock_ts'] = ((int) $g['tc_base'] > 0 && count($moves) >= 2) ? chess_now_ms() : 0;
        chess_save($g);
        return chess_payload($g, $uid);
    });
}

function chess_resign(int $uid, int $gid): array
{
    return chess_transaction(function () use ($uid, $gid) {
        $g = chess_load_locked($gid);
        if (!$g || $g['status'] === 'finished') {
            return chess_fail('err_no_game');
        }
        $color = chess_color($g, $uid);
        if ($color === '') {
            return chess_fail('err_access');
        }
        if ($g['status'] === 'waiting') {
            Cot::$db->delete(Cot::$db_x . 'chess_games', 'game_id = ?', [$gid]);
            return ['success' => true, 'deleted' => true];
        }
        if (chess_check_flag($g)) {
            chess_save($g);
            return chess_payload($g, $uid);
        }
        chess_finish($g, $color === 'w' ? '0-1' : '1-0', 'resign');
        chess_save($g);
        return chess_payload($g, $uid);
    });
}

/** Draw handling: $do = offer | accept | decline */
function chess_draw(int $uid, int $gid, string $do): array
{
    return chess_transaction(function () use ($uid, $gid, $do) {
        $g = chess_load_locked($gid);
        if (!$g || $g['status'] !== 'active' || (int) $g['is_ai'] === 1) {
            return chess_fail('err_no_game');
        }
        $color = chess_color($g, $uid);
        if ($color === '') {
            return chess_fail('err_access');
        }
        if (chess_check_flag($g)) {
            chess_save($g);
            return chess_payload($g, $uid);
        }
        $mine = $color === 'w' ? 1 : 2;
        $theirs = 3 - $mine;
        $cur = (int) $g['draw_offer'];
        if ($do === 'offer') {
            if ($cur === 0) {
                $g['draw_offer'] = $mine;
            }
        } elseif ($do === 'accept' && $cur === $theirs) {
            chess_finish($g, '1/2-1/2', 'agreement');
        } elseif ($do === 'decline' && $cur === $theirs) {
            $g['draw_offer'] = 0;
        } else {
            return chess_fail('err_draw');
        }
        chess_save($g);
        return chess_payload($g, $uid);
    });
}

/* ---------- lobby ---------- */

function chess_lobby(int $uid): array
{
    chess_cleanup();
    $db = Cot::$db;
    $x  = Cot::$db_x;
    $ut = '`' . $x . 'users`';

    $rows = $db->query(
        "SELECT g.game_id, g.white_id, g.black_id, g.tc_base, g.tc_inc, g.created_at, u1.user_name AS n1, u2.user_name AS n2
         FROM {$x}chess_games g
         LEFT JOIN {$ut} u1 ON u1.user_id = g.white_id
         LEFT JOIN {$ut} u2 ON u2.user_id = g.black_id
         WHERE g.status = 'waiting' AND g.is_ai = 0 AND (g.white_id IS NULL OR g.black_id IS NULL)
           AND COALESCE(g.white_id, 0) <> ? AND COALESCE(g.black_id, 0) <> ?
         ORDER BY g.created_at DESC LIMIT 20",
        [$uid, $uid]
    )->fetchAll();
    $open = [];
    foreach ($rows as $r) {
        $creatorWhite = $r['white_id'] !== null;
        $open[] = [
            'game_id'    => (int) $r['game_id'],
            'user_name'  => $creatorWhite ? $r['n1'] : $r['n2'],
            'you_play'   => $creatorWhite ? 'b' : 'w',
            'tc'         => chess_tc_code((int) $r['tc_base'], (int) $r['tc_inc']),
            'created_at' => $r['created_at'],
        ];
    }

    $rows = $db->query(
        "SELECT g.*, u1.user_name AS n1, u2.user_name AS n2
         FROM {$x}chess_games g
         LEFT JOIN {$ut} u1 ON u1.user_id = g.white_id
         LEFT JOIN {$ut} u2 ON u2.user_id = g.black_id
         WHERE (g.white_id = ? OR g.black_id = ?) AND g.status IN ('waiting','active')
         ORDER BY g.updated_at DESC LIMIT 20",
        [$uid, $uid]
    )->fetchAll();
    $mine = [];
    foreach ($rows as $r) {
        $color = chess_color($r, $uid);
        $oppId = $color === 'w' ? $r['black_id'] : $r['white_id'];
        $oppNm = $color === 'w' ? $r['n2'] : $r['n1'];
        $mine[] = [
            'game_id'    => (int) $r['game_id'],
            'status'     => $r['status'],
            'is_ai'      => (int) $r['is_ai'] === 1,
            'color'      => $color,
            'my_turn'    => $r['status'] === 'active' && chess_turn_of($r) === $color,
            'opp'        => $oppId === null ? '' : ((int) $oppId === 0 ? chess_msg('ai_name') : (string) $oppNm),
            'tc'         => chess_tc_code((int) $r['tc_base'], (int) $r['tc_inc']),
            'updated_at' => $r['updated_at'],
        ];
    }

    $top = $db->query(
        "SELECT s.user_id, u.user_name, s.rating, s.wins, s.losses, s.draws
         FROM {$x}chess_stats s INNER JOIN {$ut} u ON u.user_id = s.user_id
         WHERE s.wins + s.losses + s.draws > 0
         ORDER BY s.rating DESC, s.wins DESC LIMIT 10"
    )->fetchAll();

    return ['success' => true, 'open' => $open, 'mine' => $mine, 'top' => $top, 'stats' => chess_user_stats($uid), 'me' => $uid];
}

/* ---------- chat (PvP only, lives while status = active) ---------- */

function chess_chat_enabled(): bool
{
    return (int) chess_cfg('chat_enabled', 1) === 1;
}

function chess_chat_cooldown(): int
{
    return min(30, max(2, (int) chess_cfg('chat_cooldown', 5)));
}

function chess_chat_max(): int
{
    return 255;
}

function chess_chat_purge(int $gid): void
{
    Cot::$db->query('DELETE FROM ' . Cot::$db_x . 'chess_chat WHERE game_id = ?', [$gid]);
}

/** Removes messages of finished and deleted games (safety net). */
function chess_chat_sweep(): void
{
    $x = Cot::$db_x;
    Cot::$db->query(
        "DELETE c FROM {$x}chess_chat c
         INNER JOIN {$x}chess_games g ON g.game_id = c.game_id
         WHERE g.status = 'finished'"
    );
    Cot::$db->query(
        "DELETE c FROM {$x}chess_chat c
         LEFT JOIN {$x}chess_games g ON g.game_id = c.game_id
         WHERE g.game_id IS NULL"
    );
}

function chess_chat_clean(string $s): string
{
    $s = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $s) ?? '';
    $s = preg_replace('/\s+/u', ' ', $s) ?? '';
    return trim($s);
}

function chess_chat_send(int $uid, int $gid, string $text): array
{
    if (!chess_chat_enabled()) {
        return chess_fail('err_chat_off');
    }
    $text = chess_chat_clean($text);
    if ($text === '') {
        return chess_fail('err_chat_empty');
    }
    if (mb_strlen($text) > chess_chat_max()) {
        return chess_fail('err_chat_long');
    }
    return chess_transaction(function () use ($uid, $gid, $text) {
        $db = Cot::$db;
        $t  = Cot::$db_x . 'chess_chat';
        $g  = chess_load_locked($gid);   // the row lock also serializes the rate limit check
        if (!$g) {
            return chess_fail('err_no_game');
        }
        if (chess_color($g, $uid) === '') {
            return chess_fail('err_access');
        }
        if ($g['status'] !== 'active' || (int) $g['is_ai'] === 1) {
            return chess_fail('err_chat_closed');
        }
        $now  = time();
        $cd   = chess_chat_cooldown();
        $last = (int) $db->query("SELECT MAX(created_ts) FROM {$t} WHERE user_id = ?", [$uid])->fetchColumn();
        if ($last > 0 && $now - $last < $cd) {
            return ['success' => false, 'message' => chess_msg('err_chat_wait'), 'wait' => $cd - ($now - $last)];
        }
        $db->insert($t, ['game_id' => $gid, 'user_id' => $uid, 'message' => $text, 'created_ts' => $now]);
        $id = (int) $db->lastInsertId();
        $cut = $db->query("SELECT msg_id FROM {$t} WHERE game_id = ? ORDER BY msg_id DESC LIMIT 1 OFFSET 199", [$gid])->fetchColumn();
        if ($cut) {
            $db->query("DELETE FROM {$t} WHERE game_id = ? AND msg_id < ?", [$gid, (int) $cut]);
        }
        return ['success' => true, 'cooldown' => $cd, 'chat' => ['id' => $id, 'mine' => true, 'text' => $text, 'ts' => $now]];
    });
}

function chess_chat_get(int $uid, int $gid, int $after): array
{
    $db = Cot::$db;
    $t  = Cot::$db_x . 'chess_chat';
    $g  = $db->query('SELECT game_id, white_id, black_id, status, is_ai FROM ' . Cot::$db_x . 'chess_games WHERE game_id = ?', [$gid])->fetch();
    if (!$g) {
        return chess_fail('err_no_game');
    }
    if (chess_color($g, $uid) === '') {
        return chess_fail('err_access');
    }
    if ($g['status'] !== 'active' || (int) $g['is_ai'] === 1 || !chess_chat_enabled()) {
        return ['success' => true, 'closed' => true, 'items' => []];
    }
    if ($after > 0) {
        $rows = $db->query(
            "SELECT msg_id, user_id, message, created_ts FROM {$t} WHERE game_id = ? AND msg_id > ? ORDER BY msg_id ASC LIMIT 50",
            [$gid, $after]
        )->fetchAll();
    } else {
        $rows = $db->query(
            "SELECT * FROM (SELECT msg_id, user_id, message, created_ts FROM {$t} WHERE game_id = ? ORDER BY msg_id DESC LIMIT 50) x ORDER BY msg_id ASC",
            [$gid]
        )->fetchAll();
    }
    $items = [];
    foreach ($rows as $r) {
        $items[] = ['id' => (int) $r['msg_id'], 'mine' => (int) $r['user_id'] === $uid, 'text' => (string) $r['message'], 'ts' => (int) $r['created_ts']];
    }
    return ['success' => true, 'closed' => false, 'items' => $items, 'cooldown' => chess_chat_cooldown()];
}

/* ---------- URLs and template helpers ---------- */

function chess_url(string $mode, array $params = [], bool $raw = false): string
{
    $p = ['e' => 'chess'];
    if ($mode !== 'home') {
        $p['m'] = $mode;
    }
    return cot_url('plug', array_merge($p, $params), '', $raw);
}

/** Config for the JS client (all chess_* language strings except admin ones). */
function chess_page_config(string $page, array $extra = []): array
{
    chess_lang_load();
    $lang = [];
    foreach (Cot::$L as $key => $value) {
        $key = (string) $key;
        if (strpos($key, 'chess_') === 0 && strpos($key, 'chess_admin_') !== 0) {
            $lang[substr($key, 6)] = $value;
        }
    }
    return array_merge([
        'page'         => $page,
        'ajax'         => cot_url('plug', ['r' => 'chess'], '', true),
        'xk'           => Cot::$sys['xk'],
        'chatCooldown' => chess_chat_cooldown(),
        'chatMax'      => chess_chat_max(),
        'idleEnabled'  => (int) chess_cfg('idle_enabled', 1) === 1,
        'idleSeconds'  => max(3, (int) chess_cfg('idle_seconds', 5)),
        'aiLevel'      => (string) chess_cfg('ai_level', 'normal'),
        'timeControls' => chess_tc_list(),
        'urls'         => [
            'home' => chess_url('home', [], true),
            'my'   => chess_url('my', [], true),
            'play' => chess_url('play', ['id' => '__ID__'], true),
        ],
        'lang'         => $lang,
    ], $extra);
}

/** Common template variables for the mode templates. */
function chess_tpl_vars(string $page, array $extra = []): array
{
    $json = json_encode(chess_page_config($page, $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return [
        'CX_CONFIG'    => htmlspecialchars($json, ENT_QUOTES, 'UTF-8'),
        'CX_LOGGED'    => (Cot::$usr['id'] > 0),
        'CX_LOGIN_URL' => cot_url('login'),
        'CX_URL_HOME'  => chess_url('home'),
        'CX_URL_MY'    => chess_url('my'),
    ];
}
