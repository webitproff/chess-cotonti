<!-- 
 *  File: chess.play.tpl
 *  Path: plugins/chess/tpl/chess.play.tpl
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
			<h1 class="h3 mb-0"><i class="cx-logo" aria-hidden="true">&#9812;</i> {PHP.L.chess_title} <small class="text-body-secondary">#{CX_GAME_ID}</small></h1>
			<div class="text-body-secondary small">
				<a href="{CX_URL_HOME}">{PHP.L.chess_nav_home}</a> &middot; <a href="{CX_URL_MY}">{PHP.L.chess_nav_my}</a>
			</div>
		</div>
		<div id="cx-header-actions" class="d-flex flex-wrap gap-2"></div>
	</div>

	<!-- IF {CX_LOGGED} -->
		<div id="cx-root">
			<div class="text-center text-body-secondary py-5">
				<div class="spinner-border spinner-border-sm me-2" role="status"></div>{PHP.L.chess_loading}
			</div>
		</div>
		<div class="toast-container position-fixed bottom-0 end-0 p-3" id="cx-toasts" aria-live="polite"></div>

		<!-- Reference: pieces and board markers -->
		<div class="card mt-3" id="cx-help">
			<div class="card-header fw-semibold">{PHP.L.chess_help_title}</div>
			<div class="table-responsive">
				<table class="table table-sm align-middle mb-0">
					<thead>
						<tr>
							<th>{PHP.L.chess_help_col_icon}</th>
							<th>{PHP.L.chess_help_col_name}</th>
							<th>{PHP.L.chess_help_col_move}</th>
							<th>{PHP.L.chess_help_col_role}</th>
							<th class="text-center">{PHP.L.chess_help_col_value}</th>
						</tr>
					</thead>
					<tbody>
					<tr>
						<td class="text-nowrap"><span class="cp-box"><span class="cp t-k is-w"><i class="a"></i><i class="b"></i><i class="c"></i><i class="d"></i><i class="e"></i></span></span><span class="cp-box"><span class="cp t-k is-b"><i class="a"></i><i class="b"></i><i class="c"></i><i class="d"></i><i class="e"></i></span></span></td>
						<td class="fw-semibold">{PHP.L.chess_piece_k}</td>
						<td>{PHP.L.chess_help_k_move}</td>
						<td>{PHP.L.chess_help_k_role}</td>
						<td class="text-nowrap text-center">{PHP.L.chess_help_k_value}</td>
					</tr>
					<tr>
						<td class="text-nowrap"><span class="cp-box"><span class="cp t-q is-w"><i class="a"></i><i class="b"></i><i class="c"></i><i class="d"></i><i class="e"></i></span></span><span class="cp-box"><span class="cp t-q is-b"><i class="a"></i><i class="b"></i><i class="c"></i><i class="d"></i><i class="e"></i></span></span></td>
						<td class="fw-semibold">{PHP.L.chess_piece_q}</td>
						<td>{PHP.L.chess_help_q_move}</td>
						<td>{PHP.L.chess_help_q_role}</td>
						<td class="text-nowrap text-center">{PHP.L.chess_help_q_value}</td>
					</tr>
					<tr>
						<td class="text-nowrap"><span class="cp-box"><span class="cp t-r is-w"><i class="a"></i><i class="b"></i><i class="c"></i><i class="d"></i><i class="e"></i></span></span><span class="cp-box"><span class="cp t-r is-b"><i class="a"></i><i class="b"></i><i class="c"></i><i class="d"></i><i class="e"></i></span></span></td>
						<td class="fw-semibold">{PHP.L.chess_piece_r}</td>
						<td>{PHP.L.chess_help_r_move}</td>
						<td>{PHP.L.chess_help_r_role}</td>
						<td class="text-nowrap text-center">{PHP.L.chess_help_r_value}</td>
					</tr>
					<tr>
						<td class="text-nowrap"><span class="cp-box"><span class="cp t-b is-w"><i class="a"></i><i class="b"></i><i class="c"></i><i class="d"></i><i class="e"></i></span></span><span class="cp-box"><span class="cp t-b is-b"><i class="a"></i><i class="b"></i><i class="c"></i><i class="d"></i><i class="e"></i></span></span></td>
						<td class="fw-semibold">{PHP.L.chess_piece_b}</td>
						<td>{PHP.L.chess_help_b_move}</td>
						<td>{PHP.L.chess_help_b_role}</td>
						<td class="text-nowrap text-center">{PHP.L.chess_help_b_value}</td>
					</tr>
					<tr>
						<td class="text-nowrap"><span class="cp-box"><span class="cp t-n is-w"><i class="a"></i><i class="b"></i><i class="c"></i><i class="d"></i><i class="e"></i></span></span><span class="cp-box"><span class="cp t-n is-b"><i class="a"></i><i class="b"></i><i class="c"></i><i class="d"></i><i class="e"></i></span></span></td>
						<td class="fw-semibold">{PHP.L.chess_piece_n}</td>
						<td>{PHP.L.chess_help_n_move}</td>
						<td>{PHP.L.chess_help_n_role}</td>
						<td class="text-nowrap text-center">{PHP.L.chess_help_n_value}</td>
					</tr>
					<tr>
						<td class="text-nowrap"><span class="cp-box"><span class="cp t-p is-w"><i class="a"></i><i class="b"></i><i class="c"></i><i class="d"></i><i class="e"></i></span></span><span class="cp-box"><span class="cp t-p is-b"><i class="a"></i><i class="b"></i><i class="c"></i><i class="d"></i><i class="e"></i></span></span></td>
						<td class="fw-semibold">{PHP.L.chess_piece_p}</td>
						<td>{PHP.L.chess_help_p_move}</td>
						<td>{PHP.L.chess_help_p_role}</td>
						<td class="text-nowrap text-center">{PHP.L.chess_help_p_value}</td>
					</tr>
					</tbody>
				</table>
			</div>
			<div class="card-body border-top">
				<div class="fw-semibold mb-2">{PHP.L.chess_mk_title}</div>
				<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-2 mb-3">
				<div class="col">
					<div class="d-flex align-items-center gap-2">
						<span class="cx-swatch is-sel"></span>
						<span class="small"><strong>{PHP.L.chess_mk_sel}</strong> &mdash; {PHP.L.chess_mk_sel_d}</span>
					</div>
				</div>
				<div class="col">
					<div class="d-flex align-items-center gap-2">
						<span class="cx-swatch is-move"></span>
						<span class="small"><strong>{PHP.L.chess_mk_move}</strong> &mdash; {PHP.L.chess_mk_move_d}</span>
					</div>
				</div>
				<div class="col">
					<div class="d-flex align-items-center gap-2">
						<span class="cx-swatch is-cap"></span>
						<span class="small"><strong>{PHP.L.chess_mk_cap}</strong> &mdash; {PHP.L.chess_mk_cap_d}</span>
					</div>
				</div>
				<div class="col">
					<div class="d-flex align-items-center gap-2">
						<span class="cx-swatch is-last"></span>
						<span class="small"><strong>{PHP.L.chess_mk_last}</strong> &mdash; {PHP.L.chess_mk_last_d}</span>
					</div>
				</div>
				<div class="col">
					<div class="d-flex align-items-center gap-2">
						<span class="cx-swatch is-check"></span>
						<span class="small"><strong>{PHP.L.chess_mk_check}</strong> &mdash; {PHP.L.chess_mk_check_d}</span>
					</div>
				</div>
				</div>
				<p class="small text-body-secondary mb-3">{PHP.L.chess_coords_hint}</p>
				<button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#cx-rules" id="cx-rules-btn">{PHP.L.chess_rules_btn}</button>
			</div>
		</div>

		<!-- Rules modal -->
		<div class="modal fade" id="cx-rules" tabindex="-1" aria-labelledby="cx-rules-title" aria-hidden="true">
			<div class="modal-dialog modal-lg modal-dialog-scrollable">
				<div class="modal-content">
					<div class="modal-header">
						<h2 class="modal-title h5" id="cx-rules-title">{PHP.L.chess_rules_title}</h2>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
					<h3 class="h6 mt-3">{PHP.L.chess_rules_1_h}</h3>
					<p class="mb-1">{PHP.L.chess_rules_1_t}</p>
					<h3 class="h6 mt-3">{PHP.L.chess_rules_2_h}</h3>
					<p class="mb-1">{PHP.L.chess_rules_2_t}</p>
					<h3 class="h6 mt-3">{PHP.L.chess_rules_3_h}</h3>
					<p class="mb-1">{PHP.L.chess_rules_3_t}</p>
					<h3 class="h6 mt-3">{PHP.L.chess_rules_4_h}</h3>
					<p class="mb-1">{PHP.L.chess_rules_4_t}</p>
					<h3 class="h6 mt-3">{PHP.L.chess_rules_5_h}</h3>
					<p class="mb-1">{PHP.L.chess_rules_5_t}</p>
					<h3 class="h6 mt-3">{PHP.L.chess_rules_6_h}</h3>
					<p class="mb-1">{PHP.L.chess_rules_6_t}</p>
					<h3 class="h6 mt-3">{PHP.L.chess_rules_7_h}</h3>
					<p class="mb-1">{PHP.L.chess_rules_7_t}</p>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-primary" data-bs-dismiss="modal">{PHP.L.chess_rules_close}</button>
					</div>
				</div>
			</div>
		</div>
	<!-- ELSE -->
		<div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap gap-2" role="alert">
			<span>{PHP.L.chess_login_required}</span>
			<a class="btn btn-primary btn-sm" href="{CX_LOGIN_URL}">{PHP.L.chess_btn_login}</a>
		</div>
	<!-- ENDIF -->

</div>
<!-- END: MAIN -->
