<!-- 
 *  File: chess.home.tpl
 *  Path: plugins/chess/tpl/chess.home.tpl
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

<div class="cx-game container-fluid px-3 py-5" id="cx-app" data-cx-config="{CX_CONFIG}">

	{FILE "{PHP.cfg.themes_dir}/{PHP.cfg.defaulttheme}/warnings.tpl"}

	<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
		<div>
			<h1 class="h3 mb-0"><i class="cx-logo" aria-hidden="true">&#9812;</i> {PHP.L.chess_title}</h1>
			<div class="text-body-secondary small">{PHP.L.chess_subtitle}</div>
		</div>
		<div id="cx-header-actions" class="d-flex gap-2"></div>
	</div>

	<ul class="nav nav-pills mb-3">
		<li class="nav-item"><a class="nav-link active" href="{CX_URL_HOME}">{PHP.L.chess_nav_home}</a></li>
		<li class="nav-item"><a class="nav-link" href="{CX_URL_MY}">{PHP.L.chess_nav_my}</a></li>
	</ul>

	<!-- IF {CX_LOGGED} -->
		<div id="cx-root">
			<div class="text-center text-body-secondary py-5">
				<div class="spinner-border spinner-border-sm me-2" role="status"></div>{PHP.L.chess_loading}
			</div>
		</div>
		<div class="toast-container position-fixed bottom-0 end-0 p-3" id="cx-toasts" aria-live="polite"></div>
	<!-- ELSE -->
		<div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap gap-2" role="alert">
			<span>{PHP.L.chess_login_required}</span>
			<a class="btn btn-primary btn-sm" href="{CX_LOGIN_URL}">{PHP.L.chess_btn_login}</a>
		</div>
	<!-- ENDIF -->

</div>
<!-- END: MAIN -->
