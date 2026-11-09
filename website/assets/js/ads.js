(function () {
  const S = window.Simsar, t = S.t, esc = S.esc;
  S.renderHeader("explore");
  S.renderFooter();
  S.applyI18n();
  S.setMeta(t("title_explore") + " — " + S.BRAND, t("meta_desc"));

  const BASE_KEYS = ["q", "category_id", "governorate_id", "city_id", "min_price", "max_price", "sort", "page", "lat", "lng"];
  const form = document.getElementById("filter-form");
  const results = document.getElementById("results");
  const pager = document.getElementById("pager");
  const sortSel = document.getElementById("sort");
  const filtersEl = document.getElementById("filters");
  const backdrop = document.getElementById("backdrop");
  const govSel = document.getElementById("f-gov"), citySel = document.getElementById("f-city"), locSel = document.getElementById("loc");
  let categories = [], catNames = {}, govs = [], attrDefs = [], loadedCat = null;

  document.getElementById("filters-open").innerHTML = S.icon("sliders");
  document.getElementById("pin-ico").outerHTML = S.icon("pin");
  document.getElementById("chev-ico").outerHTML = S.icon("chevD");

  const openSheet = (o) => { filtersEl.classList.toggle("open", o); backdrop.hidden = !o; };
  document.getElementById("filters-open").onclick = () => openSheet(true);
  document.getElementById("filters-close").onclick = () => openSheet(false);
  backdrop.onclick = () => openSheet(false);

  const params = () => new URLSearchParams(location.search);

  function fillCities(govId) {
    const cities = govs.find((g) => String(g.id) === String(govId))?.cities || [];
    citySel.length = 1;
    cities.forEach((c) => citySel.add(new Option(c.name, c.id)));
    document.getElementById("city-field").hidden = cities.length === 0;
  }
  govSel.addEventListener("change", () => fillCities(govSel.value));

  async function loadAttrFilters(catId) {
    const box = document.getElementById("attr-filters");
    attrDefs = []; box.innerHTML = "";
    if (!catId) return;
    try {
      const { data } = await S.api("/categories/" + encodeURIComponent(catId));
      attrDefs = (data.attributes || []).filter((a) => ["select", "multiselect", "boolean"].includes(a.type));
      box.innerHTML = attrDefs.map((a) => {
        const opts = a.type === "boolean" ? [["1", t("yes")], ["0", t("no")]] : (a.options || []).map((o) => [o, S.tr(o)]);
        return `<div class="field"><label>${esc(S.labelOf(a))}</label>
          <select name="attr_${esc(a.key)}"><option value="">${esc(t("any"))}</option>${opts.map(([v, l]) => `<option value="${esc(v)}">${esc(l)}</option>`).join("")}</select></div>`;
      }).join("");
    } catch { /* optional */ }
  }

  function syncForm(p) {
    form.q.value = p.get("q") || "";
    form.category_id.value = p.get("category_id") || "";
    govSel.value = p.get("governorate_id") || "";
    locSel.value = p.get("governorate_id") || "";
    fillCities(govSel.value);
    citySel.value = p.get("city_id") || "";
    form.min_price.value = p.get("min_price") || "";
    form.max_price.value = p.get("max_price") || "";
    sortSel.value = p.get("sort") || "";
    attrDefs.forEach((a) => { const el = form.elements["attr_" + a.key]; if (el) el.value = p.get(a.key) || ""; });
  }

  function navigate(p) {
    p.delete("page_reset");
    history.pushState(null, "", "ads.html" + (p.toString() ? "?" + p : ""));
    run();
  }

  function formToParams() {
    const p = new URLSearchParams();
    ["q", "category_id", "governorate_id", "city_id", "min_price", "max_price"].forEach((k) => { const v = (form.elements[k]?.value || "").trim(); if (v) p.set(k, v); });
    attrDefs.forEach((a) => { const v = form.elements["attr_" + a.key]?.value; if (v) p.set(a.key, v); });
    const cur = params();
    if (sortSel.value) p.set("sort", sortSel.value);
    if (sortSel.value === "nearest" && cur.get("lat")) { p.set("lat", cur.get("lat")); p.set("lng", cur.get("lng")); }
    return p;
  }

  form.addEventListener("submit", (e) => { e.preventDefault(); openSheet(false); navigate(formToParams()); });
  form.category_id.addEventListener("change", () => loadAttrFilters(form.category_id.value));
  document.getElementById("reset").onclick = () => { openSheet(false); navigate(new URLSearchParams()); };

  locSel.addEventListener("change", () => {
    const p = params();
    locSel.value ? p.set("governorate_id", locSel.value) : p.delete("governorate_id");
    p.delete("city_id"); p.delete("page");
    navigate(p);
  });

  sortSel.addEventListener("change", () => {
    if (sortSel.value !== "nearest") return navigate(formToParams());
    const revert = (msg) => { S.toast(t(msg)); sortSel.value = params().get("sort") || ""; };
    if (!navigator.geolocation) return revert("geo_unsupported");
    navigator.geolocation.getCurrentPosition(
      (pos) => { const p = formToParams(); p.set("lat", pos.coords.latitude.toFixed(5)); p.set("lng", pos.coords.longitude.toFixed(5)); navigate(p); },
      () => revert("geo_denied"), { timeout: 10000 });
  });

  function renderCatChips(p) {
    const cur = p.get("category_id") || "";
    const mk = (id, name) => {
      const q = new URLSearchParams(p); q.delete("page");
      attrDefs.forEach((a) => q.delete(a.key)); // attribute filters belong to the old category
      id ? q.set("category_id", id) : q.delete("category_id");
      return `<a class="chip ${String(cur) === String(id) ? "on" : ""}" href="ads.html${q.toString() ? "?" + q : ""}">${esc(name)}</a>`;
    };
    document.getElementById("cat-chips").innerHTML = mk("", t("all")) + categories.map((c) => mk(c.id, S.nameOf(c))).join("");
  }

  function renderActiveChips(p) {
    const labels = [];
    const gov = govs.find((g) => String(g.id) === p.get("governorate_id"));
    const city = gov?.cities.find((c) => String(c.id) === p.get("city_id"));
    if (p.get("q")) labels.push(["q", `“${p.get("q")}”`]);
    if (city) labels.push(["city_id", city.name]);
    if (p.get("min_price")) labels.push(["min_price", `${t("from")} ${p.get("min_price")}`]);
    if (p.get("max_price")) labels.push(["max_price", `${t("to")} ${p.get("max_price")}`]);
    attrDefs.forEach((a) => { if (p.get(a.key)) labels.push([a.key, `${S.labelOf(a)}: ${S.tr(p.get(a.key))}`]); });
    const el = document.getElementById("active-chips");
    el.innerHTML = labels.map(([k, l]) => `<button class="chip small on" data-k="${esc(k)}">${esc(l)} ×</button>`).join("");
    el.hidden = !labels.length;
    el.querySelectorAll("button").forEach((b) => b.onclick = () => { const q = params(); q.delete(b.dataset.k); q.delete("page"); navigate(q); });
    const catName = catNames[p.get("category_id")];
    document.getElementById("page-title").textContent = catName ? t("title_cat", { c: catName }) : t("explore");
    document.title = (catName ? t("title_cat", { c: catName }) : t("title_explore")) + " — " + S.BRAND;
  }

  function renderPager(meta) {
    if (!meta || meta.last_page <= 1) { pager.innerHTML = ""; return; }
    const cur = meta.current_page, last = meta.last_page;
    const pages = [...new Set([1, last, cur - 1, cur, cur + 1].filter((n) => n >= 1 && n <= last))].sort((a, b) => a - b);
    let html = `<button class="flip" data-p="${cur - 1}" ${cur === 1 ? "disabled" : ""} aria-label="${esc(t("prev"))}">${S.icon("chevL")}</button>`;
    pages.forEach((n, i) => {
      if (i && n - pages[i - 1] > 1) html += `<button disabled>…</button>`;
      html += `<button data-p="${n}" class="${n === cur ? "on" : ""}">${n}</button>`;
    });
    html += `<button class="flip" data-p="${cur + 1}" ${cur === last ? "disabled" : ""} aria-label="${esc(t("next"))}">${S.icon("chevR")}</button>`;
    pager.innerHTML = html;
    pager.querySelectorAll("button[data-p]:not([disabled])").forEach((b) => b.onclick = () => {
      const q = params(); q.set("page", b.dataset.p); navigate(q); window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }

  let reqId = 0;
  async function run() {
    const p = params();
    const cat = p.get("category_id") || "";
    if (cat !== loadedCat) { loadedCat = cat; await loadAttrFilters(cat); }
    syncForm(p);
    renderCatChips(p);
    renderActiveChips(p);

    const q = {};
    BASE_KEYS.forEach((k) => { if (p.get(k)) q[k] = p.get(k); });
    attrDefs.forEach((a) => { if (p.get(a.key)) q[a.key] = p.get(a.key); });
    if (q.sort === "nearest" && !q.lat) delete q.sort;

    const my = ++reqId;
    results.innerHTML = S.skeletonCards(8);
    pager.innerHTML = "";
    try {
      const res = await S.api("/ads", q);
      if (my !== reqId) return;
      document.getElementById("count").textContent = t("results_n", { n: S.num.format(res.meta?.total ?? res.data.length) });
      results.innerHTML = res.data.length ? res.data.map((a) => S.adCard(a, catNames)).join("") : S.emptyBox(t("no_results"), t("no_results_sub"));
      renderPager(res.meta);
    } catch {
      if (my !== reqId) return;
      document.getElementById("count").textContent = "";
      results.innerHTML = S.errorBox();
    }
  }
  window.addEventListener("popstate", run);

  Promise.all([
    S.categories().then((list) => {
      categories = list;
      catNames = Object.fromEntries(list.map((c) => [String(c.id), S.nameOf(c)]));
      const sel = document.getElementById("f-cat");
      list.forEach((c) => sel.add(new Option(S.nameOf(c), c.id)));
    }),
    S.governorates().then((list) => {
      govs = list;
      list.forEach((g) => { govSel.add(new Option(g.name, g.id)); locSel.add(new Option(g.name, g.id)); });
    })
  ]).finally(run);
})();
