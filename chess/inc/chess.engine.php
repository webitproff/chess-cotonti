<?php
/**
 *  File: chess.engine.php
 *  Path: plugins/chess/inc/chess.engine.php
 *
 *  Chess rules: FEN, move generation, legality, SAN, game end detection.
 *
 *  Square index: sq = row * 8 + file, row 0 = rank 8 (as in FEN), file 0 = "a".
 *  Pieces: 1 pawn, 2 knight, 3 bishop, 4 rook, 5 queen, 6 king; white > 0, black < 0.
 *  State: ['b' => int[64], 't' => 1|-1, 'c' => castling mask (K=1,Q=2,k=4,q=8),
 *         'ep' => square|-1, 'hm' => halfmove clock, 'fm' => fullmove, 'kw' => white king sq, 'kb' => black king sq]
 *  Move: [from, to, promo (0 or 2..5), flag] ; flag 0 normal, 1 en passant, 2 castle O-O, 3 castle O-O-O, 4 double push
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

function chess_start_fen(): string
{
    return 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
}

function chess_tables(): array
{
    static $T = null;
    if ($T !== null) {
        return $T;
    }
    $T = ['n' => [], 'k' => [], 'ray' => [], 'pa' => [[], []], 'pc' => [[], []], 'mask' => array_fill(0, 64, 15)];
    // dirs 0-3 rook (N, S, E, W), 4-7 bishop (NE, NW, SE, SW) as [drow, dfile]
    $dirs = [[-1, 0], [1, 0], [0, 1], [0, -1], [-1, 1], [-1, -1], [1, 1], [1, -1]];
    $kn = [[-2, -1], [-2, 1], [-1, -2], [-1, 2], [1, -2], [1, 2], [2, -1], [2, 1]];
    $in = static fn(int $r, int $f): bool => $r >= 0 && $r < 8 && $f >= 0 && $f < 8;
    for ($sq = 0; $sq < 64; $sq++) {
        $r = intdiv($sq, 8);
        $f = $sq % 8;
        $T['n'][$sq] = [];
        foreach ($kn as [$dr, $df]) {
            if ($in($r + $dr, $f + $df)) {
                $T['n'][$sq][] = ($r + $dr) * 8 + $f + $df;
            }
        }
        $T['k'][$sq] = [];
        $T['ray'][$sq] = [];
        foreach ($dirs as $d => [$dr, $df]) {
            if ($in($r + $dr, $f + $df)) {
                $T['k'][$sq][] = ($r + $dr) * 8 + $f + $df;
            }
            $ray = [];
            $nr = $r + $dr;
            $nf = $f + $df;
            while ($in($nr, $nf)) {
                $ray[] = $nr * 8 + $nf;
                $nr += $dr;
                $nf += $df;
            }
            $T['ray'][$sq][$d] = $ray;
        }
        // squares from which a pawn of colour index 0 (white) / 1 (black) attacks $sq
        $T['pa'][0][$sq] = [];
        $T['pa'][1][$sq] = [];
        $T['pc'][0][$sq] = [];
        $T['pc'][1][$sq] = [];
        foreach ([-1, 1] as $df) {
            if ($in($r + 1, $f + $df)) { $T['pa'][0][$sq][] = ($r + 1) * 8 + $f + $df; }
            if ($in($r - 1, $f + $df)) { $T['pa'][1][$sq][] = ($r - 1) * 8 + $f + $df; }
            // capture targets of a pawn standing on $sq
            if ($in($r - 1, $f + $df)) { $T['pc'][0][$sq][] = ($r - 1) * 8 + $f + $df; }
            if ($in($r + 1, $f + $df)) { $T['pc'][1][$sq][] = ($r + 1) * 8 + $f + $df; }
        }
    }
    foreach ([60 => 12, 56 => 13, 63 => 14, 4 => 3, 0 => 7, 7 => 11] as $sq => $m) {
        $T['mask'][$sq] = $m;
    }
    return $T;
}

function chess_sq_name(int $sq): string
{
    return chr(97 + $sq % 8) . (8 - intdiv($sq, 8));
}

function chess_sq_index(string $name): int
{
    if (!preg_match('/^[a-h][1-8]$/', $name)) {
        return -1;
    }
    return (8 - (int) $name[1]) * 8 + (ord($name[0]) - 97);
}

/* ---------- FEN ---------- */

function chess_fen_parse(string $fen): ?array
{
    $p = explode(' ', trim($fen));
    if (count($p) < 4) {
        return null;
    }
    $rows = explode('/', $p[0]);
    if (count($rows) !== 8) {
        return null;
    }
    $map = ['p' => 1, 'n' => 2, 'b' => 3, 'r' => 4, 'q' => 5, 'k' => 6];
    $b = array_fill(0, 64, 0);
    $kw = -1;
    $kb = -1;
    foreach ($rows as $r => $row) {
        $f = 0;
        foreach (str_split($row) as $ch) {
            if (ctype_digit($ch)) {
                $f += (int) $ch;
                continue;
            }
            $lc = strtolower($ch);
            if (!isset($map[$lc]) || $f > 7) {
                return null;
            }
            $v = $map[$lc] * (ctype_upper($ch) ? 1 : -1);
            $b[$r * 8 + $f] = $v;
            if ($v === 6) { $kw = $r * 8 + $f; }
            if ($v === -6) { $kb = $r * 8 + $f; }
            $f++;
        }
        if ($f !== 8) {
            return null;
        }
    }
    if ($kw < 0 || $kb < 0) {
        return null;
    }
    $c = 0;
    if (strpos($p[2], 'K') !== false) { $c |= 1; }
    if (strpos($p[2], 'Q') !== false) { $c |= 2; }
    if (strpos($p[2], 'k') !== false) { $c |= 4; }
    if (strpos($p[2], 'q') !== false) { $c |= 8; }
    return [
        'b'  => $b,
        't'  => $p[1] === 'b' ? -1 : 1,
        'c'  => $c,
        'ep' => $p[3] !== '-' ? chess_sq_index($p[3]) : -1,
        'hm' => isset($p[4]) ? (int) $p[4] : 0,
        'fm' => isset($p[5]) ? max(1, (int) $p[5]) : 1,
        'kw' => $kw,
        'kb' => $kb,
    ];
}

function chess_fen_build(array $s): string
{
    $L = [1 => 'p', 2 => 'n', 3 => 'b', 4 => 'r', 5 => 'q', 6 => 'k'];
    $rows = [];
    for ($r = 0; $r < 8; $r++) {
        $row = '';
        $e = 0;
        for ($f = 0; $f < 8; $f++) {
            $v = $s['b'][$r * 8 + $f];
            if ($v === 0) {
                $e++;
                continue;
            }
            if ($e) { $row .= $e; $e = 0; }
            $ch = $L[abs($v)];
            $row .= $v > 0 ? strtoupper($ch) : $ch;
        }
        if ($e) { $row .= $e; }
        $rows[] = $row;
    }
    $c = ($s['c'] & 1 ? 'K' : '') . ($s['c'] & 2 ? 'Q' : '') . ($s['c'] & 4 ? 'k' : '') . ($s['c'] & 8 ? 'q' : '');
    return implode('/', $rows) . ' ' . ($s['t'] === 1 ? 'w' : 'b') . ' ' . ($c ?: '-') . ' '
        . ($s['ep'] >= 0 ? chess_sq_name($s['ep']) : '-') . ' ' . $s['hm'] . ' ' . $s['fm'];
}

/** Position key for repetition detection (first four FEN fields). */
function chess_pos_key(array $s): string
{
    return implode(' ', array_slice(explode(' ', chess_fen_build($s)), 0, 4));
}

/* ---------- attacks ---------- */

function chess_attacked(array &$b, int $sq, int $by): bool
{
    $T = chess_tables();
    $pi = $by === 1 ? 0 : 1;
    foreach ($T['pa'][$pi][$sq] as $s2) {
        if ($b[$s2] === $by) {
            return true;
        }
    }
    $kn = $by * 2;
    foreach ($T['n'][$sq] as $s2) {
        if ($b[$s2] === $kn) {
            return true;
        }
    }
    $kg = $by * 6;
    foreach ($T['k'][$sq] as $s2) {
        if ($b[$s2] === $kg) {
            return true;
        }
    }
    $rk = $by * 4;
    $qn = $by * 5;
    $bs = $by * 3;
    for ($d = 0; $d < 8; $d++) {
        foreach ($T['ray'][$sq][$d] as $s2) {
            $v = $b[$s2];
            if ($v === 0) {
                continue;
            }
            if ($v === $qn || $v === ($d < 4 ? $rk : $bs)) {
                return true;
            }
            break;
        }
    }
    return false;
}

function chess_in_check(array &$s, int $color): bool
{
    return chess_attacked($s['b'], $color === 1 ? $s['kw'] : $s['kb'], -$color);
}

/* ---------- move generation ---------- */

function chess_gen(array &$s, bool $capsOnly = false): array
{
    $T = chess_tables();
    $b = &$s['b'];
    $us = $s['t'];
    $m = [];
    $ci = $us === 1 ? 0 : 1;
    $fw = $us === 1 ? -8 : 8;
    $startR = $us === 1 ? 6 : 1;
    $lastR = $us === 1 ? 0 : 7;

    for ($sq = 0; $sq < 64; $sq++) {
        $p = $b[$sq];
        if ($p * $us <= 0) {
            continue;
        }
        $a = $p * $us;
        if ($a === 1) {
            $to = $sq + $fw;
            if ($b[$to] === 0) {
                if (intdiv($to, 8) === $lastR) {
                    foreach ([5, 4, 3, 2] as $pr) { $m[] = [$sq, $to, $pr, 0]; }
                } elseif (!$capsOnly) {
                    $m[] = [$sq, $to, 0, 0];
                    if (intdiv($sq, 8) === $startR && $b[$to + $fw] === 0) {
                        $m[] = [$sq, $to + $fw, 0, 4];
                    }
                }
            }
            foreach ($T['pc'][$ci][$sq] as $to) {
                $q = $b[$to];
                if ($q * $us < 0) {
                    if (intdiv($to, 8) === $lastR) {
                        foreach ([5, 4, 3, 2] as $pr) { $m[] = [$sq, $to, $pr, 0]; }
                    } else {
                        $m[] = [$sq, $to, 0, 0];
                    }
                } elseif ($q === 0 && $to === $s['ep']) {
                    $m[] = [$sq, $to, 0, 1];
                }
            }
        } elseif ($a === 2) {
            foreach ($T['n'][$sq] as $to) {
                $q = $b[$to];
                if ($q * $us > 0 || ($capsOnly && $q === 0)) {
                    continue;
                }
                $m[] = [$sq, $to, 0, 0];
            }
        } elseif ($a === 6) {
            foreach ($T['k'][$sq] as $to) {
                $q = $b[$to];
                if ($q * $us > 0 || ($capsOnly && $q === 0)) {
                    continue;
                }
                $m[] = [$sq, $to, 0, 0];
            }
            if (!$capsOnly) {
                if ($us === 1 && $sq === 60) {
                    if (($s['c'] & 1) && $b[61] === 0 && $b[62] === 0 && $b[63] === 4
                        && !chess_attacked($b, 60, -1) && !chess_attacked($b, 61, -1)) {
                        $m[] = [60, 62, 0, 2];
                    }
                    if (($s['c'] & 2) && $b[59] === 0 && $b[58] === 0 && $b[57] === 0 && $b[56] === 4
                        && !chess_attacked($b, 60, -1) && !chess_attacked($b, 59, -1)) {
                        $m[] = [60, 58, 0, 3];
                    }
                } elseif ($us === -1 && $sq === 4) {
                    if (($s['c'] & 4) && $b[5] === 0 && $b[6] === 0 && $b[7] === -4
                        && !chess_attacked($b, 4, 1) && !chess_attacked($b, 5, 1)) {
                        $m[] = [4, 6, 0, 2];
                    }
                    if (($s['c'] & 8) && $b[3] === 0 && $b[2] === 0 && $b[1] === 0 && $b[0] === -4
                        && !chess_attacked($b, 4, 1) && !chess_attacked($b, 3, 1)) {
                        $m[] = [4, 2, 0, 3];
                    }
                }
            }
        } else {
            $d0 = $a === 3 ? 4 : 0;
            $d1 = $a === 4 ? 4 : 8;
            for ($d = $d0; $d < $d1; $d++) {
                foreach ($T['ray'][$sq][$d] as $to) {
                    $q = $b[$to];
                    if ($q === 0) {
                        if (!$capsOnly) {
                            $m[] = [$sq, $to, 0, 0];
                        }
                        continue;
                    }
                    if ($q * $us < 0) {
                        $m[] = [$sq, $to, 0, 0];
                    }
                    break;
                }
            }
        }
    }
    return $m;
}

function chess_make(array &$s, array $m): array
{
    $T = chess_tables();
    $b = &$s['b'];
    [$from, $to, $promo, $fl] = $m;
    $piece = $b[$from];
    $us = $s['t'];
    $u = ['c' => $s['c'], 'ep' => $s['ep'], 'hm' => $s['hm'], 'cs' => $to, 'pc' => $piece, 'kw' => $s['kw'], 'kb' => $s['kb']];
    $cap = $b[$to];
    if ($fl === 1) {
        $csq = $to + ($us === 1 ? 8 : -8);
        $cap = $b[$csq];
        $b[$csq] = 0;
        $u['cs'] = $csq;
    }
    $u['cap'] = $cap;
    $b[$to] = $promo ? $promo * $us : $piece;
    $b[$from] = 0;
    if ($piece === $us * 6) {
        if ($us === 1) { $s['kw'] = $to; } else { $s['kb'] = $to; }
    }
    if ($fl === 2) {
        $b[$to - 1] = $b[$to + 1];
        $b[$to + 1] = 0;
    } elseif ($fl === 3) {
        $b[$to + 1] = $b[$to - 2];
        $b[$to - 2] = 0;
    }
    $s['c'] &= $T['mask'][$from] & $T['mask'][$to];
    $s['ep'] = $fl === 4 ? intdiv($from + $to, 2) : -1;
    $s['hm'] = (abs($piece) === 1 || $cap !== 0) ? 0 : $s['hm'] + 1;
    if ($us === -1) {
        $s['fm']++;
    }
    $s['t'] = -$us;
    return $u;
}

function chess_unmake(array &$s, array $m, array $u): void
{
    $b = &$s['b'];
    [$from, $to, $promo, $fl] = $m;
    $s['t'] = -$s['t'];
    $us = $s['t'];
    $b[$from] = $u['pc'];
    $b[$to] = 0;
    if ($fl === 1) {
        $b[$u['cs']] = $u['cap'];
    } else {
        $b[$to] = $u['cap'];
    }
    if ($fl === 2) {
        $b[$to + 1] = $b[$to - 1];
        $b[$to - 1] = 0;
    } elseif ($fl === 3) {
        $b[$to - 2] = $b[$to + 1];
        $b[$to + 1] = 0;
    }
    $s['c'] = $u['c'];
    $s['ep'] = $u['ep'];
    $s['hm'] = $u['hm'];
    $s['kw'] = $u['kw'];
    $s['kb'] = $u['kb'];
    if ($us === -1) {
        $s['fm']--;
    }
}

function chess_legal(array &$s): array
{
    $out = [];
    $us = $s['t'];
    foreach (chess_gen($s) as $mv) {
        $u = chess_make($s, $mv);
        if (!chess_attacked($s['b'], $us === 1 ? $s['kw'] : $s['kb'], -$us)) {
            $out[] = $mv;
        }
        chess_unmake($s, $mv, $u);
    }
    return $out;
}

/* ---------- notation ---------- */

function chess_piece_letter(int $type): string
{
    return [1 => 'p', 2 => 'n', 3 => 'b', 4 => 'r', 5 => 'q', 6 => 'k'][$type] ?? '';
}

/** Standard algebraic notation of a legal move (call before the move is made). */
function chess_san(array &$s, array $mv, array $legal): string
{
    $b = $s['b'];
    [$from, $to, $promo, $fl] = $mv;
    $piece = abs($b[$from]);
    if ($fl === 2) {
        $san = 'O-O';
    } elseif ($fl === 3) {
        $san = 'O-O-O';
    } else {
        $capture = ($b[$to] !== 0 || $fl === 1);
        $dest = chess_sq_name($to);
        if ($piece === 1) {
            $san = $capture ? chr(97 + $from % 8) . 'x' . $dest : $dest;
            if ($promo) {
                $san .= '=' . strtoupper(chess_piece_letter($promo));
            }
        } else {
            $san = strtoupper(chess_piece_letter($piece));
            $others = [];
            foreach ($legal as $o) {
                if ($o[1] === $to && $o[0] !== $from && abs($b[$o[0]]) === $piece) {
                    $others[] = $o[0];
                }
            }
            if ($others) {
                $sameFile = false;
                $sameRank = false;
                foreach ($others as $o) {
                    if ($o % 8 === $from % 8) { $sameFile = true; }
                    if (intdiv($o, 8) === intdiv($from, 8)) { $sameRank = true; }
                }
                if (!$sameFile) {
                    $san .= chr(97 + $from % 8);
                } elseif (!$sameRank) {
                    $san .= (8 - intdiv($from, 8));
                } else {
                    $san .= chess_sq_name($from);
                }
            }
            $san .= ($capture ? 'x' : '') . $dest;
        }
    }
    $u = chess_make($s, $mv);
    $opp = $s['t'];
    if (chess_in_check($s, $opp)) {
        $san .= count(chess_legal($s)) > 0 ? '+' : '#';
    }
    chess_unmake($s, $mv, $u);
    return $san;
}

/* ---------- material / game end ---------- */

function chess_insufficient(array $b): bool
{
    $minors = [];
    foreach ($b as $sq => $v) {
        $a = abs($v);
        if ($a === 1 || $a === 4 || $a === 5) {
            return false;
        }
        if ($a === 2 || $a === 3) {
            $minors[] = [$a, (intdiv($sq, 8) + $sq % 8) % 2];
        }
    }
    if (count($minors) <= 1) {
        return true;
    }
    $color = null;
    foreach ($minors as [$a, $sc]) {
        if ($a !== 3) {
            return false;
        }
        if ($color === null) { $color = $sc; }
        if ($color !== $sc) { return false; }
    }
    return true;   // only bishops, all on squares of one colour
}

/** Can the given colour (1/-1) still give mate? Used to decide timeout results. */
function chess_can_mate(array $b, int $color): bool
{
    $minors = 0;
    $oppPieces = 0;
    foreach ($b as $v) {
        $a = abs($v);
        if ($v * $color > 0) {
            if ($a === 1 || $a === 4 || $a === 5) { return true; }
            if ($a === 2 || $a === 3) { $minors++; }
        } elseif ($v !== 0 && $a !== 6) {
            $oppPieces++;
        }
    }
    return $minors >= 2 || ($minors === 1 && $oppPieces > 0);
}

/**
 * Game end detection for the side to move.
 *
 * @param string[] $hist position keys including the current one
 * @return array{over: bool, result: string, reason: string, check: bool, legal: array}
 */
function chess_game_over(array &$s, array $hist): array
{
    $legal = chess_legal($s);
    $us = $s['t'];
    $chk = chess_in_check($s, $us);
    $r = ['over' => false, 'result' => '', 'reason' => '', 'check' => $chk, 'legal' => $legal];
    if (!$legal) {
        $r['over'] = true;
        if ($chk) {
            $r['result'] = $us === 1 ? '0-1' : '1-0';
            $r['reason'] = 'checkmate';
        } else {
            $r['result'] = '1/2-1/2';
            $r['reason'] = 'stalemate';
        }
        return $r;
    }
    if (chess_insufficient($s['b'])) {
        return ['over' => true, 'result' => '1/2-1/2', 'reason' => 'insufficient'] + $r;
    }
    if ($s['hm'] >= 100) {
        return ['over' => true, 'result' => '1/2-1/2', 'reason' => 'fifty'] + $r;
    }
    $key = chess_pos_key($s);
    $n = 0;
    foreach ($hist as $h) {
        if ($h === $key) { $n++; }
    }
    if ($n >= 3) {
        return ['over' => true, 'result' => '1/2-1/2', 'reason' => 'repetition'] + $r;
    }
    return $r;
}
