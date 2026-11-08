(function () {
  const S = window.Simsar, t = S.t;
  S.renderHeader("home");
  S.renderFooter();
  S.applyI18n();
  S.setMeta(t("title_home"), t("meta_desc"));

  const h = new Date().getHours();
  document.getElementById("greet").textContent = t(h < 12 ? "greet_morning" : h < 18 ? "greet_afternoon" : "greet_evening");
  document.getElementById("search-ico").outerHTML = S.icon("search");
  document.getElementById("hero-logo").innerHTML = S.logos("hero-logo");
  document.getElementById("download-buttons").innerHTML = S.storeButtons();

  document.getElementById("hero-search").addEventListener("submit", (e) => {
    e.preventDefault();
    const q = e.target.q.value.trim();
    location.href = "ads.html" + (q ? "?q=" + encodeURIComponent(q) : "");
  });

  // ---- App features ----
  const features = [["search", "f_search"], ["pin", "f_near"], ["chat", "f_chat"], ["heart", "f_fav"], ["star", "f_rate"],
    ["bell", "f_notif"], ["bolt", "f_feat"], ["shield", "f_review"], ["plus", "f_post"]];
  document.getElementById("features-grid").innerHTML = features
    .map(([i, k]) => `<div class="feature"><div class="f-ico">${S.icon(i)}</div><h3>${S.esc(t(k))}</h3><p>${S.esc(t(k + "_d"))}</p></div>`).join("");

  // ---- Category tiles with listing counts (like the app) ----
  const catsEl = document.getElementById("cats");
  catsEl.innerHTML = '<div class="cat sk-box" style="height:72px"></div>'.repeat(3);
  let catNames = {};
  S.categories().then((cats) => {
    catNames = Object.fromEntries(cats.map((c) => [c.id, S.nameOf(c)]));
    if (!cats.length) { catsEl.innerHTML = ""; return; }
    catsEl.innerHTML = cats.map((c) => `<a class="cat" href="ads.html?category_id=${c.id}">
      <span class="cat-ico">${S.categoryVisual(c)}</span><span>${S.esc(S.nameOf(c))}<small data-count="${c.id}">&nbsp;</small></span></a>`).join("");
    if (cats.length <= 8) cats.forEach((c) => S.api("/ads", { category_id: c.id }).then((r) => {
      const el = catsEl.querySelector(`[data-count="${c.id}"]`);
      if (el && r.meta) el.textContent = S.num.format(r.meta.total);
    }).catch(() => {}));
  });

  // ---- Banners ----
  S.api("/banners").then(({ data }) => {
    if (!data || !data.length) return;
    const el = document.getElementById("banners");
    el.innerHTML = data.map((b, i) => {
      const link = b.link_url ? S.safeUrl(b.link_url) : "";
      const img = `<img src="${S.esc(b.image_url)}" alt="${S.esc(b.title || "")}">`;
      return `<div class="banner-slide ${i === 0 ? "on" : ""}">${link ? `<a href="${S.esc(link)}" target="_blank" rel="noopener">${img}</a>` : img}</div>`;
    }).join("") + `<span class="sponsored">${S.esc(t("sponsored"))}</span>` +
      (data.length > 1 ? `<div class="banner-dots">${data.map((_, i) => `<button aria-label="${i + 1}" class="${i === 0 ? "on" : ""}"></button>`).join("")}</div>` : "");
    document.getElementById("banners-section").hidden = false;
    if (data.length > 1) {
      let cur = 0;
      const slides = el.querySelectorAll(".banner-slide"), dots = el.querySelectorAll(".banner-dots button");
      const go = (n) => { slides[cur].classList.remove("on"); dots[cur].classList.remove("on"); cur = (n + slides.length) % slides.length; slides[cur].classList.add("on"); dots[cur].classList.add("on"); };
      dots.forEach((d, i) => d.addEventListener("click", () => go(i)));
      setInterval(() => go(cur + 1), 5000);
    }
  }).catch(() => {});

  // ---- Featured + latest (featured come first in the default order) ----
  const latest = document.getElementById("latest");
  latest.innerHTML = S.skeletonCards(8);
  Promise.all([S.api("/ads"), S.categories()]).then(([res]) => {
    const ads = res.data || [];
    const featured = ads.filter((a) => a.is_featured);
    if (featured.length) {
      document.getElementById("featured").innerHTML = featured.slice(0, 8).map((a) => S.adCard(a, catNames)).join("");
      document.getElementById("featured-section").hidden = false;
    }
    const newest = [...ads].sort((a, b) => new Date(b.published_at || b.created_at) - new Date(a.published_at || a.created_at));
    latest.innerHTML = newest.length ? newest.slice(0, 8).map((a) => S.adCard(a, catNames)).join("") : S.emptyBox(t("no_ads"), t("no_ads_sub"));
  }).catch(() => { latest.innerHTML = S.errorBox(); });

  // ---- Popular ----
  S.api("/ads", { sort: "popular" }).then(({ data }) => {
    const withImg = (data || []).filter((a) => S.cover(a));
    if (withImg.length < 3) return;
    document.getElementById("popular").innerHTML = withImg.slice(0, 10).map(S.miniCard).join("");
    document.getElementById("popular-section").hidden = false;
  }).catch(() => {});
})();
