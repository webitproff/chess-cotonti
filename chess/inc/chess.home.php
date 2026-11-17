<?php
/**
 *  File: chess.home.php
 *  Path: plugins/chess/inc/chess.home.php
 *  Mode "home": lobby (vs computer / PvP / open games / statistics). The lobby itself is drawn by js/chess.js.
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

Cot::$out['subtitle'] = Cot::$L['chess_title'];

$t = new XTemplate(cot_tplfile(['chess', 'home'], 'plug'));
$t->assign(chess_tpl_vars('home'));

cot_display_messages($t);

