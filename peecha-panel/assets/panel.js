/* پنل پیچا — بدون وابستگی به jQuery */
(function () {
	'use strict';
	var $ = function (s, c) { return (c || document).querySelector(s); };
	var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
	var FA = '۰۱۲۳۴۵۶۷۸۹';
	function toEn(v) { return String(v).replace(/[۰-۹]/g, function (d) { return FA.indexOf(d); }).replace(/[٠-٩]/g, function (d) { return d.charCodeAt(0) - 1632; }).replace(/[,٬]/g, ''); }

	/* تأیید قبل از کارهای حساس */
	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-confirm]');
		if (b && !window.confirm(b.getAttribute('data-confirm'))) { e.preventDefault(); }
		if (e.target.closest('[data-print]')) { window.print(); }
	});
	/* جلوگیری از ارسال دوباره‌ی فرم */
	document.addEventListener('submit', function (e) {
		var f = e.target;
		if (e.defaultPrevented || !f.closest('.pp')) { return; }
		if (f.dataset.sent) { e.preventDefault(); return; }
		f.dataset.sent = '1';
		$$('button', f).forEach(function (b) { b.disabled = true; });
	});

	/* انتخاب همه‌ی سفارش‌ها */
	var all = $('.pp-check-all');
	if (all) { all.addEventListener('change', function () { $$('input[name="ids[]"]').forEach(function (c) { c.checked = all.checked; }); }); }
	var bulk = $('#pp-bulk');
	if (bulk) {
		bulk.addEventListener('submit', function (e) {
			if (!$$('input[name="ids[]"]:checked').length) { e.preventDefault(); alert('ابتدا یک یا چند سفارش را انتخاب کنید.'); }
		}, true);
	}

	/* نمایش حالت‌های محصول متغیر */
	$$('.pp-vtoggle').forEach(function (b) {
		b.addEventListener('click', function () {
			var rows = $$('.pp-var-of-' + b.dataset.for), open = rows.length && rows[0].hidden;
			rows.forEach(function (r) { r.hidden = !open; });
			b.textContent = open ? 'حالت‌ها ▴' : 'حالت‌ها ▾';
		});
	});

	/* ویرایش سریع قیمت و موجودی */
	$$('.pp-prow').forEach(function (row) {
		$$('.pp-in', row).forEach(function (i) {
			i.dataset.orig = i.value;
			i.addEventListener('input', function () { i.classList.toggle('changed', i.value !== i.dataset.orig); });
			i.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); var s = $('.pp-qsave', row); if (s) { s.click(); } } });
		});
	});
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.pp-qsave');
		if (!btn) { return; }
		var row = btn.closest('.pp-prow'), msg = $('.pp-rmsg', row), fd = new FormData();
		fd.append('action', 'pp_quick_save');
		fd.append('nonce', window.PP.nonce);
		fd.append('id', row.dataset.id);
		$$('.pp-in', row).forEach(function (i) { fd.append(i.name, i.tagName === 'SELECT' ? i.value : toEn(i.value.trim())); });
		btn.disabled = true; msg.className = 'pp-rmsg'; msg.textContent = '…';
		fetch(window.PP.ajax, { method: 'POST', body: fd, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (j) {
				btn.disabled = false;
				if (!j || !j.success) { msg.className = 'pp-rmsg bad'; msg.textContent = (j && j.data && j.data.msg) || 'خطا در ذخیره'; return; }
				var d = j.data;
				$$('.pp-in', row).forEach(function (i) {
					if (i.name === 'regular') { i.value = d.regular; }
					if (i.name === 'sale') { i.value = d.sale; }
					if (i.name === 'stock' && d.stock !== null) { i.value = d.stock; }
					i.dataset.orig = i.value; i.classList.remove('changed'); i.dispatchEvent(new Event('pp-sync'));
				});
				msg.className = 'pp-rmsg' + (d.warn ? ' bad' : ''); msg.textContent = d.msg;
				setTimeout(function () { if (!d.warn) { msg.textContent = ''; } }, 2500);
			})
			.catch(function () { btn.disabled = false; msg.className = 'pp-rmsg bad'; msg.textContent = 'اتصال برقرار نشد'; });
	});

	/* نمایش قیمت با جداکننده‌ی هزارگان زیر کادرها تا صفرها اشتباه نشوند */
	var faNum = function (n) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '٬').replace(/\d/g, function (d) { return FA[d]; }); };
	$$('.pp input[name="regular"], .pp input[name="sale"]').forEach(function (i) {
		var h = document.createElement('small'); h.className = 'pp-hint'; i.insertAdjacentElement('afterend', h);
		var f = function () { var v = toEn(i.value.trim()); h.textContent = /^\d+(\.\d+)?$/.test(v) && +v >= 1000 ? faNum(v.split('.')[0]) : ''; };
		i.addEventListener('input', f); i.addEventListener('pp-sync', f); f();
	});

	/* نمایش/پنهان فیلدهای موجودی */
	$$('[data-toggle]').forEach(function (c) {
		var f = function () { var sel = c.getAttribute('data-toggle').split(','); $(sel[0]).hidden = !c.checked; $(sel[1]).hidden = c.checked; };
		c.addEventListener('change', f);
	});
	/* تبدیل اعداد فارسی هنگام ارسال فرم محصول */
	var ed = $('.pp-edit');
	if (ed) { ed.addEventListener('submit', function () { $$('input[inputmode]', ed).forEach(function (i) { i.value = toEn(i.value.trim()); }); }, true); }

	/* انتخاب تصویر از کتابخانه‌ی رسانه */
	$$('.pp-media').forEach(function (box) {
		var multi = box.dataset.multiple === '1', input = $('input', box), list = $('.pp-media-list', box), frame;
		function sync() { input.value = $$('figure', list).map(function (f) { return f.dataset.id; }).join(','); }
		function add(id, url) {
			if (!multi) { list.innerHTML = ''; }
			if ($('figure[data-id="' + id + '"]', list)) { return; }
			var f = document.createElement('figure'); f.dataset.id = id; f.draggable = multi;
			var img = document.createElement('img'); img.src = url; img.alt = '';
			var x = document.createElement('button'); x.type = 'button'; x.className = 'pp-media-x'; x.textContent = '×'; x.setAttribute('aria-label', 'حذف');
			f.appendChild(img); f.appendChild(x); list.appendChild(f);
		}
		$('.pp-media-add', box).addEventListener('click', function () {
			if (!window.wp || !wp.media) { return; }
			if (!frame) {
				frame = wp.media({ title: multi ? 'انتخاب تصاویر گالری' : 'انتخاب تصویر محصول', multiple: multi ? 'add' : false, library: { type: 'image' }, button: { text: 'انتخاب' } });
				frame.on('select', function () {
					frame.state().get('selection').each(function (a) {
						var j = a.toJSON(), s = j.sizes && (j.sizes.thumbnail || j.sizes.medium) ? (j.sizes.thumbnail || j.sizes.medium).url : j.url;
						add(j.id, s);
					});
					sync();
				});
			}
			frame.open();
		});
		list.addEventListener('click', function (e) { var x = e.target.closest('.pp-media-x'); if (x) { x.parentNode.remove(); sync(); } });
		if (multi) {
			$$('figure', list).forEach(function (f) { f.draggable = true; });
			var drag = null;
			list.addEventListener('dragstart', function (e) { drag = e.target.closest('figure'); });
			list.addEventListener('dragover', function (e) {
				e.preventDefault();
				var t = e.target.closest('figure');
				if (drag && t && t !== drag) { var r = t.getBoundingClientRect(); list.insertBefore(drag, (e.clientX < r.left + r.width / 2) ? t.nextSibling : t); }
			});
			list.addEventListener('drop', function (e) { e.preventDefault(); drag = null; sync(); });
		}
	});

	/* پیام موفقیت بعد از چند ثانیه محو شود و از آدرس حذف شود */
	var t = $('.pp-toast.ok');
	if (t) { setTimeout(function () { t.style.transition = 'opacity .4s'; t.style.opacity = '0'; setTimeout(function () { t.remove(); }, 400); }, 4000); }
	if (window.history && history.replaceState && /[?&]pp_msg=/.test(location.search)) {
		history.replaceState(null, '', location.href.replace(/([?&])pp_msg=[^&]*&?/, '$1').replace(/[?&]$/, ''));
	}
})();
