<?php
/**
 *  File: chess.en.lang.php
 *  Path: plugins/chess/lang/chess.en.lang.php
 *  Chess plugin - English language file.
 *  Phrase arrays: JS picks a random item. Placeholders: {piece} {victim} {victim_nom} {promoted} {from} {to} {san} {name} {n}
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

// ========================
// PLUGIN INFO (ADMIN)
// ========================
$L['info_name'] = 'Chess';
$L['info_desc'] = 'Chess: online game against the computer and between players, clocks, chat with opponent, help and rules';
$L['info_notes'] = 'Latest version <a target="_blank" href="https://github.com/webitproff/chess-cotonti"><strong>via the GitHub link</strong></a>';

// ========================
// TITLES AND DESCRIPTIONS (same values, pulled by other keys)
// ========================
$L['chess_title'] = $L['info_name'];
$L['chess_desc']  = $L['info_desc'];
$L['chess_name']  = $L['info_name'];

// Plugin info and config labels

$L['cfg_max_active_games'] = 'Maximum active games per player';
$L['cfg_max_active_games_hint']  = ''
    . 'This is the number of unfinished games that the player can resume and continue. <br>';

$L['cfg_ai_level']      = 'Difficulty against the computer';
$L['cfg_ai_level_hint'] = ''
    . '<strong>easy</strong> — one ply with random noise (makes mistakes), <br>'
    . '<strong>normal</strong> — two plies + capture quiescence, <br>'
    . '<strong>hard</strong> — iterative deepening up to 4 plies within a time limit';

$L['cfg_abandon_hours'] = 'Close inactive games after (hours)';

$L['cfg_chat_enabled']       = 'Chat between real players in PvP';
$L['cfg_chat_enabled_hint']  = ''
    . 'Chat messages are stored in the DB only while the game is in active status. <br>'
    . 'If the game status is <code>finished</code>, the history is deleted.';

$L['cfg_chat_cooldown'] = 'Interval between chat messages (sec.)';
$L['cfg_idle_enabled'] = 'Sound alert and joke notification if a player "falls asleep" on their turn';
$L['cfg_idle_seconds'] = 'After how many seconds of inactivity to signal';



$L['chess_subtitle'] = 'Play the computer or another player. ';
$L['chess_nav_home'] = 'Lobby';
$L['chess_nav_my'] = 'My games';
$L['chess_loading'] = 'Loading…';
$L['chess_login_required'] = 'Please sign in to play chess.';
$L['chess_btn_login'] = 'Sign in';
$L['chess_ai_name'] = 'Computer';
$L['chess_wait_opp'] = 'Waiting for an opponent…';
$L['chess_white'] = 'White';
$L['chess_black'] = 'Black';
$L['chess_tc_none'] = 'No clock';

$L['chess_piece_k'] = 'King';
$L['chess_piece_q'] = 'Queen';
$L['chess_piece_r'] = 'Rook';
$L['chess_piece_b'] = 'Bishop';
$L['chess_piece_n'] = 'Knight';
$L['chess_piece_p'] = 'Pawn';
$L['chess_piece_k_acc'] = 'the king';
$L['chess_piece_q_acc'] = 'the queen';
$L['chess_piece_r_acc'] = 'the rook';
$L['chess_piece_b_acc'] = 'the bishop';
$L['chess_piece_n_acc'] = 'the knight';
$L['chess_piece_p_acc'] = 'the pawn';

$L['chess_help_title'] = 'Reference: pieces and markers';
$L['chess_help_col_icon'] = 'Piece';
$L['chess_help_col_name'] = 'Name';
$L['chess_help_col_move'] = 'How it moves';
$L['chess_help_col_role'] = 'Role in the game';
$L['chess_help_col_value'] = 'Value';
$L['chess_help_k_move'] = 'One square in any direction. Can castle with a rook. Cannot move into attack.';
$L['chess_help_k_role'] = 'The key piece: if it cannot escape a check, that is checkmate and the game is lost. Keep it safe, and bring it into play in the endgame.';
$L['chess_help_k_value'] = '∞';
$L['chess_help_q_move'] = 'Any number of squares along a file, rank or diagonal. Cannot jump over pieces.';
$L['chess_help_q_role'] = 'The strongest piece, attacking across the whole board. Do not bring it out too early - it is easy to chase away.';
$L['chess_help_q_value'] = '9';
$L['chess_help_r_move'] = 'Any number of squares along a file or rank. Takes part in castling.';
$L['chess_help_r_role'] = 'A long-range piece, happiest on open files. Two rooks on one line are a dangerous pair.';
$L['chess_help_r_value'] = '5';
$L['chess_help_b_move'] = 'Any number of squares diagonally. Stays on one square colour for the whole game.';
$L['chess_help_b_role'] = 'A long-range diagonal sniper. A pair of bishops is very strong in open positions.';
$L['chess_help_b_value'] = '3';
$L['chess_help_n_move'] = 'In an "L": two squares straight and one to the side. The only piece that jumps over others.';
$L['chess_help_n_role'] = 'Master of cramped positions and surprise forks: it can attack two pieces at once. Best near the centre.';
$L['chess_help_n_value'] = '3';
$L['chess_help_p_move'] = 'One square forward (two from its start square). Captures diagonally forward. En passant exists. On the last rank it promotes to a queen, rook, bishop or knight.';
$L['chess_help_p_role'] = 'The soul of the game: builds chains, holds the centre and opens lines for the pieces. It never moves back - think before every step.';
$L['chess_help_p_value'] = '1';

$L['chess_mk_title'] = 'Board markers';
$L['chess_mk_sel'] = 'Selected piece';
$L['chess_mk_sel_d'] = 'Click your piece and its square lights up.';
$L['chess_mk_move'] = 'Possible move';
$L['chess_mk_move_d'] = 'A dot on an empty square: you can go there.';
$L['chess_mk_cap'] = 'Capture';
$L['chess_mk_cap_d'] = 'A ring around an enemy piece: you can take it.';
$L['chess_mk_last'] = 'Last move';
$L['chess_mk_last_d'] = 'Where the piece came from and went to.';
$L['chess_mk_check'] = 'Check';
$L['chess_mk_check_d'] = 'A red glow under a king: it is under attack.';
$L['chess_coords_hint'] = 'Files are letters a-h, ranks are numbers 1-8. For example, e2-e4: the pawn goes from e2 to e4.';

$L['chess_rules_btn'] = 'Basic rules of the game';
$L['chess_rules_title'] = 'Basic rules of chess';
$L['chess_rules_close'] = 'Close';
$L['chess_rules_1_h'] = 'Goal';
$L['chess_rules_1_t'] = 'Checkmate the opponent\'s king: it is in check and no move can save it from capture.';
$L['chess_rules_2_h'] = 'Starting out';
$L['chess_rules_2_t'] = 'White moves first, then players alternate. You cannot skip a move. Rooks stand in the corners, then knights, bishops; the queen stands on her own colour, the king next to her.';
$L['chess_rules_3_h'] = 'Moves and captures';
$L['chess_rules_3_t'] = 'Each piece moves in its own way (see the table above). To capture, move onto the enemy piece\'s square and it is removed. You cannot capture your own pieces. Everything except the knight is blocked by pieces in the way.';
$L['chess_rules_4_h'] = 'Check and checkmate';
$L['chess_rules_4_t'] = 'Check is a threat to the king. You must answer at once: move the king, block the line or capture the attacker. If you cannot, it is checkmate and the game is over. You may never make a move that leaves your own king in check.';
$L['chess_rules_5_h'] = 'Special moves';
$L['chess_rules_5_t'] = 'Castling: the king moves two squares towards a rook and the rook hops over it - only if neither has moved, the squares between are empty, and the king is not in check nor passes through attacked squares. En passant: a pawn captures an enemy pawn that has just advanced two squares past it. Promotion: a pawn reaching the last rank becomes a queen, rook, bishop or knight of your choice.';
$L['chess_rules_6_h'] = 'Draws';
$L['chess_rules_6_t'] = 'Stalemate (no legal move but no check), insufficient material to mate, threefold repetition, 50 moves with no capture or pawn move, or a draw by agreement (offer it in a game with another player).';
$L['chess_rules_7_h'] = 'Chess clock';
$L['chess_rules_7_t'] = 'Each player has their own time. Your clock runs only on your turn and the increment (if any) is added after your move. The first move of each side is played without the clock. When time runs out you lose - unless the opponent has no material left to mate, then it is a draw.';

$L['chess_new_game'] = 'New game';
$L['chess_lbl_color'] = 'Your colour';
$L['chess_color_random'] = 'Random';
$L['chess_lbl_tc'] = 'Time control (minutes + seconds per move)';
$L['chess_lbl_level'] = 'Computer level';
$L['chess_level_easy'] = 'Easy';
$L['chess_level_normal'] = 'Normal';
$L['chess_level_hard'] = 'Hard';
$L['chess_btn_vs_ai'] = 'Play the computer';
$L['chess_btn_vs_player'] = 'Create a game for a player';
$L['chess_btn_join'] = 'Join';
$L['chess_btn_resume'] = 'Resume';
$L['chess_open_games'] = 'Open games';
$L['chess_no_open'] = 'No open games - create your own.';
$L['chess_my_games'] = 'My active games';
$L['chess_no_my_games'] = 'You have no unfinished games.';
$L['chess_stats_title'] = 'My statistics';
$L['chess_stat_rating'] = 'Rating';
$L['chess_stat_pvp'] = 'Vs players (wins / draws / losses)';
$L['chess_stat_ai'] = 'Vs computer (wins / draws / losses)';
$L['chess_top_title'] = 'Top players';
$L['chess_no_top'] = 'Nobody has played a game yet.';
$L['chess_state_waiting'] = 'waiting for opponent';
$L['chess_state_your_turn'] = 'Your move';
$L['chess_state_enemy_turn'] = 'Opponent\'s move';

$L['chess_my_subtitle'] = 'Your games: continue, resign or review the results.';
$L['chess_my_active'] = 'Current games';
$L['chess_my_history'] = 'Recently finished';
$L['chess_my_no_history'] = 'No finished games yet.';
$L['chess_my_col_mode'] = 'Mode';
$L['chess_my_col_color'] = 'Colour';
$L['chess_my_col_tc'] = 'Clock';
$L['chess_my_col_opp'] = 'Opponent';
$L['chess_my_col_status'] = 'Status';
$L['chess_my_col_updated'] = 'Updated';
$L['chess_my_col_actions'] = 'Actions';
$L['chess_my_col_result'] = 'Result';
$L['chess_my_col_reason'] = 'Reason';
$L['chess_my_mode_ai'] = 'Vs computer';
$L['chess_my_mode_pvp'] = 'Vs player';
$L['chess_my_cancelled'] = 'The game was closed.';
$L['chess_my_res_draw'] = 'Draw';
$L['chess_my_res_none'] = 'No result';

$L['chess_btn_resign'] = 'Resign';
$L['chess_btn_resign_confirm'] = 'Sure? Click again';
$L['chess_btn_cancel_game'] = 'Cancel game';
$L['chess_btn_cancel'] = 'Cancel';
$L['chess_btn_flip'] = 'Flip board';
$L['chess_btn_to_lobby'] = 'To lobby';
$L['chess_btn_draw_offer'] = 'Offer a draw';
$L['chess_btn_draw_accept'] = 'Accept draw';
$L['chess_btn_draw_decline'] = 'Decline';
$L['chess_draw_offered'] = 'Draw offered…';
$L['chess_promo_title'] = 'Promote the pawn to:';
$L['chess_moves_title'] = 'Move list';
$L['chess_no_moves'] = 'No moves yet.';
$L['chess_log_title'] = 'Commentator';
$L['chess_log_me'] = 'You:';
$L['chess_log_opp'] = 'Opponent:';
$L['chess_log_start'] = 'The game has begun! May the best (or luckiest) win 🎲';
$L['chess_banner_waiting'] = 'Waiting for an opponent. The game starts as soon as someone joins.';
$L['chess_banner_check'] = 'You are in check!';
$L['chess_opp_joined'] = '{name} has joined! Let\'s play ♟️';
$L['chess_result_win'] = 'You won!';
$L['chess_result_lose'] = 'You lost';
$L['chess_result_draw'] = 'Draw';
$L['chess_reason_checkmate'] = 'checkmate';
$L['chess_reason_stalemate'] = 'stalemate';
$L['chess_reason_insufficient'] = 'insufficient material';
$L['chess_reason_fifty'] = '50-move rule';
$L['chess_reason_repetition'] = 'threefold repetition';
$L['chess_reason_agreement'] = 'by agreement';
$L['chess_reason_resign'] = 'resignation';
$L['chess_reason_timeout'] = 'time out';
$L['chess_reason_abandon'] = 'game abandoned';
$L['chess_sound_on'] = 'Sound on';
$L['chess_sound_off'] = 'Sound off';

$L['chess_chat_title'] = 'Chat with your opponent';
$L['chess_chat_placeholder'] = 'Message (up to 255 characters)';
$L['chess_chat_send'] = 'Send';

$L['chess_err_auth'] = 'Please sign in.';
$L['chess_err_no_game'] = 'Game not found.';
$L['chess_err_access'] = 'This is not your game.';
$L['chess_err_own_game'] = 'You cannot join your own game.';
$L['chess_err_limit'] = 'Too many active games. Finish or close one first.';
$L['chess_err_coords'] = 'Invalid coordinates.';
$L['chess_err_illegal'] = 'That move is not allowed.';
$L['chess_err_promo'] = 'Choose a piece to promote the pawn to.';
$L['chess_err_not_your_turn'] = 'It is not your turn.';
$L['chess_err_draw'] = 'You cannot offer a draw now.';
$L['chess_err_unknown'] = 'Something went wrong. Please try again.';
$L['chess_err_network'] = 'No connection to the server.';
$L['chess_err_chat_off'] = 'Chat is disabled.';
$L['chess_err_chat_empty'] = 'Empty message.';
$L['chess_err_chat_long'] = 'Message is too long (255 characters max).';
$L['chess_err_chat_closed'] = 'Chat is available only in a running game between players.';
$L['chess_err_chat_wait'] = 'Not so fast! Wait {n} more s.';

$L['chess_admin_title'] = 'Chess - manage games';
$L['chess_admin_help'] = 'Games list, deletion, purge of finished games and the top players table.';
$L['chess_admin_f_all'] = 'All';
$L['chess_admin_st_waiting'] = 'Waiting';
$L['chess_admin_st_active'] = 'Active';
$L['chess_admin_st_finished'] = 'Finished';
$L['chess_admin_deleted'] = 'Game deleted.';
$L['chess_admin_purged'] = 'Finished games deleted';
$L['chess_admin_purge'] = 'Purge finished';
$L['chess_admin_purge_confirm'] = 'Delete all finished games?';
$L['chess_admin_delete'] = 'Delete';
$L['chess_admin_delete_confirm'] = 'Delete this game?';
$L['chess_admin_no_games'] = 'No games.';
$L['chess_admin_top'] = 'Top players';
$L['chess_admin_col_white'] = 'White';
$L['chess_admin_col_black'] = 'Black';
$L['chess_admin_col_tc'] = 'Clock';
$L['chess_admin_col_moves'] = 'Moves';
$L['chess_admin_col_result'] = 'Result';
$L['chess_admin_col_player'] = 'Player';

// Funny phrases
$L['chess_phr_move_my'] = [
	'{piece} goes {from}-{to}. Confident, as if it knows what it is doing 😎',
	'{san}: the move is made, no way back 🚀',
	'{piece} steps to {to}. Your opponent is nervously googling openings 🔍',
	'A quiet move, {san}. The quiet ones are the scariest.',
];
$L['chess_phr_move_opp'] = [
	'Opponent plays {piece} to {to}. Suspiciously calm… 🤨',
	'{san} - and it is your turn to think 🧠',
	'{piece} heads to {to}. What is the plan? 🕵️',
];
$L['chess_phr_cap_my'] = [
	'{piece} eats {victim}! Yum 😋',
	'{victim_nom} leaves the board in shame. {san}! 💥',
	'You take {victim}. Your opponent mutters something rude 🤬',
	'{piece} takes {victim} on {to}. Free lunch! 🍽️',
];
$L['chess_phr_cap_opp'] = [
	'Opponent takes {victim} on {to} - ouch! 😭',
	'Their {piece} grabs {victim} on {to}. That was not lunch, that was dinner… 🍴',
	'Loss: {victim_nom}. Breathe in, breathe out. Revenge! 😤',
	'Oh! {victim_nom} falls on {to}. A minute of silence',
];
$L['chess_phr_capq_my'] = [
	'QUEEN DOWN! {piece} takes the queen on {to}! 👑💥',
	'The queen is toppled! Your opponent is in shock 😱',
];
$L['chess_phr_capq_opp'] = [
	'Your queen fell on {to}… a moment of silence 🕯️',
	'Opponent took your queen! Time for a legendary comeback 💪',
];
$L['chess_phr_castle_my'] = [
	'Castling {san}! The king hides behind the rook - smart 🏰',
	'King goes home, rook stands guard 🏠',
];
$L['chess_phr_castle_opp'] = [
	'Opponent castles ({san}) - the king takes shelter 🏰',
	'Castling! Their king just got much cosier 🛋️',
];
$L['chess_phr_promote_my'] = [
	'Your pawn reached the edge and became a {promoted}! Cinderella at the ball 👸✨',
	'{san}! Rags to riches: now it is a {promoted} 🎉',
];
$L['chess_phr_promote_opp'] = [
	'Opponent promoted a pawn to a {promoted}. A dangerous metamorphosis! 🦋',
	'Whoa, the opponent has a new {promoted}! Hold on tight 😬',
];
$L['chess_phr_ep_my'] = [
	'En passant! Beautiful and a little sneaky',
	'{san}: the pawn caught the runaway. Sleight of hand! 🤹',
];
$L['chess_phr_ep_opp'] = [
	'En passant! Your opponent knows the rare rules 🎓',
	'Your pawn was taken en passant - the rule everybody forgets 😅',
];
$L['chess_phr_check_opp'] = [
	'Check! King, someone is looking for you 🔔',
	'{san} - check! Your opponent has to answer ⚠️',
];
$L['chess_phr_check_me'] = [
	'You are in check! Save the king! 🚨',
	'{san} - check to your king. Careful, it is hot! 🔥',
];
$L['chess_phr_mate_win'] = [
	'{san} - CHECKMATE! The enemy king is pondering the meaning of life 🏆',
	'Checkmate! You are magnificent! 👑🎉',
];
$L['chess_phr_mate_lose'] = [
	'{san} - checkmate. Your king went on vacation (for good) 🏖️',
	'Checkmate… it happens even to champions 🫡',
];
$L['chess_phr_final_mate_win'] = [
	'Victory! The opponent lays the kingdom at your feet 🏆',
	'Checkmate! Today you are a grandmaster 🥇',
];
$L['chess_phr_final_mate_lose'] = [
	'A loss… but there is much to learn. Rematch? 🔁',
	'Checkmate. The king rests; you plan your revenge 😤',
];
$L['chess_phr_final_time_win'] = [
	'Your opponent ran out of time - you win! ⏱️🏆',
	'Flag fell! The clock is on your side today ⏰',
];
$L['chess_phr_final_time_lose'] = [
	'Time is up. The clock ticks mercilessly ⏳😵',
	'Your flag fell - a loss. Think faster next time! ⏰',
];
$L['chess_phr_final_resign_win'] = [
	'The opponent resigned! A white flag over their kingdom 🏳️🏆',
	'Victory - your opponent could not take the pressure 💪',
];
$L['chess_phr_final_resign_lose'] = [
	'You resigned. Sometimes retreat is a strategy too 🏳️',
	'Game over by resignation. Do not be sad, it is only a game 🫶',
];
$L['chess_phr_final_stalemate'] = [
	'Stalemate! No moves, no check - a draw, movie style 🤝',
	'Stalemate - rare beauty and eternal pain 🎭',
];
$L['chess_phr_final_draw'] = [
	'A draw! Equal forces, peace and friendship 🤝',
	'Draw - both sides deserve respect ☮️',
];
$L['chess_phr_low_time'] = [
	'Time is running out! Stop thinking, move! ⏰🔥',
	'Under 30 seconds! The clock is breathing down your neck 😰',
];
$L['chess_phr_nag'] = [
	'Did you fall asleep!? 😴',
	'Are you going to move or have you already given up!? 🏳️',
	'Hey, the board is waiting! The pieces are bored 🥱',
	'It is YOUR move, not your opponent\'s! 📣',
	'Hello? Are we playing today? 🚗',
	'Are you even alive in there? Write something - or better, move 🫥',
];
$L['chess_phr_nag_late'] = [
	'Chess is not a waiting contest! Move already!! 😡',
	'We will grow old here! The pieces demand a move! 👴',
	'Want some help? Move the rook over there, it is fun 🙃',
	'Is the opponent asleep? No, it is you! Wake up! ☕',
];
$L['chess_phr_opp_slow'] = [
	'The opponent is thinking for a long time… probably calculating 20 moves ahead 🤔',
	'The opponent froze. Maybe went for tea? ☕',
];
