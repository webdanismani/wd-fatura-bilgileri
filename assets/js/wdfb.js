/* WD Fatura Bilgileri */
(function ($) {
	'use strict';

	var C = window.WDFB_CFG;
	if (!C) { return; }
	['unvanReq', 'vdReq', 'vknReq', 'isAccount'].forEach(function (k) { C[k] = Number(C[k]) === 1; });
	C.threshold = Number(C.threshold) || 0;

	var FOREIGN = '11111111111';
	var FOLD = { 'ç': 'c', 'ğ': 'g', 'ı': 'i', 'ö': 'o', 'ş': 's', 'ü': 'u', 'â': 'a', 'î': 'i', 'û': 'u' };
	var fold = function (s) { return String(s || '').toLocaleLowerCase('tr').replace(/[çğıöşüâîû]/g, function (c) { return FOLD[c]; }); };
	var trTitle = function (s) {
		return String(s).trim().replace(/\s+/g, ' ').split(' ').map(function (w) {
			var l = w.toLocaleLowerCase('tr');
			return l.charAt(0).toLocaleUpperCase('tr') + l.slice(1);
		}).join(' ');
	};
	var isMobile = function () { return window.matchMedia('(max-width: 640px)').matches; };
	var h = function (tag, cls, text) {
		var el = document.createElement(tag);
		if (cls) { el.className = cls; }
		if (text != null) { el.textContent = text; }
		return el;
	};
	var svg = function (inner, cls) {
		var t = document.createElement('template');
		t.innerHTML = '<svg viewBox="0 0 20 20" aria-hidden="true"' + (cls ? ' class="' + cls + '"' : '') + '>' + inner + '</svg>';
		return t.content.firstChild;
	};
	var ICON = {
		check: '<path d="m5 10.5 3.2 3L15 6.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
		pen: '<path d="M12.8 4.2 15.8 7.2M4 16l.9-3.6L13.4 4a1.4 1.4 0 0 1 2 0l.6.6a1.4 1.4 0 0 1 0 2L7.6 15.1 4 16Z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>',
		search: '<circle cx="9" cy="9" r="5.5" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="m13.2 13.2 3.3 3.3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
		close: '<path d="m5.5 5.5 9 9m0-9-9 9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>'
	};

	/* ----- doğrulama ----- */

	var isTckn = function (v) {
		if (!/^[1-9]\d{10}$/.test(v)) { return false; }
		var d = v.split('').map(Number);
		var d10 = (((d[0] + d[2] + d[4] + d[6] + d[8]) * 7 - (d[1] + d[3] + d[5] + d[7])) % 10 + 10) % 10;
		if (d10 !== d[9]) { return false; }
		var sum = 0;
		for (var i = 0; i < 10; i++) { sum += d[i]; }
		return sum % 10 === d[10];
	};
	var isVkn = function (v) {
		if (!/^\d{10}$/.test(v)) { return false; }
		var d = v.split('').map(Number), sum = 0;
		for (var i = 0; i < 9; i++) {
			var tmp = (d[i] + (9 - i)) % 10;
			var val = (tmp * Math.pow(2, 9 - i)) % 9;
			if (tmp !== 0 && val === 0) { val = 9; }
			sum += val;
		}
		return (10 - (sum % 10)) % 10 === d[9];
	};
	var mask = function (v) { return v.length < 6 ? v : v.slice(0, 3) + '•'.repeat(v.length - 5) + v.slice(-2); };

	/* ----- vergi daireleri ----- */

	var collator = new Intl.Collator('tr', { sensitivity: 'base' });
	var ALL = [];
	Object.keys(C.vd).forEach(function (il) {
		var ilAd = C.iller[il] || '';
		C.vd[il].forEach(function (r) {
			ALL.push({ kod: r[0], name: r[1], il: Number(il), ilAd: ilAd, f: fold(r[1]), fi: fold(r[1] + ' ' + ilAd) });
		});
	});
	ALL.sort(function (a, b) { return collator.compare(a.ilAd, b.ilAd) || collator.compare(a.name, b.name); });

	/* ------------------------------------------------------------------ */

	function Panel(root) {
		var self = this;
		this.root = root;
		this.row = root.closest('.wdfb-row');
		this.form = root.closest('form');
		this.isCheckout = !!(this.form && this.form.classList.contains('checkout'));
		this.q = function (r) { return root.querySelector('[data-role="' + r + '"]'); };

		this.tc = this.q('tc');
		this.foreign = this.q('foreign');
		this.unvan = this.q('unvan');
		this.vkn = this.q('vkn');
		this.vd = this.q('vd');
		this.vdKod = this.q('vd_kod');
		this.vdBox = this.q('vd-box');
		this.vdInput = this.vdBox ? this.vdBox.querySelector('.wdfb-cb__input') : null;
		this.efatura = this.q('efatura');
		this.summary = this.q('summary');

		this.theme();
		this.bindTip();
		this.bindTc();
		this.bindVkn();
		this.bindVd();
		this.bindCountry();
		this.bindValidation();

		['billing_first_name', 'billing_last_name'].forEach(function (id) {
			var el = document.getElementById(id);
			if (el) { el.addEventListener('input', function () { self.render(); }); }
		});
		if (this.unvan) { this.unvan.addEventListener('input', function () { self.clearErr(self.unvan); self.render(); }); }
		if (this.efatura) { this.efatura.addEventListener('change', function () { self.render(); }); }

		$(document.body).on('updated_checkout', function () { self.updateTcRequirement(); });
		this.updateTcRequirement();
		this.checkTc(false);
		this.checkVkn(false);
		this.render();
	}

	Panel.prototype.theme = function () {
		var pref = this.root.getAttribute('data-theme-pref') || 'auto', theme = pref;
		if (pref === 'auto') {
			var el = this.root.parentElement, rgb = null;
			while (el && el !== document.documentElement) {
				var m = getComputedStyle(el).backgroundColor.match(/rgba?\(([\d.]+),\s*([\d.]+),\s*([\d.]+)(?:,\s*([\d.]+))?/);
				if (m && (m[4] === undefined || Number(m[4]) > 0.5)) { rgb = m; break; }
				el = el.parentElement;
			}
			var lum = rgb ? (0.2126 * rgb[1] + 0.7152 * rgb[2] + 0.0722 * rgb[3]) / 255 : 1;
			theme = lum < 0.45 ? 'dark' : 'light';
		}
		this.root.setAttribute('data-theme', theme);
	};

	Panel.prototype.tip = function () { return this.root.getAttribute('data-tip'); };
	Panel.prototype.field = function (el) { return el ? el.closest('.wdfb-f') : null; };

	Panel.prototype.setState = function (el, state, msg) {
		var f = this.field(el);
		if (!f) { return; }
		f.classList.toggle('is-valid', state === 'valid');
		f.classList.toggle('is-bad', state === 'bad');
		f.classList.toggle('is-invalid', state === 'bad' && !!msg);
		var hint = f.querySelector('.wdfb-f__hint');
		if (hint) {
			if (hint.__base === undefined) { hint.__base = hint.textContent.trim(); }
			hint.textContent = msg || hint.__base;
		}
	};
	Panel.prototype.clearErr = function (el) {
		var f = this.field(el);
		if (f) { f.classList.remove('is-invalid'); }
	};

	/* ----- tür ----- */

	Panel.prototype.bindTip = function () {
		var self = this;
		var panes = this.root.querySelectorAll('.wdfb__pane');
		var apply = function (instant) {
			var t = self.tip();
			Array.prototype.forEach.call(panes, function (p) {
				var on = p.getAttribute('data-pane') === t;
				clearTimeout(p.__t);
				if (!on) { p.classList.remove('is-open'); return; }
				if (instant) { p.classList.add('is-open'); } else { p.__t = setTimeout(function () { p.classList.add('is-open'); }, 330); }
			});
		};
		this.root.querySelectorAll('input[name="billing_wdfb_tip"]').forEach(function (r) {
			r.addEventListener('change', function () {
				if (!r.checked) { return; }
				self.root.setAttribute('data-tip', r.value);
				self.closeVd();
				apply(false);
				self.render();
			});
		});
		apply(true);
	};

	/* ----- TC ----- */

	Panel.prototype.tcRequired = function () {
		if (C.tcMode === 'required') { return true; }
		if (C.tcMode !== 'threshold') { return false; }
		if (C.isAccount) { return false; }
		var holder = this.root.querySelector('.wdfb-total-holder') || document.querySelector('.wdfb-total-holder');
		var total = holder ? Number(holder.getAttribute('data-total')) : 0;
		return total >= C.threshold;
	};

	Panel.prototype.updateTcRequirement = function () {
		var mark = this.q('tc-mark');
		if (!mark) { return; }
		var req = this.tcRequired();
		mark.innerHTML = req ? '<abbr class="wdfb-req" title="zorunlu">*</abbr>' : '<span class="wdfb-opt">isteğe bağlı</span>';
	};

	Panel.prototype.checkTc = function (strict) {
		if (!this.tc) { return true; }
		var count = this.q('tc-count');
		if (this.foreign && this.foreign.checked) {
			this.setState(this.tc, 'valid', 'Yabancı uyruklu olarak faturalandırılacak.');
			return true;
		}
		var v = this.tc.value;
		if (count) { count.textContent = v.length + '/11'; }
		if (v.length === 11) {
			if (isTckn(v)) { this.setState(this.tc, 'valid', ''); return true; }
			this.setState(this.tc, 'bad', 'Bu TC kimlik numarası geçerli değil. Rakamları kontrol edin.');
			return false;
		}
		if (!v.length) {
			if (strict && this.tcRequired()) { this.setState(this.tc, 'bad', 'Fatura için TC kimlik numarası gerekli.'); return false; }
			this.setState(this.tc, '', '');
			return !this.tcRequired();
		}
		if (strict) { this.setState(this.tc, 'bad', 'TC kimlik numarası 11 haneli olmalı.'); return false; }
		this.setState(this.tc, '', '');
		return false;
	};

	Panel.prototype.bindTc = function () {
		var self = this;
		if (!this.tc) { return; }
		this.tc.addEventListener('input', function () {
			var v = self.tc.value.replace(/\D/g, '').slice(0, 11);
			if (v !== self.tc.value) { self.tc.value = v; }
			self.checkTc(false);
			self.render();
		});
		this.tc.addEventListener('blur', function () { if (self.tc.value) { self.checkTc(true); } });
		if (this.foreign) {
			this.foreign.addEventListener('change', function () {
				self.tc.disabled = self.foreign.checked;
				if (self.foreign.checked) { self.tc.value = ''; }
				self.checkTc(false);
				if (!self.foreign.checked) { self.tc.focus(); }
				self.render();
			});
		}
	};

	/* ----- VKN ----- */

	Panel.prototype.checkVkn = function (strict) {
		if (!this.vkn) { return true; }
		var v = this.vkn.value, badge = this.q('vkn-badge'), count = this.q('vkn-count');
		var tckn = v.length === 11;
		if (badge) { badge.textContent = tckn ? 'TCKN' : 'VKN'; }
		if (count) { count.textContent = v.length + (v.length > 10 ? '/11' : '/10'); }

		if (v.length === 10 && isVkn(v)) { this.setState(this.vkn, 'valid', 'Vergi kimlik numarası doğrulandı.'); return true; }
		if (tckn && isTckn(v)) { this.setState(this.vkn, 'valid', 'Şahıs şirketi — TC kimlik numarası doğrulandı.'); return true; }
		if (tckn) { this.setState(this.vkn, 'bad', 'Bu TC kimlik numarası geçerli değil.'); return false; }
		if (!v.length) {
			if (strict && C.vknReq) { this.setState(this.vkn, 'bad', 'Vergi kimlik numarası gerekli.'); return false; }
			this.setState(this.vkn, '', '');
			return !C.vknReq;
		}
		if (strict) {
			this.setState(this.vkn, 'bad', v.length === 10 ? 'Bu vergi kimlik numarası geçerli değil.' : 'Vergi no 10, TC kimlik no 11 haneli olmalı.');
			return false;
		}
		this.setState(this.vkn, '', '');
		return false;
	};

	Panel.prototype.bindVkn = function () {
		var self = this;
		if (!this.vkn) { return; }
		this.vkn.addEventListener('input', function () {
			var v = self.vkn.value.replace(/\D/g, '').slice(0, 11);
			if (v !== self.vkn.value) { self.vkn.value = v; }
			self.checkVkn(false);
			self.render();
		});
		this.vkn.addEventListener('blur', function () { if (self.vkn.value) { self.checkVkn(true); } });
	};

	/* ----- vergi dairesi seçimi ----- */

	Panel.prototype.currentIl = function () {
		var st = document.getElementById('billing_state');
		var m = st && st.value && st.value.match(/^TR(\d{2})$/);
		return m ? Number(m[1]) : 0;
	};

	Panel.prototype.bindVd = function () {
		var self = this, inp = this.vdInput;
		if (!inp) { return; }
		this.vdOpen = false;
		this.vdText = this.vd.value;

		var sync = function () { inp.readOnly = isMobile(); inp.setAttribute('inputmode', isMobile() ? 'none' : 'text'); };
		sync();
		window.matchMedia('(max-width: 640px)').addEventListener('change', function () { self.closeVd(); sync(); });

		inp.addEventListener('focus', function () {
			if (isMobile()) { return; }
			self.openVd('');
			setTimeout(function () { try { inp.select(); } catch (e) { /* noop */ } }, 0);
		});
		inp.addEventListener('click', function () { if (isMobile() || !self.vdOpen) { self.openVd(''); } });
		inp.addEventListener('input', function () {
			if (!self.vdOpen) { self.openVd(inp.value); } else { self.vdQuery = inp.value; self.renderVd(); }
		});
		inp.addEventListener('keydown', function (e) { self.vdKey(e); });
		inp.addEventListener('blur', function () {
			if (isMobile()) { return; }
			setTimeout(function () { if (document.activeElement !== inp) { self.closeVd(); } }, 120);
		});
		document.addEventListener('mousedown', function (e) {
			if (self.vdOpen && !isMobile() && !self.vdBox.contains(e.target)) { self.closeVd(); }
		});
	};

	Panel.prototype.openVd = function (query) {
		if (this.vdOpen) { return; }
		var self = this, sheet = isMobile();
		this.vdOpen = true;
		this.vdQuery = query || '';
		this.vdActive = -1;

		var pop = h('div', 'wdfb-pop' + (sheet ? ' is-sheet' : ''));
		if (sheet) {
			var head = h('div', 'wdfb-pop__head');
			head.appendChild(h('span', 'wdfb-pop__grab'));
			var row = h('div', 'wdfb-pop__row');
			row.appendChild(h('span', null, 'Vergi dairesi'));
			var close = h('button', 'wdfb-pop__close');
			close.type = 'button';
			close.setAttribute('aria-label', 'Kapat');
			close.appendChild(svg(ICON.close));
			close.addEventListener('click', function () { self.closeVd(); });
			row.appendChild(close);
			head.appendChild(row);
			var sb = h('label', 'wdfb-pop__search');
			sb.appendChild(svg(ICON.search));
			var si = h('input');
			si.type = 'search';
			si.placeholder = 'Vergi dairesi veya il ara';
			si.addEventListener('input', function () { self.vdQuery = si.value; self.renderVd(); });
			si.addEventListener('keydown', function (e) { self.vdKey(e); });
			sb.appendChild(si);
			head.appendChild(sb);
			pop.appendChild(head);
			this.vdSheetInput = si;
		}
		this.vdMeta = h('div', 'wdfb-pop__meta');
		pop.appendChild(this.vdMeta);
		this.vdList = h('ul', 'wdfb-pop__list');
		this.vdList.setAttribute('role', 'listbox');
		pop.appendChild(this.vdList);
		this.vdManual = h('button', 'wdfb-pop__manual');
		this.vdManual.type = 'button';
		this.vdManual.hidden = true;
		pop.appendChild(this.vdManual);

		pop.addEventListener('mousedown', function (e) { if (e.target.tagName !== 'INPUT') { e.preventDefault(); } });
		this.vdList.addEventListener('click', function (e) {
			var li = e.target.closest('.wdfb-it');
			if (li) { self.pickVd(Number(li.getAttribute('data-i'))); }
		});
		this.vdList.addEventListener('mousemove', function (e) {
			var li = e.target.closest('.wdfb-it');
			if (li) { self.activeVd(Number(li.getAttribute('data-i')), false); }
		});
		this.vdManual.addEventListener('click', function () { self.manualVd(); });

		if (sheet) {
			this.vdPortal = h('div', 'wdfb wdfb-portal');
			this.vdPortal.setAttribute('data-theme', this.root.getAttribute('data-theme'));
			var bd = h('div', 'wdfb-backdrop');
			bd.addEventListener('click', function () { self.closeVd(); });
			this.vdPortal.appendChild(bd);
			this.vdPortal.appendChild(pop);
			document.body.appendChild(this.vdPortal);
			document.documentElement.classList.add('wdfb-lock');
			setTimeout(function () { if (self.vdSheetInput) { self.vdSheetInput.focus(); } }, 60);
		} else {
			this.vdBox.appendChild(pop);
			var r = this.vdBox.getBoundingClientRect();
			if (window.innerHeight - r.bottom < 280 && r.top > window.innerHeight - r.bottom) { pop.classList.add('is-up'); }
		}
		this.vdPop = pop;
		this.vdBox.classList.add('is-open');
		this.vdInput.setAttribute('aria-expanded', 'true');
		this.renderVd();

		if (!this.vdQuery && this.vdKod.value) {
			var kod = this.vdKod.value;
			var at = this.vdView.findIndex(function (x) { return x.kod === kod; });
			if (at >= 0) { this.activeVd(at, true); }
		}
	};

	Panel.prototype.closeVd = function () {
		if (!this.vdOpen) { return; }
		this.vdOpen = false;
		if (this.vdPortal) { this.vdPortal.remove(); this.vdPortal = null; document.documentElement.classList.remove('wdfb-lock'); }
		else if (this.vdPop) { this.vdPop.remove(); }
		this.vdPop = null;
		this.vdSheetInput = null;
		this.vdBox.classList.remove('is-open');
		this.vdInput.setAttribute('aria-expanded', 'false');
		this.vdInput.value = this.vd.value;
	};

	Panel.prototype.renderVd = function () {
		var self = this;
		var q = fold(this.vdQuery).trim();
		var il = this.currentIl();
		var list;

		if (!q) {
			var local = il ? ALL.filter(function (x) { return x.il === il; }) : [];
			var rest = ALL.filter(function (x) { return x.il !== il; });
			list = local.map(function (x) { return { it: x, g: (C.iller[il] || '') + ' vergi daireleri' }; })
				.concat(rest.map(function (x) { return { it: x, g: x.ilAd }; }));
		} else {
			var tokens = q.split(/\s+/), scored = [];
			ALL.forEach(function (x, i) {
				var pos = x.f.indexOf(q), s;
				if (pos === 0) { s = 0; }
				else if (pos > 0) { s = x.f.charAt(pos - 1) === ' ' ? 1 : 2; }
				else {
					for (var t = 0; t < tokens.length; t++) { if (x.fi.indexOf(tokens[t]) < 0) { return; } }
					s = 3;
				}
				if (x.il === il) { s -= 0.5; }
				scored.push({ it: x, s: s, i: i, pos: pos });
			});
			scored.sort(function (a, b) { return (a.s - b.s) || (a.i - b.i); });
			list = scored.slice(0, 150);
		}

		var frag = document.createDocumentFragment(), lastG = null;
		this.vdView = [];
		list.forEach(function (row) {
			if (!q && row.g !== lastG) {
				frag.appendChild(h('li', 'wdfb-grp', row.g));
				lastG = row.g;
			}
			var x = row.it, n = self.vdView.length;
			var li = h('li', 'wdfb-it');
			li.setAttribute('role', 'option');
			li.setAttribute('data-i', n);
			var name = h('span', 'wdfb-it__name');
			if (q && row.pos >= 0) {
				name.appendChild(document.createTextNode(x.name.slice(0, row.pos)));
				name.appendChild(h('mark', null, x.name.slice(row.pos, row.pos + q.length)));
				name.appendChild(document.createTextNode(x.name.slice(row.pos + q.length)));
			} else {
				name.textContent = x.name;
			}
			li.appendChild(name);
			if (x.kod === self.vdKod.value) { li.appendChild(svg(ICON.check, 'wdfb-it__check')); }
			else if (q || x.il !== il) { li.appendChild(h('span', 'wdfb-it__side', x.ilAd)); }
			frag.appendChild(li);
			self.vdView.push(x);
		});
		if (!list.length) { frag.appendChild(h('li', 'wdfb-pop__empty', 'Eşleşen vergi dairesi bulunamadı')); }

		this.vdList.textContent = '';
		this.vdList.appendChild(frag);

		this.vdMeta.textContent = '';
		if (q) {
			this.vdMeta.appendChild(h('span', null, list.length + ' sonuç'));
		} else {
			this.vdMeta.appendChild(h('span', null, ALL.length.toLocaleString('tr-TR') + ' vergi dairesi'));
			if (il && C.iller[il]) {
				var b = h('span');
				b.appendChild(document.createTextNode('Önce '));
				b.appendChild(h('b', null, C.iller[il]));
				this.vdMeta.appendChild(b);
			}
		}

		var raw = (this.vdQuery || '').trim();
		this.vdManualIndex = -1;
		if (raw.length >= 3 && !list.some(function (r) { return r.pos === 0; })) {
			this.vdManual.textContent = '';
			this.vdManual.appendChild(svg(ICON.pen));
			var sp = h('span');
			sp.appendChild(document.createTextNode('Listede yok mu? '));
			sp.appendChild(h('em', null, '“' + trTitle(raw) + '”'));
			sp.appendChild(document.createTextNode(' yaz'));
			this.vdManual.appendChild(sp);
			this.vdManual.hidden = false;
			this.vdManualIndex = this.vdView.length;
		} else {
			this.vdManual.hidden = true;
		}

		this.vdActive = -1;
		if (q && this.vdView.length) { this.activeVd(0, false); }
	};

	Panel.prototype.activeVd = function (i, scroll) {
		var max = this.vdView.length + (this.vdManualIndex >= 0 ? 1 : 0);
		if (!max) { return; }
		i = Math.max(0, Math.min(max - 1, i));
		var prev = this.vdList.querySelector('.wdfb-it.is-active');
		if (prev) { prev.classList.remove('is-active'); }
		this.vdManual.classList.toggle('is-active', i === this.vdManualIndex);
		this.vdActive = i;
		var li = this.vdList.querySelector('[data-i="' + i + '"]');
		if (li) {
			li.classList.add('is-active');
			if (scroll !== false) { li.scrollIntoView({ block: 'nearest' }); }
		}
	};

	Panel.prototype.vdKey = function (e) {
		switch (e.key) {
			case 'ArrowDown': e.preventDefault(); if (!this.vdOpen) { this.openVd(''); } else { this.activeVd(this.vdActive + 1); } break;
			case 'ArrowUp': if (this.vdOpen) { e.preventDefault(); this.activeVd(this.vdActive - 1); } break;
			case 'Enter':
				if (this.vdOpen) {
					e.preventDefault();
					if (this.vdActive === this.vdManualIndex && this.vdActive >= 0) { this.manualVd(); }
					else if (this.vdActive >= 0) { this.pickVd(this.vdActive); }
				}
				break;
			case 'Escape': if (this.vdOpen) { e.preventDefault(); this.closeVd(); } break;
			case 'Tab': if (this.vdOpen && this.vdQuery && this.vdActive >= 0 && this.vdActive !== this.vdManualIndex) { this.pickVd(this.vdActive); } else { this.closeVd(); } break;
		}
	};

	Panel.prototype.pickVd = function (i) {
		var x = this.vdView[i];
		if (!x) { return; }
		this.vd.value = x.name;
		this.vdKod.value = x.kod;
		this.closeVd();
		this.clearErr(this.vdInput);
		this.render();
		if (this.vkn && !this.vkn.value && !isMobile()) { this.vkn.focus(); }
	};

	Panel.prototype.manualVd = function () {
		var raw = trTitle((this.vdQuery || '').trim());
		if (raw.length < 3) { return; }
		if (!/vergi dairesi|malmüdürlüğü/i.test(raw)) { raw += ' Vergi Dairesi'; }
		this.vd.value = raw;
		this.vdKod.value = '';
		this.closeVd();
		this.clearErr(this.vdInput);
		this.render();
	};

	/* ----- ülke ----- */

	Panel.prototype.bindCountry = function () {
		var self = this;
		var apply = function () {
			var el = document.getElementById('billing_country');
			var tr = !el || !el.value || el.value === 'TR';
			if (self.row) { self.row.classList.toggle('is-off', !tr); }
		};
		$(document.body).on('change', '#billing_country', apply);
		apply();
	};

	/* ----- özet ----- */

	Panel.prototype.render = function () {
		if (!this.summary) { return; }
		var txt = this.q('summary-text'), ok = false;
		txt.textContent = '';
		var add = function (node, sep) {
			if (sep && txt.childNodes.length) { txt.appendChild(h('span', 'sep', '·')); }
			txt.appendChild(typeof node === 'string' ? document.createTextNode(node) : node);
		};

		if (this.tip() === 'bireysel') {
			var f = document.getElementById('billing_first_name'), l = document.getElementById('billing_last_name');
			var name = ((f ? f.value : '') + ' ' + (l ? l.value : '')).trim();
			var tcOk = !this.tc || (this.foreign && this.foreign.checked) || (this.tc.value.length === 11 && isTckn(this.tc.value));
			var tcEmptyOk = this.tc && !this.tc.value && !this.tcRequired() && C.tcMode !== 'hidden';
			if (name) { add(h('b', null, name)); }
			if (this.foreign && this.foreign.checked) { add('Yabancı uyruklu', true); }
			else if (this.tc && this.tc.value.length === 11 && isTckn(this.tc.value)) { add(h('i', null, 'TCKN ' + mask(this.tc.value)), true); }
			add('Bireysel', true);
			ok = !!name && (tcOk || tcEmptyOk);
		} else {
			var u = this.unvan ? this.unvan.value.trim() : '';
			var v = this.vkn ? this.vkn.value : '';
			var vOk = (v.length === 10 && isVkn(v)) || (v.length === 11 && isTckn(v));
			if (u) { add(h('b', null, u)); }
			if (this.vd.value) { add(this.vd.value, true); }
			if (vOk) { add(h('i', null, (v.length === 11 ? 'TCKN ' : 'VKN ') + v), true); }
			if (this.efatura && this.efatura.checked) { add('e-Fatura', true); }
			ok = (!C.unvanReq || u.length >= 2) && (!C.vdReq || !!this.vd.value) && (vOk || (!v && !C.vknReq));
		}
		if (!txt.childNodes.length) { txt.textContent = 'Bilgiler tamamlandığında burada görünür'; }
		this.summary.setAttribute('data-state', ok ? 'ok' : 'empty');
	};

	/* ----- gönderim ----- */

	Panel.prototype.validate = function () {
		if (!this.row || this.row.offsetParent === null) { return true; }
		var first = null, self = this;
		var fail = function (el, msg) {
			var f = self.field(el);
			if (!f) { return; }
			if (msg) { self.setState(el, 'bad', msg); }
			f.classList.add('is-invalid', 'is-shake');
			setTimeout(function () { f.classList.remove('is-shake'); }, 400);
			if (!first) { first = el; }
		};

		if (this.tip() === 'bireysel') {
			if (this.tc && !this.checkTc(true)) { fail(this.tc); }
		} else {
			if (C.unvanReq && (!this.unvan || this.unvan.value.trim().length < 2)) { fail(this.unvan, 'Firma ünvanı gerekli.'); }
			if (C.vdReq && !this.vd.value) { fail(this.vdInput, 'Vergi dairesi seçin.'); }
			if (this.vkn && !this.checkVkn(true)) { fail(this.vkn); }
		}
		if (first) {
			first.scrollIntoView({ behavior: 'smooth', block: 'center' });
			if (!first.disabled && first !== this.vdInput) { setTimeout(function () { first.focus({ preventScroll: true }); }, 350); }
			return false;
		}
		return true;
	};

	Panel.prototype.bindValidation = function () {
		var self = this;
		if (!this.form) { return; }
		if (this.isCheckout) {
			$(this.form).on('checkout_place_order', function () { return self.validate() ? undefined : false; });
		} else {
			this.form.addEventListener('submit', function (e) { if (!self.validate()) { e.preventDefault(); } });
		}
	};

	var boot = function () {
		document.querySelectorAll('[data-wdfb]').forEach(function (root) {
			if (!root.__wdfb) { root.__wdfb = new Panel(root); }
		});
	};
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', boot); } else { boot(); }

})(jQuery);
