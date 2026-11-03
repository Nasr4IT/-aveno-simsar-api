(function () {
  const S = window.Simsar, C = S.CFG;
  S.renderHeader("ads");
  S.renderFooter();

  const BASE_KEYS = ["q", "category_id", "governorate_id", "min_price", "max_price", "sort", "page", "lat", "lng"];
  const form = document.getElementById("filter-form");
  const results = document.getElementById("results");
  const pager = document.getElementById("pager");
  const sortSel = document.getElementById("sort");
  const filtersEl = document.getElementById("filters");
  let categories = [];
  let attrDefs = []; // filterable attributes of the selected category

  document.getElementById("filters-open").innerHTML = S.icon("filter") + " الفلاتر";
  document.getElementById("filters-open").onclick = () => filtersEl.classList.add("open");
  document.getElementById("filters-close").onclick = () => filtersEl.classList.remove("open");
  C.GOVERNORATES.forEach((g) => document.getElementById("f-gov").add(new Option(g.name, g.id)));

  const params = () => new URLSearchParams(location.search);

  function syncForm(p) {
    form.q.value = p.get("q") || "";
    form.category_id.value = p.get("category_id") || "";
    form.governorate_id.value = p.get("governorate_id") || "";
    form.min_price.value = p.get("min_price") || "";
    form.max_price.value = p.get("max_price") || "";
    sortSel.value = p.get("sort") || "";
    attrDefs.forEach((a) => { const el = form.elements["attr_" + a.key]; if (el) el.value = p.get(a.key) || ""; });
  }

  async function loadAttrFilters(catId) {
    const box = document.getElementById("attr-filters");
    attrDefs = [];
    box.innerHTML = "";
    if (!catId) return;
    try {
      const { data } = await S.api("/categories/" + encodeURIComponent(catId));
      attrDefs = (data.attributes || []).filter((a) => ["select", "multiselect", "boolean"].includes(a.type));
      box.innerHTML = attrDefs.map((a) => {
        const opts = a.type === "boolean" ? [["1", "نعم"], ["0", "لا"]] : (a.options || []).map((o) => [o, o]);
        return `<div class="field"><label>${S.esc(a.label_ar)}</label>
          <select name="attr_${S.esc(a.key)}"><option value="">الكل</option>${opts.map(([v, l]) => `<option value="${S.esc(v)}">${S.esc(l)}</option>`).join("")}</select></div>`;
      }).join("");
    } catch { /* attribute filters are optional */ }
  }

  function navigate(p, push = true) {
    const url = "ads.html" + (p.toString() ? "?" + p : "");
    push ? history.pushState(null, "", url) : history.replaceState(null, "", url);
    run();
  }

  function formToParams() {
    const p = new URLSearchParams();
    ["q", "category_id", "governorate_id", "min_price", "max_price"].forEach((k) => { const v = form[k].value.trim(); if (v) p.set(k, v); });
    attrDefs.forEach((a) => { const v = form.elements["attr_" + a.key]?.value; if (v) p.set(a.key, v); });
    const cur = params();
    if (sortSel.value) p.set("sort", sortSel.value);
    if (sortSel.value === "nearest" && cur.get("lat")) { p.set("lat", cur.get("lat")); p.set("lng", cur.get("lng")); }
    return p;
  }

  form.addEventListener("submit", (e) => { e.preventDefault(); filtersEl.classList.remove("open"); navigate(formToParams()); });
  form.category_id.addEventListener("change", async () => { await loadAttrFilters(form.category_id.value); });
  document.getElementById("reset").onclick = () => { filtersEl.classList.remove("open"); navigate(new URLSearchParams()); };

  sortSel.addEventListener("change", () => {
    if (sortSel.value !== "nearest") return navigate(formToParams());
    if (!navigator.geolocation) { alertToast("المتصفح لا يدعم تحديد الموقع"); sortSel.value = params().get("sort") || ""; return; }
    navigator.geolocation.getCurrentPosition(
      (pos) => { const p = formToParams(); p.set("lat", pos.coords.latitude.toFixed(5)); p.set("lng", pos.coords.longitude.toFixed(5)); navigate(p); },
      () => { alertToast("يجب السماح بالوصول إلى موقعك لترتيب الإعلانات حسب الأقرب"); sortSel.value = params().get("sort") || ""; },
      { timeout: 10000 }
    );
  });

  function alertToast(msg) {
    const t = document.createElement("div");
    t.className = "toast"; t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
  }

  function renderCatChips(p) {
    const cur = p.get("category_id") || "";
    const mk = (id, name) => {
      const q = new URLSearchParams(p); q.delete("page");
      // switching category resets that category's attribute filters
      attrDefs.forEach((a) => q.delete(a.key));
      id ? q.set("category_id", id) : q.delete("category_id");
      return `<a class="chip chip-cat ${String(cur) === String(id) ? "on" : ""}" href="ads.html${q.toString() ? "?" + q : ""}">${S.esc(name)}</a>`;
    };
    document.getElementById("cat-chips").innerHTML = mk("", "الكل") + categories.map((c) => mk(c.id, c.name_ar)).join("");
  }

  function renderActiveChips(p) {
    const labels = [];
    const catName = categories.find((c) => String(c.id) === p.get("category_id"))?.name_ar;
    const govName = C.GOVERNORATES.find((g) => String(g.id) === p.get("governorate_id"))?.name;
    if (p.get("q")) labels.push(["q", `"${p.get("q")}"`]);
    if (govName) labels.push(["governorate_id", govName]);
    if (p.get("min_price")) labels.push(["min_price", "من " + p.get("min_price")]);
    if (p.get("max_price")) labels.push(["max_price", "إلى " + p.get("max_price")]);
    attrDefs.forEach((a) => { if (p.get(a.key)) labels.push([a.key, `${a.label_ar}: ${p.get(a.key)}`]); });
    const el = document.getElementById("active-chips");
    el.innerHTML = labels.map(([k, l]) => `<button class="chip" data-k="${S.esc(k)}">${S.esc(l)} ×</button>`).join("");
    el.querySelectorAll("button").forEach((b) => b.onclick = () => { const q = params(); q.delete(b.dataset.k); q.delete("page"); navigate(q); });
    document.getElementById("page-title").textContent = catName ? `إعلانات ${catName}` : "تصفح الإعلانات";
    document.title = (catName ? `إعلانات ${catName}` : "تصفح الإعلانات") + " — " + C.SITE_NAME;
  }

  function renderPager(meta, p) {
    if (!meta || meta.last_page <= 1) { pager.innerHTML = ""; return; }
    const cur = meta.current_page, last = meta.last_page;
    const pages = new Set([1, last, cur - 1, cur, cur + 1].filter((n) => n >= 1 && n <= last));
    const sorted = [...pages].sort((a, b) => a - b);
    let html = `<button data-p="${cur - 1}" ${cur === 1 ? "disabled" : ""} aria-label="السابق">${S.icon("chevR")}</button>`;
    sorted.forEach((n, i) => {
      if (i && n - sorted[i - 1] > 1) html += `<button disabled>…</button>`;
      html += `<button data-p="${n}" class="${n === cur ? "on" : ""}">${n}</button>`;
    });
    html += `<button data-p="${cur + 1}" ${cur === last ? "disabled" : ""} aria-label="التالي">${S.icon("chevL")}</button>`;
    pager.innerHTML = html;
    pager.querySelectorAll("button[data-p]:not([disabled])").forEach((b) => b.onclick = () => {
      const q = params(); q.set("page", b.dataset.p); navigate(q); window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }

  let reqId = 0;
  async function run() {
    const p = params();
    if (p.get("category_id") !== (form.dataset.cat || null)) {
      form.dataset.cat = p.get("category_id") || "";
      await loadAttrFilters(p.get("category_id"));
    }
    syncForm(p);
    renderCatChips(p);
    renderActiveChips(p);

    const q = {};
    BASE_KEYS.forEach((k) => { if (p.get(k)) q[k] = p.get(k); });
    attrDefs.forEach((a) => { if (p.get(a.key)) q[a.key] = p.get(a.key); });
    if (q.sort === "nearest" && !q.lat) delete q.sort;

    const my = ++reqId;
    results.innerHTML = S.skeletonCards(9);
    pager.innerHTML = "";
    try {
      const res = await S.api("/ads", q);
      if (my !== reqId) return;
      const total = res.meta?.total ?? res.data.length;
      document.getElementById("count").textContent = `${new Intl.NumberFormat("en-US").format(total)} إعلان`;
      results.innerHTML = res.data.length ? res.data.map(S.adCard).join("") : S.emptyBox("لا توجد نتائج مطابقة", "جرّب تغيير كلمات البحث أو إزالة بعض الفلاتر.");
      renderPager(res.meta, p);
    } catch (e) {
      if (my !== reqId) return;
      document.getElementById("count").textContent = "";
      results.innerHTML = S.errorBox();
    }
  }

  window.addEventListener("popstate", run);

  S.api("/categories").then(({ data }) => {
    categories = data || [];
    const sel = document.getElementById("f-cat");
    categories.forEach((c) => sel.add(new Option(c.name_ar, c.id)));
  }).catch(() => {}).finally(run);
})();
