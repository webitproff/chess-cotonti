<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=standalone
Order=10
[END_COT_EXT]
==================== */

/**
 *  File: chess.php
 *  Path: plugins/chess/chess.php
 *  Standalone entry + mode router
 *
 *  Modes (files in plugins/chess/inc/, templates in plugins/chess/tpl/):
 *   home (default) - ?e=chess              lobby: vs computer / PvP / open games / statistics
 *   my             - ?e=chess&m=my          my games: list, continue, cancel
 *   play           - ?e=chess&m=play&id=N   a single game
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

Cot::$env['location'] = 'chess';

require_once cot_incfile('chess', 'plug');

list($usr['auth_read'], $usr['auth_write'], $usr['isadmin']) = cot_auth('plug', 'chess');
cot_block($usr['auth_read']);

chess_lang_load();

$m = cot_import('m', 'G', 'ALP');
if (empty($m) || !in_array($m, ['home', 'my', 'play'], true)) {
	$m = 'home';
}

// CSS/JS only where boards are drawn (home + play)
if (in_array($m, ['home', 'play'], true)) {
	Resources::addFile(Cot::$cfg['plugins_dir'] . '/chess/css/chess.css', 'css', 50, 'global');
	Resources::linkFileFooter(Cot::$cfg['plugins_dir'] . '/chess/js/chess.js');
}

require_once cot_incfile('chess', 'plug', $m);
