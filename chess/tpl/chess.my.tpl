<!-- 
 *  File: chess.my.tpl
 *  Path: plugins/chess/tpl/chess.my.tpl
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

<div class="cx-game container-fluid px-3 py-5" id="cx-app">

	{FILE "{PHP.cfg.themes_dir}/{PHP.cfg.defaulttheme}/warnings.tpl"}

	<div class="mb-3">
		<h1 class="h3 mb-0"><i class="cx-logo" aria-hidden="true">&#9812;</i> {PHP.L.chess_title}</h1>
		<div class="text-body-secondary small">{PHP.L.chess_my_subtitle}</div>
	</div>

	<ul class="nav nav-pills mb-3">
		<li class="nav-item"><a class="nav-link" href="{CX_URL_HOME}">{PHP.L.chess_nav_home}</a></li>
		<li class="nav-item"><a class="nav-link active" href="{CX_URL_MY}">{PHP.L.chess_nav_my}</a></li>
	</ul>

	<!-- IF {CX_LOGGED} -->

		<div class="card mb-3">
			<div class="card-header fw-semibold">{PHP.L.chess_my_active}</div>
			<div class="table-responsive">
				<table class="table table-hover align-middle mb-0">
					<thead>
						<tr>
							<th>#</th>
							<th>{PHP.L.chess_my_col_mode}</th>
							<th>{PHP.L.chess_my_col_color}</th>
							<th>{PHP.L.chess_my_col_tc}</th>
							<th>{PHP.L.chess_my_col_opp}</th>
							<th>{PHP.L.chess_my_col_status}</th>
							<th>{PHP.L.chess_my_col_updated}</th>
							<th class="text-end">{PHP.L.chess_my_col_actions}</th>
						</tr>
					</thead>
					<tbody>
						<!-- BEGIN: ACTIVE_ROW -->
						<tr>
							<td>{ROW_ID}</td>
							<td>{ROW_MODE}</td>
							<td>{ROW_COLOR}</td>
							<td>{ROW_TC}</td>
							<td>{ROW_OPP}</td>
							<td><span class="badge text-bg-{ROW_STATUS_CLASS}">{ROW_STATUS}</span></td>
							<td class="text-body-secondary small">{ROW_UPDATED}</td>
							<td class="text-end text-nowrap">
								<a class="btn btn-sm btn-primary" href="{ROW_PLAY_URL}">{PHP.L.chess_btn_resume}</a>
								<a class="btn btn-sm btn-outline-danger" href="{ROW_CANCEL_URL}" data-confirm="{PHP.L.chess_btn_resign_confirm}">{ROW_CANCEL_LABEL}</a>
							</td>
						</tr>
						<!-- END: ACTIVE_ROW -->
						<!-- BEGIN: ACTIVE_EMPTY -->
						<tr><td colspan="8" class="text-body-secondary">{PHP.L.chess_no_my_games}</td></tr>
						<!-- END: ACTIVE_EMPTY -->
					</tbody>
				</table>
			</div>
		</div>

		<div class="card mb-3">
			<div class="card-header fw-semibold">{PHP.L.chess_my_history}</div>
			<div class="table-responsive">
				<table class="table align-middle mb-0">
					<thead>
						<tr>
							<th>#</th>
							<th>{PHP.L.chess_my_col_mode}</th>
							<th>{PHP.L.chess_my_col_color}</th>
							<th>{PHP.L.chess_my_col_opp}</th>
							<th>{PHP.L.chess_my_col_result}</th>
							<th>{PHP.L.chess_my_col_reason}</th>
							<th>{PHP.L.chess_my_col_updated}</th>
						</tr>
					</thead>
					<tbody>
						<!-- BEGIN: FINISHED_ROW -->
						<tr>
							<td>{FROW_ID}</td>
							<td>{FROW_MODE}</td>
							<td>{FROW_COLOR}</td>
							<td>{FROW_OPP}</td>
							<td><span class="badge text-bg-{FROW_RESULT_CLASS}">{FROW_RESULT}</span></td>
							<td class="text-body-secondary small">{FROW_REASON}</td>
							<td class="text-body-secondary small">{FROW_UPDATED}</td>
						</tr>
						<!-- END: FINISHED_ROW -->
						<!-- BEGIN: FINISHED_EMPTY -->
						<tr><td colspan="8" class="text-body-secondary">{PHP.L.chess_my_no_history}</td></tr>
						<!-- END: FINISHED_EMPTY -->
					</tbody>
				</table>
			</div>
		</div>

		<script>
		document.addEventListener('click', function (e) {
			var a = e.target.closest ? e.target.closest('[data-confirm]') : null;
			if (a && !window.confirm(a.getAttribute('data-confirm'))) { e.preventDefault(); }
		});
		</script>

	<!-- ELSE -->
		<div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap gap-2" role="alert">
			<span>{PHP.L.chess_login_required}</span>
			<a class="btn btn-primary btn-sm" href="{CX_LOGIN_URL}">{PHP.L.chess_btn_login}</a>
		</div>
	<!-- ENDIF -->

</div>
<!-- END: MAIN -->
