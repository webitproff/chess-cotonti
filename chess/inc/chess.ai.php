<?php
/**
 *  File: chess.ai.php
 *  Path: plugins/chess/inc/chess.ai.php
 *
 *  Computer opponent: alpha-beta search with material + piece-square evaluation.
 *  easy   - one ply with random noise (makes mistakes)
 *  normal - two plies + capture quiescence
 *  hard   - iterative deepening up to 4 plies within a time budget
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

function chess_pst(): array
{
    static $P = null;
    if ($P !== null) {
        return $P;
    }
    $P = [
        1 => [0, 0, 0, 0, 0, 0, 0, 0, 50, 50, 50, 50, 50, 50, 50, 50, 10, 10, 20, 30, 30, 20, 10, 10, 5, 5, 10, 25, 25, 10, 5, 5,
              0, 0, 0, 20, 20, 0, 0, 0, 5, -5, -10, 0, 0, -10, -5, 5, 5, 10, 10, -20, -20, 10, 10, 5, 0, 0, 0, 0, 0, 0, 0, 0],
        2 => [-50, -40, -30, -30, -30, -30, -40, -50, -40, -20, 0, 0, 0, 0, -20, -40, -30, 0, 10, 15, 15, 10, 0, -30, -30, 5, 15, 20, 20, 15, 5, -30,
              -30, 0, 15, 20, 20, 15, 0, -30, -30, 5, 10, 15, 15, 10, 5, -30, -40, -20, 0, 5, 5, 0, -20, -40, -50, -40, -30, -30, -30, -30, -40, -50],
        3 => [-20, -10, -10, -10, -10, -10, -10, -20, -10, 0, 0, 0, 0, 0, 0, -10, -10, 0, 5, 10, 10, 5, 0, -10, -10, 5, 5, 10, 10, 5, 5, -10,
              -10, 0, 10, 10, 10, 10, 0, -10, -10, 10, 10, 10, 10, 10, 10, -10, -10, 5, 0, 0, 0, 0, 5, -10, -20, -10, -10, -10, -10, -10, -10, -20],
        4 => [0, 0, 0, 0, 0, 0, 0, 0, 5, 10, 10, 10, 10, 10, 10, 5, -5, 0, 0, 0, 0, 0, 0, -5, -5, 0, 0, 0, 0, 0, 0, -5,
              -5, 0, 0, 0, 0, 0, 0, -5, -5, 0, 0, 0, 0, 0, 0, -5, -5, 0, 0, 0, 0, 0, 0, -5, 0, 0, 0, 5, 5, 0, 0, 0],
        5 => [-20, -10, -10, -5, -5, -10, -10, -20, -10, 0, 0, 0, 0, 0, 0, -10, -10, 0, 5, 5, 5, 5, 0, -10, -5, 0, 5, 5, 5, 5, 0, -5,
              0, 0, 5, 5, 5, 5, 0, -5, -10, 5, 5, 5, 5, 5, 0, -10, -10, 0, 5, 0, 0, 0, 0, -10, -20, -10, -10, -5, -5, -10, -10, -20],
        6 => [-30, -40, -40, -50, -50, -40, -40, -30, -30, -40, -40, -50, -50, -40, -40, -30, -30, -40, -40, -50, -50, -40, -40, -30, -30, -40, -40, -50, -50, -40, -40, -30,
              -20, -30, -30, -40, -40, -30, -30, -20, -10, -20, -20, -20, -20, -20, -20, -10, 20, 20, 0, 0, 0, 0, 20, 20, 20, 30, 10, 0, 0, 10, 30, 20],
        7 => [-50, -40, -30, -20, -20, -30, -40, -50, -30, -20, -10, 0, 0, -10, -20, -30, -30, -10, 20, 30, 30, 20, -10, -30, -30, -10, 30, 40, 40, 30, -10, -30,
              -30, -10, 30, 40, 40, 30, -10, -30, -30, -10, 20, 30, 30, 20, -10, -30, -30, -30, 0, 0, 0, 0, -30, -30, -50, -30, -30, -30, -30, -30, -30, -50],
    ];
    return $P;
}

/** Static evaluation from the point of view of the side to move. */
function chess_eval(array &$s): int
{
    static $val = [1 => 100, 2 => 320, 3 => 330, 4 => 500, 5 => 900, 6 => 0];
    $P = chess_pst();
    $b = &$s['b'];
    $score = 0;
    $queens = 0;
    foreach ($b as $v) {
        if ($v === 5 || $v === -5) { $queens++; }
    }
    $endgame = $queens === 0;
    for ($sq = 0; $sq < 64; $sq++) {
        $v = $b[$sq];
        if ($v === 0) {
            continue;
        }
        $a = abs($v);
        $t = ($a === 6 && $endgame) ? 7 : $a;
        if ($v > 0) {
            $score += $val[$a] + $P[$t][$sq];
        } else {
            $score -= $val[$a] + $P[$t][$sq ^ 56];
        }
    }
    return $s['t'] === 1 ? $score : -$score;
}

/** Move ordering: captures by MVV-LVA, promotions first. */
function chess_ai_order(array &$s, array $moves): array
{
    static $val = [0, 100, 320, 330, 500, 900, 20000];
    $b = &$s['b'];
    $scored = [];
    foreach ($moves as $m) {
        $victim = $m[3] === 1 ? 1 : abs($b[$m[1]]);
        $sc = 0;
        if ($victim) {
            $sc = 10000 + 10 * $val[$victim] - $val[abs($b[$m[0]])];
        }
        if ($m[2]) {
            $sc += 9000 + $val[$m[2]];
        }
        $scored[] = [$sc, $m];
    }
    usort($scored, static fn($x, $y) => $y[0] <=> $x[0]);
    return array_column($scored, 1);
}

function chess_ai_q(array &$s, int $alpha, int $beta, int $qd, array &$ctx): int
{
    $ctx['n']++;
    if (($ctx['n'] & 255) === 0 && microtime(true) > $ctx['dl']) {
        $ctx['abort'] = true;
    }
    if ($ctx['abort']) {
        return 0;
    }
    $stand = chess_eval($s);
    if ($qd <= 0) {
        return $stand;
    }
    if ($stand >= $beta) {
        return $beta;
    }
    if ($stand > $alpha) {
        $alpha = $stand;
    }
    $us = $s['t'];
    foreach (chess_ai_order($s, chess_gen($s, true)) as $m) {
        $u = chess_make($s, $m);
        if (chess_attacked($s['b'], $us === 1 ? $s['kw'] : $s['kb'], -$us)) {
            chess_unmake($s, $m, $u);
            continue;
        }
        $sc = -chess_ai_q($s, -$beta, -$alpha, $qd - 1, $ctx);
        chess_unmake($s, $m, $u);
        if ($ctx['abort']) {
            return 0;
        }
        if ($sc >= $beta) {
            return $beta;
        }
        if ($sc > $alpha) {
            $alpha = $sc;
        }
    }
    return $alpha;
}

function chess_ai_search(array &$s, int $depth, int $alpha, int $beta, int $ply, array &$ctx): int
{
    $ctx['n']++;
    if (($ctx['n'] & 255) === 0 && microtime(true) > $ctx['dl']) {
        $ctx['abort'] = true;
    }
    if ($ctx['abort']) {
        return 0;
    }
    if ($depth <= 0) {
        return chess_ai_q($s, $alpha, $beta, $ctx['q'], $ctx);
    }
    $us = $s['t'];
    $count = 0;
    foreach (chess_ai_order($s, chess_gen($s)) as $m) {
        $u = chess_make($s, $m);
        if (chess_attacked($s['b'], $us === 1 ? $s['kw'] : $s['kb'], -$us)) {
            chess_unmake($s, $m, $u);
            continue;
        }
        $count++;
        $sc = -chess_ai_search($s, $depth - 1, -$beta, -$alpha, $ply + 1, $ctx);
        chess_unmake($s, $m, $u);
        if ($ctx['abort']) {
            return 0;
        }
        if ($sc >= $beta) {
            return $beta;
        }
        if ($sc > $alpha) {
            $alpha = $sc;
        }
    }
    if ($count === 0) {
        return chess_in_check($s, $us) ? -(100000 - $ply) : 0;
    }
    return $alpha;
}

/**
 * Picks a move for the side to move.
 *
 * @param string $level easy|normal|hard
 * @return array|null move [from, to, promo, flag] or null when there is no legal move
 */
function chess_ai_pick(array $s, string $level): ?array
{
    $legal = chess_legal($s);
    if (!$legal) {
        return null;
    }
    if (count($legal) === 1) {
        return $legal[0];
    }
    shuffle($legal);
    $us = $s['t'];

    if ($level === 'easy') {
        $ctx = ['n' => 0, 'dl' => microtime(true) + 5, 'abort' => false, 'q' => 0];
        $best = $legal[0];
        $bestScore = -PHP_INT_MAX;
        foreach ($legal as $m) {
            $u = chess_make($s, $m);
            $sc = -chess_ai_search($s, 0, -200000, 200000, 1, $ctx) + random_int(-90, 90);
            chess_unmake($s, $m, $u);
            if ($sc > $bestScore) {
                $bestScore = $sc;
                $best = $m;
            }
        }
        return $best;
    }

    $maxDepth = $level === 'hard' ? 4 : 2;
    $ctx = ['n' => 0, 'dl' => microtime(true) + ($level === 'hard' ? 2.0 : 1.0), 'abort' => false, 'q' => $level === 'hard' ? 4 : 3];
    $best = chess_ai_order($s, $legal)[0];
    for ($d = 1; $d <= $maxDepth; $d++) {
        $ctx['abort'] = false;
        $iterBest = null;
        $alpha = -200000;
        $moves = chess_ai_order($s, $legal);
        // try the previous best first
        usort($moves, static fn($x, $y) => ($y === $best) <=> ($x === $best));
        foreach ($moves as $m) {
            $u = chess_make($s, $m);
            $sc = -chess_ai_search($s, $d - 1, -200000, -$alpha, 1, $ctx);
            chess_unmake($s, $m, $u);
            if ($ctx['abort']) {
                break;
            }
            if ($iterBest === null || $sc > $alpha) {
                $iterBest = $m;
                $alpha = max($alpha, $sc);
            }
        }
        if ($ctx['abort'] || $iterBest === null) {
            break;
        }
        $best = $iterBest;
        if ($alpha > 90000) {
            break;   // forced mate found
        }
    }
    return $best;
}
