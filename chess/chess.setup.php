<?php
/* ====================
[BEGIN_COT_EXT]
Code=chess
Name=Chess
Description=Chess: vs computer and PvP with clocks, chat and sounds (Bootstrap 5.3)
Version=1.1.1
Date=2026-Nov-17
Author=webitproff
Copyright=webitproff https://github.com/webitproff/chess-cotonti
Notes=
Auth_guests=R
Lock_guests=W12345A
Auth_members=RW
Lock_members=12345
[END_COT_EXT]

[BEGIN_COT_EXT_CONFIG]
max_active_games=01:select:1,2,3,5,10:3:Max active games per user
ai_level=02:select:easy,normal,hard:normal:Default computer difficulty
abandon_hours=03:select:1,6,24,72,168:72:Close inactive games after (hours)
chat_enabled=04:radio:0:1:Chat between PvP players
chat_cooldown=05:select:3,5,10,15:5:Chat message interval (seconds)
idle_enabled=06:radio:0:1:Car horn and joke when a player idles on his turn
idle_seconds=07:select:5,10,15,30,60:30:Idle time before the horn (seconds)
[END_COT_EXT_CONFIG]
==================== */
defined('COT_CODE') or die('Wrong URL.');

/** 
 *  File: chess.setup.php 
 *  Path: plugins/chess/chess.setup.php 
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
 
