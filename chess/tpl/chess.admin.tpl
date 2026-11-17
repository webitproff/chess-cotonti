<!-- 
 *  File: chess.admin.tpl
 *  Path: plugins/chess/tpl/chess.admin.tpl
 *
 *
 *  Web Application (Plugin) for CMF Cotonti v.1.0 compatible with PHP 8.5+, MySQL 8.4+
 *  Description=Chess: vs computer and PvP with clocks, chat and sounds
 *  Version=1.1.1
 *  Date=2026-Nov-17
 *  Author=webitproff
 *  Copyright=webitproff https://github.com/webitproff/chess-cotonti
 *  License: BSD
 *
 -->

<!-- BEGIN: MAIN -->

<div class="block">
	<h2>{ADMIN_TITLE}</h2>

	{FILE "{PHP.cfg.themes_dir}/{PHP.cfg.defaulttheme}/warnings.tpl"}

	<p class="small">{PHP.L.chess_admin_help}</p>

	<ul class="nav nav-pills mb-3" style="list-style:none;display:flex;gap:.5rem;padding:0;flex-wrap:wrap">
		<li><a class="nav-link {FILTER_ALL_CLASS}" href="{FILTER_ALL_URL}">{PHP.L.chess_admin_f_all} ({CNT_ALL})</a></li>
		<li><a class="nav-link {FILTER_WAITING_CLASS}" href="{FILTER_WAITING_URL}">{PHP.L.chess_admin_st_waiting} ({CNT_WAITING})</a></li>
		<li><a class="nav-link {FILTER_ACTIVE_CLASS}" href="{FILTER_ACTIVE_URL}">{PHP.L.chess_admin_st_active} ({CNT_ACTIVE})</a></li>
		<li><a class="nav-link {FILTER_FINISHED_CLASS}" href="{FILTER_FINISHED_URL}">{PHP.L.chess_admin_st_finished} ({CNT_FINISHED})</a></li>
		<li style="margin-left:auto"><a class="btn btn-sm btn-outline-danger" href="{PURGE_URL}" data-confirm="{PHP.L.chess_admin_purge_confirm}">{PHP.L.chess_admin_purge}</a></li>
	</ul>

	<table class="cells table table-sm table-striped">
		<thead>
			<tr>
				<th>#</th>
				<th>{PHP.L.chess_my_col_mode}</th>
				<th>{PHP.L.chess_my_col_status}</th>
				<th>{PHP.L.chess_admin_col_white}</th>
				<th>{PHP.L.chess_admin_col_black}</th>
				<th>{PHP.L.chess_admin_col_tc}</th>
				<th>{PHP.L.chess_admin_col_moves}</th>
				<th>{PHP.L.chess_admin_col_result}</th>
				<th>{PHP.L.chess_my_col_reason}</th>
				<th>{PHP.L.chess_my_col_updated}</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<!-- BEGIN: GAME_ROW -->
			<tr>
				<td>{GAME_ID}</td>
				<td>{GAME_MODE}</td>
				<td>{GAME_STATUS}</td>
				<td>{GAME_WHITE}</td>
				<td>{GAME_BLACK}</td>
				<td>{GAME_TC}</td>
				<td>{GAME_MOVES}</td>
				<td>{GAME_RESULT}</td>
				<td>{GAME_REASON}</td>
				<td>{GAME_UPDATED}</td>
				<td><a class="btn btn-sm btn-outline-danger" href="{GAME_DELETE_URL}" data-confirm="{PHP.L.chess_admin_delete_confirm}">{PHP.L.chess_admin_delete}</a></td>
			</tr>
			<!-- END: GAME_ROW -->
			<!-- BEGIN: EMPTY -->
			<tr><td colspan="11">{PHP.L.chess_admin_no_games}</td></tr>
			<!-- END: EMPTY -->
		</tbody>
	</table>

	<div class="pagenav">{PAGENAV_PREV} {PAGENAV_PAGES} {PAGENAV_NEXT}</div>

	<h3>{PHP.L.chess_admin_top}</h3>
	<table class="cells table table-sm table-striped">
		<thead>
			<tr>
				<th>#</th>
				<th>{PHP.L.chess_admin_col_player}</th>
				<th>{PHP.L.chess_stat_rating}</th>
				<th>{PHP.L.chess_stat_pvp}</th>
				<th>{PHP.L.chess_stat_ai}</th>
			</tr>
		</thead>
		<tbody>
			<!-- BEGIN: TOP_ROW -->
			<tr>
				<td>{TOP_UID}</td>
				<td>{TOP_NAME}</td>
				<td>{TOP_RATING}</td>
				<td>{TOP_PVP}</td>
				<td>{TOP_AI}</td>
			</tr>
			<!-- END: TOP_ROW -->
			<!-- BEGIN: TOP_EMPTY -->
			<tr><td colspan="5">{PHP.L.chess_no_top}</td></tr>
			<!-- END: TOP_EMPTY -->
		</tbody>
	</table>
</div>
<script>
document.addEventListener('click', function (e) {
	var a = e.target.closest ? e.target.closest('[data-confirm]') : null;
	if (a && !window.confirm(a.getAttribute('data-confirm'))) { e.preventDefault(); }
});
</script>
<!-- END: MAIN -->
