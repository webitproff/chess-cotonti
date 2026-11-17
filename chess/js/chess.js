/**
 *  File: chess.js
 *  Path: plugins/chess/js/chess.js
 *
 *  Chess client: lobby, board, clocks, sounds, chat, jokes and the "wake up" car horn.
 *  The server is the only authority on rules: the client sends from/to/promo and draws what it gets back.
 *  Requires Bootstrap 5.3 (toasts and the rules modal degrade gracefully without its JS).
 *
 *  Web Application (Plugin) for CMF Cotonti v.1.0 compatible with PHP 8.5+, MySQL 8.4+
 *  Description=Chess: vs computer and PvP with clocks, chat and sounds
 *  Version=1.1.1
 *  Date=2026-Nov-17
 *  Author=webitproff
 *  Copyright=webitproff https://github.com/webitproff/chess-cotonti
 *  License: BSD
 */
(function () {
	'use strict';

	var app = document.getElementById('cx-app');
	var root = document.getElementById('cx-root');
	if (!app || !root) { return; }

	var cfg;
	try { cfg = JSON.parse(app.getAttribute('data-cx-config')); } catch (e) { return; }

	var L = cfg.lang || {};
	cfg.urls = cfg.urls || {};
	var VALUE = { p: 1, n: 3, b: 3, r: 5, q: 9, k: 0 };
	var ORDER = ['q', 'r', 'b', 'n', 'p'];

	/* ---------- helpers ---------- */

	function t(key, vars) {
		var s = L[key];
		if (s === undefined) { s = key; }
		if (Array.isArray(s)) { s = s[Math.floor(Math.random() * s.length)]; }
		return String(s).replace(/\{(\w+)\}/g, function (m, k) {
			return (vars && vars[k] !== undefined) ? vars[k] : m;
		});
	}

	function el(tag, cls, text) {
		var e = document.createElement(tag);
		if (cls) { e.className = cls; }
		if (text !== undefined && text !== null) { e.textContent = text; }
		return e;
	}

	function btn(cls, text, onClick) {
		var b = el('button', 'btn ' + cls, text);
		b.type = 'button';
		if (onClick) { b.addEventListener('click', onClick); }
		return b;
	}

	function clear(node) { while (node.firstChild) { node.removeChild(node.firstChild); } }
	function playUrl(id) { return String(cfg.urls.play || '').replace('__ID__', id); }
	function go(url) { window.location.href = url; }

	function api(action, data) {
		var f = new FormData();
		f.append('action', action);
		f.append('x', cfg.xk);
		Object.keys(data || {}).forEach(function (k) { f.append(k, data[k]); });
		return fetch(cfg.ajax, { method: 'POST', body: f, credentials: 'same-origin' })
			.then(function (r) { return r.text(); })
			.then(function (txt) {
				try { return JSON.parse(txt); } catch (e) { return { success: false, message: t('err_unknown') }; }
			})
			.catch(function () { return { success: false, message: t('err_network') }; });
	}

	function toast(msg, type) {
		var box = document.getElementById('cx-toasts');
		if (!box) { return; }
		var d = el('div', 'toast align-items-center border-0 text-bg-' + (type || 'secondary'));
		d.setAttribute('role', 'status');
		var f = el('div', 'd-flex');
		f.appendChild(el('div', 'toast-body', msg));
		var b = el('button', 'btn-close btn-close-white me-2 m-auto');
		b.type = 'button';
		b.setAttribute('aria-label', 'Close');
		b.addEventListener('click', function () { d.remove(); });
		f.appendChild(b);
		d.appendChild(f);
		box.appendChild(d);
		while (box.children.length > 4) { box.removeChild(box.firstChild); }
		if (window.bootstrap && window.bootstrap.Toast) {
			d.addEventListener('hidden.bs.toast', function () { d.remove(); });
			new window.bootstrap.Toast(d, { delay: 4200 }).show();
		} else {
			d.classList.add('show');
			setTimeout(function () { d.remove(); }, 4200);
		}
	}

	/** Two-step confirmation button. */
	function confirmBtn(cls, label, confirmLabel, action) {
		var timer = null;
		var b = btn(cls, label);
		b.addEventListener('click', function () {
			if (b.getAttribute('data-armed')) {
				clearTimeout(timer);
				action();
				return;
			}
			b.setAttribute('data-armed', '1');
			b.textContent = confirmLabel;
			timer = setTimeout(function () {
				b.removeAttribute('data-armed');
				b.textContent = label;
			}, 3000);
		});
		return b;
	}

	/* ---------- sounds (Web Audio API, no external files) ---------- */

	var Snd = (function () {
		var ctx = null, on = true;
		try { on = window.localStorage.getItem('cx_sound') !== '0'; } catch (e) { on = true; }

		function ac() {
			if (!ctx) {
				var C = window.AudioContext || window.webkitAudioContext;
				if (!C) { return null; }
				try { ctx = new C(); } catch (e) { return null; }
			}
			if (ctx.state === 'suspended' && ctx.resume) { ctx.resume(); }
			return ctx;
		}

		function tone(freq, start, dur, type, vol, slideTo) {
			var c = ac();
			if (!c) { return; }
			var o = c.createOscillator(), g = c.createGain(), t0 = c.currentTime + start;
			o.type = type || 'sine';
			o.frequency.setValueAtTime(freq, t0);
			if (slideTo) { o.frequency.exponentialRampToValueAtTime(slideTo, t0 + dur); }
			g.gain.setValueAtTime(0.0001, t0);
			g.gain.exponentialRampToValueAtTime(vol || 0.15, t0 + 0.01);
			g.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
			o.connect(g);
			g.connect(c.destination);
			o.start(t0);
			o.stop(t0 + dur + 0.02);
		}

		// short burst of band-passed noise: wooden knock / radio squelch
		function noise(start, dur, vol, freq) {
			var c = ac();
			if (!c) { return; }
			var len = Math.max(1, Math.floor(c.sampleRate * dur));
			var buf = c.createBuffer(1, len, c.sampleRate);
			var data = buf.getChannelData(0);
			for (var i = 0; i < len; i++) { data[i] = (Math.random() * 2 - 1) * (1 - i / len); }
			var src = c.createBufferSource(), f = c.createBiquadFilter(), g = c.createGain(), t0 = c.currentTime + start;
			src.buffer = buf;
			f.type = 'bandpass';
			f.frequency.value = freq || 2200;
			f.Q.value = 0.9;
			g.gain.setValueAtTime(vol || 0.12, t0);
			g.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
			src.connect(f);
			f.connect(g);
			g.connect(c.destination);
			src.start(t0);
		}

		function knock(at, heavy) {
			noise(at, heavy ? 0.08 : 0.045, heavy ? 0.32 : 0.22, heavy ? 650 : 950);
			tone(heavy ? 120 : 175, at, heavy ? 0.13 : 0.08, 'triangle', heavy ? 0.3 : 0.24, heavy ? 65 : 95);
		}

		var sounds = {
			select: function () { tone(1250, 0, 0.03, 'square', 0.04); },
			move: function () { knock(0, false); },
			capture: function () { knock(0, true); knock(0.075, false); },
			castle: function () { knock(0, false); knock(0.16, true); },
			check: function () { tone(880, 0, 0.12, 'sine', 0.18); tone(1175, 0.13, 0.22, 'sine', 0.18); },
			promote: function () { [523, 659, 784, 1047, 1319].forEach(function (f, i) { tone(f, i * 0.07, 0.14, 'triangle', 0.15); }); },
			illegal: function () { tone(110, 0, 0.14, 'sawtooth', 0.1); },
			tick: function () { tone(1000, 0, 0.03, 'square', 0.05); },
			start: function () { tone(660, 0, 0.1, 'triangle', 0.12); tone(990, 0.12, 0.16, 'triangle', 0.12); },
			win: function () {
				[523, 659, 784, 1047].forEach(function (f, i) { tone(f, i * 0.15, 0.22, 'triangle', 0.16); });
				tone(1047, 0.62, 0.5, 'triangle', 0.16);
			},
			lose: function () { [392, 349, 311, 262].forEach(function (f, i) { tone(f, i * 0.22, 0.3, 'triangle', 0.16); }); },
			draw: function () { tone(523, 0, 0.2, 'triangle', 0.14); tone(523, 0.28, 0.3, 'triangle', 0.14); },
			// car horn: two notes together (about 400 and 500 Hz), "beep-beep"
			horn: function () {
				[0, 0.42].forEach(function (at) {
					tone(400, at, 0.3, 'sawtooth', 0.08);
					tone(500, at, 0.3, 'sawtooth', 0.08);
					tone(200, at, 0.3, 'square', 0.03);
				});
			},
			// chat: radio style (Quindar tones)
			send: function () { noise(0, 0.025, 0.14, 3000); tone(2525, 0.03, 0.16, 'sine', 0.13); },
			recv: function () {
				noise(0, 0.11, 0.1, 1800);
				tone(2475, 0.12, 0.1, 'sine', 0.13);
				tone(1320, 0.27, 0.07, 'sine', 0.11);
				tone(1760, 0.36, 0.09, 'sine', 0.11);
			}
		};

		// browsers allow audio only after a user gesture
		document.addEventListener('pointerdown', function () { if (on) { ac(); } }, { passive: true });

		return {
			play: function (name) { if (on && sounds[name]) { try { sounds[name](); } catch (e) { /* ignore */ } } },
			isOn: function () { return on; },
			toggle: function () {
				on = !on;
				try { window.localStorage.setItem('cx_sound', on ? '1' : '0'); } catch (e) { /* ignore */ }
				if (on) { sounds.select(); }
				return on;
			}
		};
	})();

	function soundBtn() {
		var b = btn('btn-outline-secondary btn-sm', '', null);
		function label() { b.textContent = (Snd.isOn() ? '♪ ' + t('sound_on') : '✕ ' + t('sound_off')); }
		label();
		b.addEventListener('click', function () { Snd.toggle(); label(); });
		return b;
	}

	/* ---------- CSS pieces and FEN ---------- */

	/** @param {string} color 'w'|'b'  @param {string} type p n b r q k */
	function piece(color, type, extra) {
		var s = el('span', 'cp t-' + type + ' is-' + color + (extra ? ' ' + extra : ''));
		['a', 'b', 'c', 'd', 'e'].forEach(function (c) { s.appendChild(el('i', c)); });
		return s;
	}

	function parseFen(fen) {
		var grid = [];
		String(fen).split(' ')[0].split('/').forEach(function (row) {
			var out = [];
			row.split('').forEach(function (ch) {
				if (/\d/.test(ch)) { for (var i = 0; i < +ch; i++) { out.push(''); } } else { out.push(ch); }
			});
			grid.push(out);
		});
		return grid;
	}

	function sqName(r, f) { return String.fromCharCode(97 + f) + (8 - r); }

	function fmtClock(ms) {
		ms = Math.max(0, ms);
		var tot = Math.floor(ms / 1000), m = Math.floor(tot / 60), s = tot % 60;
		if (ms < 10000) { return '0:0' + s + '.' + Math.floor((ms % 1000) / 100); }
		return m + ':' + (s < 10 ? '0' : '') + s;
	}

	/* ---------- state ---------- */

	var S = { view: 'lobby', gid: 0, game: null, seen: 0, log: [], busy: false, pollTimer: null,
		sel: '', flip: false, promo: null, host: null, clock: null, flagAsked: false, lowWarned: false,
		fresh: [], form: { color: 'r', tc: '0+0', level: cfg.aiLevel || 'normal' }, sig: '', lastTick: 0 };

	function stopTimers() {
		if (S.pollTimer) { clearTimeout(S.pollTimer); S.pollTimer = null; }
	}

	function setHeaderActions(nodes) {
		var h = document.getElementById('cx-header-actions');
		if (!h) { return; }
		clear(h);
		nodes.forEach(function (n) { h.appendChild(n); });
	}

	/* ---------- moves analysis ---------- */

	function capturedBy(moves, by) {
		var out = [];
		moves.forEach(function (m) { if (m.by === by && m.c) { out.push(m.c); } });
		out.sort(function (a, b) { return ORDER.indexOf(a) - ORDER.indexOf(b); });
		return out;
	}

	function materialDiff(moves) {
		var w = 0, b = 0;
		moves.forEach(function (m) {
			if (m.c) { if (m.by === 'w') { w += VALUE[m.c]; } else { b += VALUE[m.c]; } }
			if (m.pr) { if (m.by === 'w') { w += VALUE[m.pr] - 1; } else { b += VALUE[m.pr] - 1; } }
		});
		return w - b;
	}

	function describeMove(m, g) {
		var mine = m.by === g.side, who = mine ? 'my' : 'opp';
		var v = {
			piece: t('piece_' + m.p), victim: m.c ? t('piece_' + m.c + '_acc') : '', victim_nom: m.c ? t('piece_' + m.c) : '',
			promoted: m.pr ? t('piece_' + m.pr) : '', from: m.f, to: m.t, san: m.s
		};
		var key, kind = 'move', type = null, sound = 'move';
		if (m.ch === 2) { key = mine ? 'phr_mate_win' : 'phr_mate_lose'; kind = 'big'; type = mine ? 'success' : 'danger'; sound = 'check'; }
		else if (m.ch === 1) { key = mine ? 'phr_check_opp' : 'phr_check_me'; kind = 'check'; type = mine ? 'warning' : 'danger'; sound = 'check'; }
		else if (m.k === 'ck' || m.k === 'cq') { key = 'phr_castle_' + who; kind = 'big'; type = 'info'; sound = 'castle'; }
		else if (m.pr) { key = 'phr_promote_' + who; kind = 'big'; type = mine ? 'success' : 'warning'; sound = 'promote'; }
		else if (m.k === 'ep') { key = 'phr_ep_' + who; kind = 'cap'; type = 'info'; sound = 'capture'; }
		else if (m.c === 'q') { key = 'phr_capq_' + who; kind = 'cap'; type = mine ? 'success' : 'danger'; sound = 'capture'; }
		else if (m.c) { key = 'phr_cap_' + who; kind = 'cap'; type = mine ? 'success' : 'warning'; sound = 'capture'; }
		else { key = 'phr_move_' + who; }
		if (m.pr && m.ch) { sound = 'check'; }
		return { text: t(key, v), mine: mine, kind: kind, type: type, sound: sound };
	}

	function addLog(text, kind, mine) {
		S.log.push({ text: text, kind: kind || 'move', mine: mine === undefined ? null : mine });
		if (S.log.length > 300) { S.log.shift(); }
	}

	function sideName(g, c) { return c === g.side ? g.me : g.opp; }

	/* ---------- play view ---------- */

	var D = {}; // dom refs of the play view

	function buildPlayView() {
		clear(root);
		D = {};
		var row = el('div', 'row g-3');
		var colL = el('div', 'col-lg-7');
		var colR = el('div', 'col-lg-5');

		D.banner = el('div', 'alert alert-secondary cx-banner-text py-2 mb-2');
		colL.appendChild(D.banner);
		D.barTop = el('div', 'cx-bar mb-1');
		colL.appendChild(D.barTop);
		D.boardHost = el('div');
		colL.appendChild(D.boardHost);
		D.barBottom = el('div', 'cx-bar mt-1');
		colL.appendChild(D.barBottom);
		D.controls = el('div', 'd-flex flex-wrap gap-2 mt-2');
		colL.appendChild(D.controls);

		var cMoves = el('div', 'card mb-3');
		cMoves.appendChild(el('div', 'card-header', t('moves_title')));
		D.moves = el('div', 'cx-moves card-body p-2');
		cMoves.appendChild(D.moves);
		colR.appendChild(cMoves);

		var cLog = el('div', 'card mb-3');
		cLog.appendChild(el('div', 'card-header', t('log_title')));
		D.log = el('ul', 'cx-log list-group list-group-flush');
		cLog.appendChild(D.log);
		colR.appendChild(cLog);

		D.chatCard = el('div', 'card mb-3');
		D.chatCard.hidden = true;
		D.chatCard.appendChild(el('div', 'card-header', t('chat_title')));
		var cb = el('div', 'card-body p-2');
		D.chatList = el('div', 'cx-chat-list mb-2');
		cb.appendChild(D.chatList);
		var form = el('form', 'input-group input-group-sm');
		D.chatInput = el('input', 'form-control');
		D.chatInput.type = 'text';
		D.chatInput.maxLength = cfg.chatMax || 255;
		D.chatInput.placeholder = t('chat_placeholder');
		D.chatInput.autocomplete = 'off';
		var send = el('button', 'btn btn-primary', t('chat_send'));
		send.type = 'submit';
		form.appendChild(D.chatInput);
		form.appendChild(send);
		form.addEventListener('submit', function (e) { e.preventDefault(); chatSend(); });
		cb.appendChild(form);
		D.chatCard.appendChild(cb);
		colR.appendChild(D.chatCard);

		row.appendChild(colL);
		row.appendChild(colR);
		root.appendChild(row);
		S.chat = { last: 0, started: false, lastSend: 0, timer: null, open: false };
	}

	function playerBar(host, g, color) {
		clear(host);
		var who = el('div', 'who');
		who.appendChild(el('span', 'cx-dot is-' + color));
		var nm = color === g.side ? g.me : (g.is_ai ? t('ai_name') : (g.opp || t('wait_opp')));
		who.appendChild(el('span', 'name', nm));
		host.appendChild(who);

		var taken = el('span', 'taken');
		capturedBy(g.moves, color).forEach(function (p) {
			taken.appendChild(piece(color === 'w' ? 'b' : 'w', p, 'cp-mini'));
		});
		var diff = materialDiff(g.moves) * (color === 'w' ? 1 : -1);
		if (diff > 0) { taken.appendChild(el('span', 'adv', '+' + diff)); }
		host.appendChild(taken);

		var clk = el('span', 'cx-clock');
		clk.setAttribute('data-color', color);
		host.appendChild(clk);
		D['clock_' + color] = clk;
	}

	function renderBoard(g) {
		var grid = parseFen(g.fen);
		var flip = (g.side === 'b') !== S.flip;
		var last = g.moves.length ? g.moves[g.moves.length - 1] : null;
		var wrap = el('div', 'cx-board-wrap');
		var ranks = el('div', 'cx-ranks'), files = el('div', 'cx-files'), board = el('div', 'cx-board');
		for (var i = 0; i < 8; i++) {
			ranks.appendChild(el('span', '', String(flip ? i + 1 : 8 - i)));
			files.appendChild(el('span', '', String.fromCharCode(97 + (flip ? 7 - i : i))));
		}
		var mine = g.status === 'active' && g.turn === g.side;
		var targets = (S.sel && g.legal[S.sel]) ? g.legal[S.sel] : [];
		for (var rr = 0; rr < 8; rr++) {
			for (var ff = 0; ff < 8; ff++) {
				var r = flip ? 7 - rr : rr, f = flip ? 7 - ff : ff;
				var name = sqName(r, f), ch = grid[r][f];
				var cls = 'cx-sq' + (((r + f) % 2) ? ' is-dark' : '');
				if (last && (last.f === name || last.t === name)) { cls += ' is-last'; }
				if (name === S.sel) { cls += ' is-sel'; }
				if (g.check === name) { cls += ' is-check'; }
				if (targets.indexOf(name) >= 0) { cls += ' is-hint' + (ch || (S.selEp === name) ? ' is-cap' : ''); }
				var own = ch && ((ch === ch.toUpperCase()) === (g.side === 'w'));
				if (mine && ((own && g.legal[name]) || targets.indexOf(name) >= 0)) { cls += ' can-move'; }
				if (S.fresh.indexOf(name) >= 0) { cls += ' just-moved'; }
				var sq = el('div', cls);
				sq.setAttribute('data-sq', name);
				if (ch) { sq.appendChild(piece(ch === ch.toUpperCase() ? 'w' : 'b', ch.toLowerCase())); }
				sq.addEventListener('click', onSquare);
				board.appendChild(sq);
			}
		}
		wrap.appendChild(ranks);
		wrap.appendChild(board);
		wrap.appendChild(files);
		clear(D.boardHost);
		D.boardHost.appendChild(wrap);
		D.wrap = wrap;
	}

	function onSquare(e) {
		var g = S.game;
		if (!g || S.busy || S.promo) { return; }
		var name = e.currentTarget.getAttribute('data-sq');
		if (g.status !== 'active' || g.turn !== g.side) { return; }
		var grid = parseFen(g.fen);
		var r = 8 - parseInt(name.charAt(1), 10), f = name.charCodeAt(0) - 97;
		var ch = grid[r][f];
		var own = ch && ((ch === ch.toUpperCase()) === (g.side === 'w'));
		touch();
		if (S.sel && g.legal[S.sel] && g.legal[S.sel].indexOf(name) >= 0) {
			var from = S.sel;
			if (g.promo[from + name]) { askPromotion(from, name); } else { doMove(from, name, ''); }
			return;
		}
		if (own && g.legal[name]) {
			S.sel = (S.sel === name) ? '' : name;
			Snd.play('select');
		} else {
			if (S.sel) { S.sel = ''; } else if (own || ch) { Snd.play('illegal'); }
		}
		renderBoard(g);
	}

	function askPromotion(from, to) {
		var g = S.game;
		S.promo = { from: from, to: to };
		var ov = el('div', 'cx-promo');
		var box = el('div', 'box');
		box.appendChild(el('div', 'fw-semibold', t('promo_title')));
		var opts = el('div', 'opts');
		['q', 'r', 'b', 'n'].forEach(function (p) {
			var b = el('button');
			b.type = 'button';
			b.title = t('piece_' + p);
			b.appendChild(piece(g.side, p));
			b.addEventListener('click', function () { S.promo = null; doMove(from, to, p); });
			opts.appendChild(b);
		});
		box.appendChild(opts);
		var cancel = btn('btn-sm btn-link mt-1', t('btn_cancel'), function () { S.promo = null; S.sel = ''; renderBoard(g); });
		box.appendChild(cancel);
		ov.appendChild(box);
		D.wrap.appendChild(ov);
	}

	function doMove(from, to, promo) {
		S.busy = true;
		S.sel = '';
		api('move', { game_id: S.gid, from: from, to: to, promo: promo }).then(function (d) {
			S.busy = false;
			if (!d.success) {
				Snd.play('illegal');
				toast(d.message || t('err_illegal'), 'danger');
				refresh(true);
				return;
			}
			applyState(d);
		});
	}

	/* ---------- applying server state ---------- */

	function applyState(d) {
		if (!d || d.success === false) {
			if (d && d.message) { toast(d.message, 'danger'); }
			return;
		}
		if (d.unchanged) {
			if (d.clock && S.game) { S.game.clock = d.clock; setClock(S.game); }
			return;
		}
		var prev = S.game;
		var firstLoad = !prev;
		var oldCount = prev ? prev.moves.length : 0;
		var wasActive = prev ? prev.status : '';
		S.game = d;
		S.rev = d.rev;
		S.fresh = [];

		if (firstLoad) {
			S.seen = d.moves.length;
			S.log = [];
			addLog(t('log_start'), 'big', null);
		} else {
			for (var i = S.seen; i < d.moves.length; i++) {
				var m = d.moves[i];
				var info = describeMove(m, d);
				addLog(info.text, info.kind, info.mine);
				S.fresh.push(m.t);
				if (i === d.moves.length - 1 || !info.mine) { Snd.play(info.sound); }
				if (info.type && d.status === 'active') { toast(info.text, info.type); }
			}
			S.seen = d.moves.length;
		}
		if (d.moves.length > oldCount) { touch(); S.nagCount = 0; }

		if (!firstLoad && d.status === 'finished' && wasActive !== 'finished') { onFinished(d); }
		if (!firstLoad && wasActive === 'waiting' && d.status === 'active') {
			Snd.play('start');
			toast(t('opp_joined', { name: d.opp }), 'success');
		}
		if (d.status === 'finished') { S.sel = ''; S.promo = null; }
		S.flagAsked = false;
		renderPlay();
	}

	function onFinished(g) {
		var res = g.result, win = (res === '1-0' && g.side === 'w') || (res === '0-1' && g.side === 'b');
		var draw = res === '1/2-1/2';
		Snd.play(draw ? 'draw' : (win ? 'win' : 'lose'));
		var key = g.reason === 'checkmate' ? (win ? 'phr_final_mate_win' : 'phr_final_mate_lose')
			: g.reason === 'timeout' ? (win ? 'phr_final_time_win' : 'phr_final_time_lose')
			: g.reason === 'resign' ? (win ? 'phr_final_resign_win' : 'phr_final_resign_lose')
			: g.reason === 'stalemate' ? 'phr_final_stalemate'
			: 'phr_final_draw';
		var txt = t(key);
		addLog(txt, 'big', null);
		toast(txt, draw ? 'secondary' : (win ? 'success' : 'danger'));
	}

	function renderPlay() {
		var g = S.game;
		if (!g) { return; }
		var top = g.side === 'w' ? 'b' : 'w', bottom = g.side;
		if (S.flip) { var tmp = top; top = bottom; bottom = tmp; }
		playerBar(D.barTop, g, top);
		playerBar(D.barBottom, g, bottom);
		renderBoard(g);
		renderBanner(g);
		renderMoves(g);
		renderLog();
		renderControls(g);
		setClock(g);
		chatSetup(g);
		var flagText = '';
		if (g.status === 'active' && g.turn === g.side) { flagText = '1'; }
		S.myTurn = !!flagText;
	}

	function renderBanner(g) {
		var cls = 'alert py-2 mb-2 cx-banner-text ', txt;
		if (g.status === 'waiting') { cls += 'alert-warning'; txt = t('banner_waiting'); }
		else if (g.status === 'finished') {
			var res = g.result, win = (res === '1-0' && g.side === 'w') || (res === '0-1' && g.side === 'b');
			cls += res === '1/2-1/2' ? 'alert-secondary' : (win ? 'alert-success' : 'alert-danger');
			txt = (res === '1/2-1/2' ? t('result_draw') : (win ? t('result_win') : t('result_lose'))) + ' - ' + (t('reason_' + g.reason) || '');
		} else if (g.turn === g.side) {
			cls += g.check ? 'alert-danger' : 'alert-primary';
			txt = (g.check ? t('banner_check') + ' ' : '') + t('state_your_turn');
		} else { cls += 'alert-secondary'; txt = t('state_enemy_turn'); }
		D.banner.className = cls;
		D.banner.textContent = txt;
	}

	function renderMoves(g) {
		clear(D.moves);
		if (!g.moves.length) { D.moves.appendChild(el('span', 'text-body-secondary small', t('no_moves'))); return; }
		var tb = el('table', 'table table-sm mb-0'), body = el('tbody');
		for (var i = 0; i < g.moves.length; i += 2) {
			var tr = el('tr');
			tr.appendChild(el('td', 'n', String(i / 2 + 1) + '.'));
			[0, 1].forEach(function (k) {
				var m = g.moves[i + k];
				tr.appendChild(el('td', 'm' + (m && i + k === g.moves.length - 1 ? ' is-lastmove' : ''), m ? m.s : ''));
			});
			body.appendChild(tr);
		}
		tb.appendChild(body);
		D.moves.appendChild(tb);
		D.moves.scrollTop = D.moves.scrollHeight;
	}

	function renderLog() {
		clear(D.log);
		S.log.slice(-8).reverse().forEach(function (e) {
			var li = el('li', 'list-group-item k-' + e.kind + (e.mine === true ? ' is-mine' : (e.mine === false ? ' is-theirs' : '')));
			if (e.mine !== null) { li.appendChild(el('span', 'who', e.mine ? t('log_me') : t('log_opp'))); }
			li.appendChild(document.createTextNode(' ' + e.text));
			D.log.appendChild(li);
		});
	}

	function renderControls(g) {
		clear(D.controls);
		D.controls.appendChild(btn('btn-outline-secondary btn-sm', t('btn_flip'), function () { S.flip = !S.flip; renderPlay(); }));
		D.controls.appendChild(soundBtn());
		if (g.status === 'active') {
			if (!g.is_ai) {
				if (g.draw_offer === g.side) {
					D.controls.appendChild(el('span', 'btn btn-sm disabled btn-outline-secondary', t('draw_offered')));
				} else if (g.draw_offer) {
					D.controls.appendChild(btn('btn-success btn-sm', t('btn_draw_accept'), function () { drawAct('accept'); }));
					D.controls.appendChild(btn('btn-outline-danger btn-sm', t('btn_draw_decline'), function () { drawAct('decline'); }));
				} else {
					D.controls.appendChild(confirmBtn('btn-outline-primary btn-sm', t('btn_draw_offer'), t('btn_resign_confirm'), function () { drawAct('offer'); }));
				}
			}
			D.controls.appendChild(confirmBtn('btn-outline-danger btn-sm', t('btn_resign'), t('btn_resign_confirm'), function () {
				api('resign', { game_id: S.gid }).then(function (d) { applyState(d.success && d.fen ? d : null); refresh(true); });
			}));
		} else if (g.status === 'waiting') {
			D.controls.appendChild(confirmBtn('btn-outline-danger btn-sm', t('btn_cancel_game'), t('btn_resign_confirm'), function () {
				api('resign', { game_id: S.gid }).then(function () { go(cfg.urls.home); });
			}));
		}
		D.controls.appendChild(el('a', 'btn btn-outline-secondary btn-sm ms-auto', t('btn_to_lobby'))).setAttribute('href', cfg.urls.home);
	}

	function drawAct(what) {
		api('draw', { game_id: S.gid, 'do': what }).then(function (d) {
			if (!d.success) { toast(d.message || t('err_draw'), 'danger'); }
			refresh(true);
		});
	}

	/* ---------- clocks ---------- */

	function setClock(g) {
		var c = g.clock;
		S.clock = null;
		['w', 'b'].forEach(function (col) {
			var node = D['clock_' + col];
			if (!node) { return; }
			if (!c || !c.base) { node.textContent = '∞'; node.className = 'cx-clock'; return; }
		});
		if (!c || !c.base) { return; }
		S.clock = { w: c.w, b: c.b, run: c.run, at: Date.now() };
		paintClocks();
	}

	function paintClocks() {
		var k = S.clock, g = S.game;
		if (!k || !g) { return; }
		var now = Date.now(), el_ = now - k.at;
		['w', 'b'].forEach(function (col) {
			var node = D['clock_' + col];
			if (!node) { return; }
			var ms = k[col] - (k.run === col ? el_ : 0);
			node.textContent = fmtClock(ms);
			node.className = 'cx-clock' + (k.run === col ? ' is-run' : '') + (ms < 20000 && g.status === 'active' ? ' is-low' : '');
			if (col === g.side && k.run === col) {
				if (ms < 20000 && ms > 0 && now - S.lastTick > 1000 && Math.floor(ms / 1000) < 10) { S.lastTick = now; Snd.play('tick'); }
				if (ms <= 0 && !S.flagAsked) { S.flagAsked = true; setTimeout(function () { refresh(true); }, 300); }
			}
			if (col !== g.side && k.run === col && ms <= 0 && !S.flagAsked) { S.flagAsked = true; setTimeout(function () { refresh(true); }, 600); }
		});
	}

	/* ---------- chat ---------- */

	function chatSetup(g) {
		var ch = S.chat;
		var on = !!(g.chat && g.status === 'active');
		D.chatCard.hidden = !on;
		if (on && !ch.open) {
			ch.open = true;
			chatPoll();
		}
		if (!on && ch.open) {
			ch.open = false;
			clearTimeout(ch.timer);
			clear(D.chatList);
			ch.last = 0;
			ch.started = false;
		}
	}

	function chatAdd(items, initial) {
		var stick = D.chatList.scrollTop + D.chatList.clientHeight >= D.chatList.scrollHeight - 30;
		var incoming = false;
		items.forEach(function (m) {
			if (m.id <= S.chat.last) { return; }
			S.chat.last = m.id;
			var d = el('div', 'cx-msg ' + (m.mine ? 'is-mine' : 'is-theirs'));
			var b = el('div', 'bubble', m.text);
			var s = el('small', 'text-body-secondary', new Date(m.ts * 1000).toTimeString().slice(0, 5));
			d.appendChild(b);
			d.appendChild(s);
			D.chatList.appendChild(d);
			if (!m.mine) { incoming = true; }
		});
		if (incoming && S.chat.started && !initial) { Snd.play('recv'); }
		if (stick) { D.chatList.scrollTop = D.chatList.scrollHeight; }
	}

	function chatPoll() {
		var ch = S.chat;
		if (!ch.open || S.view !== 'play') { return; }
		api('chatget', { game_id: S.gid, after: ch.last }).then(function (d) {
			if (!ch.open) { return; }
			if (d.success && !d.closed) {
				chatAdd(d.items || [], !ch.started);
				ch.started = true;
			}
			ch.timer = setTimeout(chatPoll, document.hidden ? 8000 : 2500);
		});
	}

	function chatSend() {
		var ch = S.chat, text = D.chatInput.value.trim();
		if (!text) { return; }
		var wait = (cfg.chatCooldown || 5) * 1000 - (Date.now() - ch.lastSend);
		if (wait > 0) { toast(t('err_chat_wait', { n: Math.ceil(wait / 1000) }), 'warning'); return; }
		ch.lastSend = Date.now();
		D.chatInput.value = '';
		api('chatsend', { game_id: S.gid, msg: text }).then(function (d) {
			if (!d.success) {
				toast(d.message || t('err_unknown'), 'warning');
				D.chatInput.value = text;
				ch.lastSend = 0;
				return;
			}
			Snd.play('send');
			clearTimeout(ch.timer);
			chatPoll();
		});
		D.chatInput.focus();
	}

	/* ---------- polling ---------- */

	function refresh(force) {
		if (S.view !== 'play') { return Promise.resolve(); }
		return api('state', { game_id: S.gid, rev: force ? 0 : (S.rev || 0) }).then(function (d) {
			if (!d.success) {
				if (!S.game) { root.textContent = d.message || t('err_unknown'); }
				return;
			}
			applyState(d);
		});
	}

	function schedulePoll() {
		stopTimers();
		(function loop() {
			var g = S.game, fast = g && (g.status === 'waiting' || (g.status === 'active' && g.turn !== g.side));
			var delay = document.hidden ? 6000 : (fast ? 1500 : 3500);
			if (g && g.status === 'finished') { delay = 15000; }
			S.pollTimer = setTimeout(function () {
				if (S.view !== 'play') { return; }
				refresh(false).then(loop, loop);
			}, delay);
		})();
	}

	/* ---------- "wake up" nag: car horn + jokes ---------- */

	function touch() { S.idleSince = Date.now(); }

	function nagTick() {
		var g = S.game;
		if (S.view !== 'play' || !g || g.status !== 'active') { return; }
		paintClocks();
		if (S.promo || S.busy) { return; }
		var now = Date.now();
		if (g.turn === g.side) {
			if (!S.idleEnabledLocal) { return; }
			if (!S.idleSince) { S.idleSince = now; }
			var idle = (now - S.idleSince) / 1000;
			var need = (cfg.idleSeconds || 5) + (S.nagCount || 0) * 15;
			if (idle >= need && (S.nagCount || 0) < 4) {
				S.nagCount = (S.nagCount || 0) + 1;
				Snd.play('horn');
				var msg = t('phr_nag' + (S.nagCount >= 3 ? '_late' : ''));
				addLog(msg, 'nag', true);
				toast(msg, 'warning');
				renderLog();
			}
		} else {
			S.idleSince = 0;
			if (!g.is_ai && g.moves.length && !S.oppNag && S.clock === null) { /* untimed PvP: gentle hint */
				S.oppSince = S.oppSince || now;
				if (now - S.oppSince > 90000) { S.oppNag = true; toast(t('phr_opp_slow'), 'info'); }
			}
		}
		// low time on my clock
		var k = S.clock;
		if (k && k.run === g.side && !S.lowWarned) {
			var ms = k[g.side] - (now - k.at);
			if (ms < 30000 && ms > 0) { S.lowWarned = true; toast(t('phr_low_time'), 'danger'); }
		}
	}

	/* ---------- lobby ---------- */

	function field(label, node) {
		var d = el('div', 'mb-2');
		d.appendChild(el('label', 'form-label small mb-1', label));
		d.appendChild(node);
		return d;
	}

	function select(opts, value, onChange) {
		var s = el('select', 'form-select form-select-sm');
		opts.forEach(function (o) {
			var op = el('option', '', o[1]);
			op.value = o[0];
			if (o[0] === value) { op.selected = true; }
			s.appendChild(op);
		});
		s.addEventListener('change', function () { onChange(s.value); });
		return s;
	}

	function tcLabel(code) { return code === '0+0' ? t('tc_none') : code; }

	function createGame(mode) {
		S.busy = true;
		api('create', { mode: mode, color: S.form.color, tc: S.form.tc, level: S.form.level }).then(function (d) {
			S.busy = false;
			if (!d.success) { toast(d.message || t('err_unknown'), 'danger'); return; }
			go(playUrl(d.game_id));
		});
	}

	function lobbyList(title, rows, emptyText, makeRow) {
		var card = el('div', 'card mb-3');
		card.appendChild(el('div', 'card-header', title));
		var lg = el('ul', 'list-group list-group-flush');
		if (!rows.length) { lg.appendChild(el('li', 'list-group-item text-body-secondary small', emptyText)); }
		rows.forEach(function (r) { lg.appendChild(makeRow(r)); });
		card.appendChild(lg);
		return card;
	}

	function renderLobby(d) {
		clear(root);
		var row = el('div', 'row g-3');
		var colA = el('div', 'col-lg-5'), colB = el('div', 'col-lg-7');

		var cf = el('div', 'card mb-3');
		cf.appendChild(el('div', 'card-header', t('new_game')));
		var cb = el('div', 'card-body');
		cb.appendChild(field(t('lbl_color'), select([['r', t('color_random')], ['w', t('white')], ['b', t('black')]], S.form.color, function (v) { S.form.color = v; })));
		cb.appendChild(field(t('lbl_tc'), select((cfg.timeControls || ['0+0']).map(function (c) { return [c, tcLabel(c)]; }), S.form.tc, function (v) { S.form.tc = v; })));
		cb.appendChild(field(t('lbl_level'), select([['easy', t('level_easy')], ['normal', t('level_normal')], ['hard', t('level_hard')]], S.form.level, function (v) { S.form.level = v; })));
		var bs = el('div', 'd-grid gap-2');
		bs.appendChild(btn('btn-primary', t('btn_vs_ai'), function () { if (!S.busy) { createGame('ai'); } }));
		bs.appendChild(btn('btn-outline-primary', t('btn_vs_player'), function () { if (!S.busy) { createGame('pvp'); } }));
		cb.appendChild(bs);
		cf.appendChild(cb);
		colA.appendChild(cf);

		var st = d.stats || {};
		var cs = el('div', 'card mb-3');
		cs.appendChild(el('div', 'card-header', t('stats_title')));
		var sb = el('div', 'card-body small');
		sb.appendChild(el('div', '', t('stat_rating') + ': ' + (st.rating !== undefined ? st.rating : 1000)));
		sb.appendChild(el('div', '', t('stat_pvp') + ': ' + (st.wins || 0) + ' / ' + (st.draws || 0) + ' / ' + (st.losses || 0)));
		sb.appendChild(el('div', '', t('stat_ai') + ': ' + (st.ai_wins || 0) + ' / ' + (st.ai_draws || 0) + ' / ' + (st.ai_losses || 0)));
		cs.appendChild(sb);
		colA.appendChild(cs);

		colB.appendChild(lobbyList(t('open_games'), d.open || [], t('no_open'), function (r) {
			var li = el('li', 'list-group-item d-flex align-items-center gap-2');
			li.appendChild(piece(r.you_play, 'k', 'cp-mini'));
			li.appendChild(el('span', 'flex-grow-1', r.user_name + ' · ' + tcLabel(r.tc) + ' · ' + (r.you_play === 'w' ? t('white') : t('black'))));
			if (r.you_play && r.mine) {
				li.appendChild(el('a', 'btn btn-sm btn-outline-secondary', t('btn_resume'))).setAttribute('href', playUrl(r.game_id));
			} else {
				li.appendChild(btn('btn-sm btn-success', t('btn_join'), function () {
					api('join', { game_id: r.game_id }).then(function (j) {
						if (!j.success) { toast(j.message || t('err_unknown'), 'danger'); loadLobby(); return; }
						go(playUrl(r.game_id));
					});
				}));
			}
			return li;
		}));

		colB.appendChild(lobbyList(t('my_games'), d.mine || [], t('no_my_games'), function (r) {
			var li = el('li', 'list-group-item d-flex align-items-center gap-2');
			li.appendChild(piece(r.color, 'k', 'cp-mini'));
			var st2 = r.status === 'waiting' ? t('state_waiting') : (r.my_turn ? t('state_your_turn') : t('state_enemy_turn'));
			li.appendChild(el('span', 'flex-grow-1', '#' + r.game_id + ' · ' + (r.opp || t('wait_opp')) + ' · ' + tcLabel(r.tc) + ' · ' + st2));
			li.appendChild(el('a', 'btn btn-sm ' + (r.my_turn ? 'btn-success' : 'btn-outline-secondary'), t('btn_resume'))).setAttribute('href', playUrl(r.game_id));
			return li;
		}));

		colB.appendChild(lobbyList(t('top_title'), d.top || [], t('no_top'), function (r, i) {
			var li = el('li', 'list-group-item d-flex justify-content-between');
			li.appendChild(el('span', '', r.user_name));
			li.appendChild(el('strong', '', String(r.rating)));
			return li;
		}));

		row.appendChild(colA);
		row.appendChild(colB);
		root.appendChild(row);
	}

	function loadLobby() {
		api('lobby', {}).then(function (d) {
			if (!d.success) { root.textContent = d.message || t('err_unknown'); return; }
			S.lobbySig = JSON.stringify([d.open, d.mine]);
			renderLobby(d);
		});
	}

	function lobbyLoop() {
		S.pollTimer = setTimeout(function () {
			if (S.view !== 'lobby') { return; }
			if (document.hidden || S.busy) { lobbyLoop(); return; }
			api('lobby', {}).then(function (d) {
				if (d.success && JSON.stringify([d.open, d.mine]) !== S.lobbySig) { S.lobbySig = JSON.stringify([d.open, d.mine]); renderLobby(d); }
				lobbyLoop();
			});
		}, 5000);
	}

	/* ---------- init ---------- */

	function init() {
		if (cfg.page === 'play') {
			S.view = 'play';
			S.gid = parseInt(cfg.gameId, 10) || 0;
			S.idleEnabledLocal = !!cfg.idleEnabled;
			S.nagCount = 0;
			S.idleSince = Date.now();
			setHeaderActions([]);
			buildPlayView();
			['mousemove', 'keydown', 'touchstart'].forEach(function () { /* the nag is about moves, not mouse activity */ });
			refresh(true).then(function () { schedulePoll(); });
			setInterval(nagTick, 250);
			document.addEventListener('visibilitychange', function () { if (!document.hidden) { refresh(false); } });
			// Bootstrap modal fallback if bootstrap.js is missing
			var rb = document.getElementById('cx-rules-btn'), rm = document.getElementById('cx-rules');
			if (rb && rm && !window.bootstrap) {
				var hide = function () { rm.classList.remove('show'); rm.style.display = 'none'; };
				rb.addEventListener('click', function () { rm.classList.add('show'); rm.style.display = 'block'; });
				Array.prototype.forEach.call(rm.querySelectorAll('[data-bs-dismiss]'), function (n) { n.addEventListener('click', hide); });
			}
		} else {
			S.view = 'lobby';
			setHeaderActions([soundBtn()]);
			loadLobby();
			lobbyLoop();
		}
	}

	init();
})();
