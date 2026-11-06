/* پنل پیچا — صفحه‌ی تغییر قیمت */
(function () {
	'use strict';
	var root = document.querySelector('.pp-prices');
	if (!root || !window.PP) { return; }
	var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
	var en = function (v) { return String(v || '').replace(/[۰-۹]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); }).replace(/[٠-٩]/g, function (d) { return '٠١٢٣٤٥٦٧٨٩'.indexOf(d); }).replace(/[٬,\s]/g, ''); };
	var fa = function (n) { return Number(n).toLocaleString('fa-IR'); };
	var bar = document.getElementById('ppSavebar'), cnt = document.getElementById('ppSaveCount');

	function fmt(inp) {
		var s = inp.parentNode.querySelector('.ppFmt'), v = en(inp.value);
		s.textContent = v !== '' && !isNaN(v) ? fa(v) + ' ' + (PP.cur || '') : '';
	}
	function dirty(inp) { return en(inp.value) !== en(inp.getAttribute('data-orig')); }
	function check(row) {
		var ins = $$('.ppP', row), ch = ins.some(dirty);
		ins.forEach(function (i) { i.classList.toggle('chg', dirty(i)); });
		row.classList.toggle('pp-dirty', ch);
		var r = row.querySelector('.ppP[data-f=regular]'), s = row.querySelector('.ppP[data-f=sale]');
		var bad = en(s.value) !== '' && (en(r.value) === '' || +en(s.value) >= +en(r.value));
		row.classList.toggle('pp-bad', bad);
		row.querySelector('.pp-rmsg').textContent = bad ? 'قیمت ویژه باید کمتر از اصلی باشد' : '';
	}
	function refresh() {
		var n = $$('tr.pp-dirty', root).length;
		bar.hidden = !n;
		cnt.textContent = n ? fa(n) + ' ردیف تغییر کرده' : '';
	}
	$$('.ppP', root).forEach(function (i) { fmt(i); });
	root.addEventListener('input', function (e) {
		if (!e.target.classList.contains('ppP')) { return; }
		fmt(e.target); check(e.target.closest('tr')); refresh();
	});
	root.addEventListener('keydown', function (e) {
		if (e.key === 'Enter' && e.target.classList.contains('ppP')) { e.preventDefault(); save(); }
	});
	/* انتخاب ردیف‌ها */
	var all = document.getElementById('ppAll');
	if (all) { all.addEventListener('change', function () { $$('.ppSel,.ppGroup', root).forEach(function (c) { c.checked = all.checked; }); }); }
	root.addEventListener('change', function (e) {
		if (e.target.classList.contains('ppGroup')) { $$('tr[data-group="' + e.target.getAttribute('data-group') + '"] .ppSel', root).forEach(function (c) { c.checked = e.target.checked; }); }
	});
	/* تغییر گروهی */
	document.getElementById('ppbApply').addEventListener('click', function () {
		var op = document.getElementById('ppbOp').value, val = parseFloat(en(document.getElementById('ppbVal').value));
		var field = document.getElementById('ppbField').value, rnd = +document.getElementById('ppbRound').value;
		if (op !== 'clear_sale' && (isNaN(val) || val < 0)) { alert('مقدار را وارد کنید'); return; }
		if ((op === 'inc_pct' || op === 'dec_pct' || op === 'sale_pct') && val >= 100 && op !== 'inc_pct') { alert('درصد باید کمتر از ۱۰۰ باشد'); return; }
		var rows = $$('tr.pp-prc', root).filter(function (r) { return r.querySelector('.ppSel').checked; });
		if (!rows.length) { rows = $$('tr.pp-prc', root); }
		var round = function (n) { n = Math.max(0, n); return rnd ? Math.round(n / rnd) * rnd : Math.round(n); };
		rows.forEach(function (row) {
			var r = row.querySelector('.ppP[data-f=regular]'), s = row.querySelector('.ppP[data-f=sale]');
			var calc = function (inp) {
				var v = en(inp.value); if (v === '' || isNaN(v)) { return; } v = +v;
				if (op === 'inc_pct') { v = v * (1 + val / 100); } else if (op === 'dec_pct') { v = v * (1 - val / 100); }
				else if (op === 'inc_amt') { v = v + val; } else if (op === 'dec_amt') { v = v - val; } else if (op === 'set') { v = val; }
				inp.value = round(v);
			};
			if (op === 'clear_sale') { s.value = ''; }
			else if (op === 'sale_pct') { var base = en(r.value); if (base !== '' && !isNaN(base)) { s.value = round(+base * (1 - val / 100)); } }
			else if (op === 'set' && field === 'sale') { s.value = round(val); }
			else {
				if (field === 'regular' || field === 'both') { calc(r); }
				if (field === 'sale' || field === 'both') { calc(s); }
			}
			fmt(r); fmt(s); check(row);
		});
		refresh();
		var first = root.querySelector('tr.pp-dirty'); if (first) { first.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
	});
	document.getElementById('ppUndo').addEventListener('click', function () {
		$$('tr.pp-dirty', root).forEach(function (row) { $$('.ppP', row).forEach(function (i) { i.value = i.getAttribute('data-orig'); fmt(i); }); check(row); });
		refresh();
	});
	/* ذخیره‌ی یک‌جا */
	var saving = false;
	function save() {
		if (saving) { return; }
		var rows = $$('tr.pp-dirty', root);
		if (!rows.length) { return; }
		var badRows = rows.filter(function (r) { return r.classList.contains('pp-bad'); });
		if (badRows.length) { badRows[0].scrollIntoView({ behavior: 'smooth', block: 'center' }); alert('در ' + fa(badRows.length) + ' ردیف قیمت ویژه بیشتر از قیمت اصلی است.'); return; }
		var items = rows.map(function (row) { return { id: +row.getAttribute('data-id'), regular: en(row.querySelector('.ppP[data-f=regular]').value), sale: en(row.querySelector('.ppP[data-f=sale]').value) }; });
		var btn = document.getElementById('ppSaveAll'); saving = true; btn.disabled = true; btn.textContent = 'در حال ذخیره…';
		var fd = new FormData(); fd.append('action', 'pp_price_batch'); fd.append('nonce', PP.pnonce); fd.append('items', JSON.stringify(items));
		fetch(PP.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (res) {
			if (!res || !res.success) { throw new Error(res && res.data && res.data.msg ? res.data.msg : 'خطا'); }
			rows.forEach(function (row) {
				var d = res.data.items[row.getAttribute('data-id')], m = row.querySelector('.pp-rmsg');
				if (d && d.ok) {
					var r = row.querySelector('.ppP[data-f=regular]'), s = row.querySelector('.ppP[data-f=sale]');
					r.value = d.regular; s.value = d.sale; r.setAttribute('data-orig', d.regular); s.setAttribute('data-orig', d.sale);
					fmt(r); fmt(s); check(row); m.textContent = 'ذخیره شد ✔'; row.classList.add('pp-saved');
					setTimeout(function () { row.classList.remove('pp-saved'); if (m.textContent === 'ذخیره شد ✔') { m.textContent = ''; } }, 2500);
				} else { m.textContent = (d && d.msg) || 'ذخیره نشد'; row.classList.add('pp-bad'); }
			});
		}).catch(function (e) { alert('ذخیره نشد: ' + e.message); }).then(function () { saving = false; btn.disabled = false; btn.textContent = 'ذخیره‌ی همه'; refresh(); });
	}
	document.getElementById('ppSaveAll').addEventListener('click', save);
	window.addEventListener('beforeunload', function (e) { if ($$('tr.pp-dirty', root).length) { e.preventDefault(); e.returnValue = ''; } });
})();
