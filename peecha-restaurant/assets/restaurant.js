(function () {
  var key = "peecha_rest_cart";
  function read() {
    try { return JSON.parse(localStorage.getItem(key) || "[]"); } catch (e) { return []; }
  }
  function write(list) { localStorage.setItem(key, JSON.stringify(list)); }
  function esc(s) {
    return String(s == null ? "" : s).replace(/[&<>"]/g, function (c) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", "\"": "&quot;" }[c];
    });
  }
  function money(n) { return Number(n || 0).toLocaleString("fa-IR") + " تومان"; }
  function qty(list, id, delta) {
    var row = list.filter(function (x) { return x.id === id; })[0];
    if (!row) return list;
    row.qty += delta;
    return list.filter(function (x) { return x.qty > 0; });
  }
  function applyFilters() {
    var catBtn = document.querySelector(".prm-filters button.on");
    var cat = catBtn ? catBtn.getAttribute("data-filter") || "" : "";
    var q = "";
    var search = document.querySelector(".prm-search");
    if (search) q = search.value.trim();
    var diet = document.querySelector(".prm-diet.on");
    var flag = diet ? diet.getAttribute("data-diet") : "";
    var n = 0;
    document.querySelectorAll(".prm-item").forEach(function (card) {
      var text = (card.getAttribute("data-text") || "").indexOf(q) !== -1;
      var show = (!cat || card.getAttribute("data-cat") === cat) && text && (!flag || card.getAttribute("data-" + flag) === "1");
      card.hidden = !show;
      if (show) n++;
    });
    document.querySelectorAll(".prm-section").forEach(function (sec) {
      var visible = sec.querySelector(".prm-item:not([hidden])");
      sec.hidden = !visible;
    });
    var empty = document.querySelector(".prm-empty");
    if (empty) empty.hidden = n !== 0;
  }
  function paint() {
    var list = read();
    var total = 0;
    var count = 0;
    list.forEach(function (row) { total += row.price * row.qty; count += row.qty; });
    document.querySelectorAll(".prm-dock").forEach(function (dock) {
      dock.hidden = count === 0;
      var label = dock.querySelector("[data-dock]");
      if (label) label.textContent = count.toLocaleString("fa-IR") + " غذا · " + money(total);
    });
    var box = document.querySelector(".prm-lines");
    var form = document.querySelector(".prm-cart");
    if (!box || !form) return;
    var hidden = form.querySelector("[name=items]");
    if (!list.length) {
      box.textContent = "از منو یک غذا اضافه کنید.";
      if (hidden) hidden.value = "";
      var sum = form.querySelector(".prm-sum");
      if (sum) sum.hidden = true;
      return;
    }
    box.innerHTML = list.map(function (row) {
      return '<div class="prm-line"><span>' + esc(row.name) + '</span><span class="prm-qty">' +
        '<button type="button" data-qty="-1" data-id="' + row.id + '">−</button><b>' + row.qty.toLocaleString("fa-IR") +
        '</b><button type="button" data-qty="1" data-id="' + row.id + '">+</button></span><b>' + money(row.price * row.qty) + "</b></div>";
    }).join("");
    var sum = form.querySelector(".prm-sum");
    if (sum) { sum.hidden = false; sum.querySelector("b").textContent = money(total); }
    if (hidden) hidden.value = JSON.stringify(list.map(function (row) { return { id: row.id, qty: row.qty }; }));
  }
  document.addEventListener("input", function (e) {
    if (e.target && e.target.classList && e.target.classList.contains("prm-search")) applyFilters();
  });
  document.addEventListener("click", function (e) {
    var f = e.target.closest && e.target.closest("[data-filter]");
    if (f) {
      document.querySelectorAll("[data-filter]").forEach(function (b) { b.classList.toggle("on", b === f); });
      applyFilters();
      return;
    }
    var d = e.target.closest && e.target.closest("[data-diet]");
    if (d) {
      var on = d.classList.contains("on");
      document.querySelectorAll("[data-diet]").forEach(function (b) { b.classList.remove("on"); });
      if (!on) d.classList.add("on");
      applyFilters();
      return;
    }
    var step = e.target.closest && e.target.closest("[data-qty]");
    if (step) {
      write(qty(read(), parseInt(step.getAttribute("data-id"), 10), parseInt(step.getAttribute("data-qty"), 10)));
      paint();
      return;
    }
    var add = e.target.closest && e.target.closest(".prm-add");
    if (!add) return;
    var id = parseInt(add.getAttribute("data-id"), 10);
    var list = read();
    var row = list.filter(function (x) { return x.id === id; })[0];
    if (row) row.qty += 1;
    else list.push({ id: id, name: add.getAttribute("data-name"), price: parseInt(add.getAttribute("data-price"), 10) || 0, qty: 1 });
    write(list);
    add.textContent = "اضافه شد";
    window.setTimeout(function () { add.textContent = "افزودن"; }, 700);
    paint();
  });
  document.addEventListener("submit", function (e) {
    if (e.target && e.target.classList && e.target.classList.contains("prm-cart")) paint();
  });
  if (document.querySelector(".prm-note") && location.search.indexOf("pr_msg=ok") !== -1) {
    localStorage.removeItem(key);
  }
  paint();
})();
