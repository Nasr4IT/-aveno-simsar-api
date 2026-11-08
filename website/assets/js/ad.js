(function () {
  const S = window.Simsar, t = S.t, I = S.icon, esc = S.esc;
  S.renderHeader("explore");
  S.renderFooter();
  S.applyI18n();

  const root = document.getElementById("ad-root");
  const id = new URLSearchParams(location.search).get("id");
  if (!id) { root.innerHTML = `<div style="padding:40px 0">${S.emptyBox(t("no_id"))}</div>`; return; }

  S.api("/ads/" + encodeURIComponent(id)).then(async ({ data: ad }) => {
    // English labels for specs come from the category definition
    let labels = {};
    if (ad.category?.id) {
      try {
        const { data } = await S.api("/categories/" + encodeURIComponent(ad.category.id));
        labels = Object.fromEntries((data.attributes || []).map((a) => [a.key, S.labelOf(a)]));
      } catch {}
    }
    const cats = await S.categories();
    const catName = S.nameOf(cats.find((c) => c.id === ad.category?.id) || ad.category);
    render(ad, labels, catName);
    loadSimilar(ad.id);
    if (ad.user?.id) loadRatings(ad.user.id);
  }).catch((e) => {
    root.innerHTML = e.status === 404
      ? `<div style="padding:40px 0">${S.emptyBox(t("not_found"), t("not_found_sub"))}<p style="text-align:center"><a class="btn btn-primary" href="ads.html">${esc(t("browse_others"))}</a></p></div>`
      : `<div style="padding:40px 0">${S.errorBox()}</div>`;
  });

  function fmtAttr(v) {
    if (v === "1" || v === true) return t("yes");
    if (v === "0" || v === false) return t("no");
    if (Array.isArray(v)) return v.map(S.tr).join(", ");
    try { const p = JSON.parse(v); if (Array.isArray(p)) return p.map(S.tr).join(", "); } catch {}
    return v == null ? "—" : S.tr(v);
  }

  function render(ad, labels, catName) {
    S.setMeta(`${ad.title} — ${S.BRAND}`, (ad.description || ad.title).slice(0, 160));
    const images = [...(ad.images || [])].sort((a, b) => (b.is_cover ? 1 : 0) - (a.is_cover ? 1 : 0));
    const place = S.placeOf(ad);
    const u = ad.user || {};
    const avatarUrl = u.avatar_url && S.safeUrl(u.avatar_url, ["http:", "https:"]);
    const since = u.created_at ? new Date(u.created_at).toLocaleDateString(S.LANG, { year: "numeric", month: "long" }) : "";
    const phone = S.CFG.SHOW_SELLER_PHONE && u.phone ? String(u.phone).replace(/[^\d+]/g, "") : "";
    const wa = phone ? phone.replace(/^\+/, "").replace(/^0/, "963") : "";
    const attrs = ad.attributes || [];
    const backHref = ad.category ? `ads.html?category_id=${encodeURIComponent(ad.category.id)}` : "ads.html";

    root.innerHTML = `
      <div class="detail">
        <div>
          <div class="gallery">
            <div class="gallery-main" id="gal">
              ${images.length ? `<img id="gal-img" src="${esc(images[0].url)}" alt="${esc(ad.title)}">` : `<div class="noimg">${I("image")}</div>`}
              <div class="gal-top">
                <a class="round-btn back" href="${esc(backHref)}" aria-label="${esc(t("back"))}" title="${esc(catName)}">${I("arrowL")}</a>
                <div class="group">
                  <button class="round-btn" id="share" aria-label="${esc(t("share"))}">${I("share")}</button>
                  <button class="round-btn" data-app-only="app_save" aria-label="${esc(t("save"))}">${I("heart")}</button>
                </div>
              </div>
              ${images.length > 1 ? `<button class="round-btn gal-nav gal-prev" aria-label="${esc(t("prev"))}">${I("chevL")}</button>
                <button class="round-btn gal-nav gal-next" aria-label="${esc(t("next"))}">${I("chevR")}</button>
                <span class="gal-count" id="gal-count">1 / ${images.length}</span>` : ""}
            </div>
            ${images.length > 1 ? `<div class="thumbs" id="thumbs">${images.map((im, i) => `<button class="${i === 0 ? "on" : ""}" data-i="${i}" aria-label="${i + 1}"><img src="${esc(im.url)}" alt="" loading="lazy"></button>`).join("")}</div>` : ""}
          </div>

          <h1 class="detail-title">${esc(ad.title)}</h1>
          <div class="detail-price">${S.price(ad.price, ad.currency)}</div>
          <div class="pills">
            ${ad.status === "sold" ? `<span class="pill sold">${esc(t("sold"))}</span>` : ""}
            ${ad.is_featured ? `<span class="pill">${I("bolt")}${esc(t("featured_badge"))}</span>` : ""}
            ${place ? `<span class="pill">${I("pin")}${place}</span>` : ""}
            <span class="pill">${I("clock")}${esc(S.ago(ad.published_at || ad.created_at))}</span>
            <span class="pill">${I("eye")}${esc(t("views_n", { n: S.num.format(ad.views_count || 0) }))}</span>
          </div>

          <div class="block">
            <h2>${esc(t("details"))}</h2>
            <dl class="kv">
              ${catName ? `<dt>${esc(t("f_category"))}</dt><dd>${esc(catName)}</dd>` : ""}
              ${attrs.map((a) => `<dt>${esc(labels[a.key] || S.labelOf(a))}</dt><dd>${esc(fmtAttr(a.value))}</dd>`).join("")}
              <dt>${esc(t("listing_id"))}</dt><dd class="latin">#${esc(ad.id)}</dd>
            </dl>
          </div>

          ${ad.description ? `<div class="block"><h2>${esc(t("description"))}</h2><p class="desc">${esc(ad.description)}</p></div>` : ""}
          <div class="block" id="reviews-block" hidden><h2>${esc(t("seller_reviews"))}</h2><div id="reviews"></div></div>
        </div>

        <aside class="sticky-side">
          <div class="card">
            <div class="seller">
              <div class="avatar">${avatarUrl ? `<img src="${esc(avatarUrl)}" alt="">` : esc((u.name || "?").trim().charAt(0))}</div>
              <div>
                <h3>${esc(u.name || t("seller"))}</h3>
                <div>${S.stars(u.rating_average)} <small class="muted">${esc(t("ratings_n", { n: u.rating_count || 0 }))}</small></div>
                ${since ? `<small class="muted">${esc(t("member_since", { d: since }))}</small>` : ""}
              </div>
            </div>
            <div class="actions">
              <div class="side-actions actions">
                <button class="btn btn-primary btn-block" data-app-only="app_chat">${I("chat")} ${esc(t("message_seller"))}</button>
                <button class="btn btn-outline btn-block" data-app-only="app_save">${I("heart")} ${esc(t("save_listing"))}</button>
              </div>
              ${phone ? `<div id="phone-slot"><button class="btn btn-outline btn-block" id="show-phone">${I("phone")} ${esc(t("show_phone"))}</button></div>` : ""}
              <button class="btn btn-ghost btn-sm" data-app-only="app_report">${esc(t("report"))}</button>
            </div>
          </div>
          <div class="tip">${esc(t("safety"))}</div>
          <div class="card" style="margin-top:16px;text-align:center">
            ${S.logos("logo app-logo")}
            <h2 style="margin-top:8px">${esc(t("better_in_app"))}</h2>
            <p class="muted" style="margin-top:0">${esc(t("better_in_app_d"))}</p>
            <div class="store-row" style="justify-content:center">${S.storeButtons()}</div>
          </div>
        </aside>
      </div>
      <div class="mobile-cta">
        <button class="btn btn-outline" data-app-only="app_save">${I("heart")} ${esc(t("save_listing"))}</button>
        <button class="btn btn-primary" data-app-only="app_chat">${I("chat")} ${esc(t("message_seller"))}</button>
      </div>`;
    document.body.classList.add("has-cta");

    // gallery
    if (images.length > 1) {
      let cur = 0;
      const img = document.getElementById("gal-img"), count = document.getElementById("gal-count");
      const thumbs = [...document.querySelectorAll("#thumbs button")];
      const show = (n) => {
        cur = (n + images.length) % images.length;
        img.src = images[cur].url;
        count.textContent = `${cur + 1} / ${images.length}`;
        thumbs.forEach((b, i) => b.classList.toggle("on", i === cur));
      };
      root.querySelector(".gal-prev").onclick = () => show(cur - 1);
      root.querySelector(".gal-next").onclick = () => show(cur + 1);
      thumbs.forEach((b) => b.onclick = () => show(+b.dataset.i));
      const rtl = document.documentElement.dir === "rtl";
      let x0 = null;
      const gal = document.getElementById("gal");
      gal.addEventListener("touchstart", (e) => { x0 = e.touches[0].clientX; }, { passive: true });
      gal.addEventListener("touchend", (e) => {
        if (x0 === null) return;
        const dx = e.changedTouches[0].clientX - x0;
        if (Math.abs(dx) > 40) show(cur + ((dx < 0) !== rtl ? 1 : -1));
        x0 = null;
      });
    }

    // phone reveal
    const sp = document.getElementById("show-phone");
    if (sp) sp.onclick = () => {
      const msg = t("wa_msg", { t: ad.title, u: location.href });
      document.getElementById("phone-slot").innerHTML = `<div class="row actions" style="grid-template-columns:1fr 1fr">
        <a class="btn btn-outline" href="tel:${esc(phone)}"><bdi dir="ltr">${esc(u.phone)}</bdi></a>
        <a class="btn btn-outline" href="https://wa.me/${esc(wa)}?text=${encodeURIComponent(msg)}" target="_blank" rel="noopener">${I("chat")} ${esc(t("whatsapp"))}</a></div>`;
    };

    // share
    document.getElementById("share").onclick = async () => {
      if (navigator.share) { try { await navigator.share({ title: ad.title, url: location.href }); } catch {} return; }
      try { await navigator.clipboard.writeText(location.href); S.toast(t("link_copied")); } catch { prompt(t("copy_link"), location.href); }
    };
  }

  function loadSimilar(adId) {
    Promise.all([S.api(`/ads/${encodeURIComponent(adId)}/similar`), S.categories()]).then(([{ data }, cats]) => {
      if (!data || !data.length) return;
      const names = Object.fromEntries(cats.map((c) => [c.id, S.nameOf(c)]));
      document.getElementById("similar").innerHTML = data.slice(0, 8).map((a) => S.adCard(a, names)).join("");
      document.getElementById("similar-section").hidden = false;
    }).catch(() => {});
  }

  function loadRatings(userId) {
    S.api(`/users/${encodeURIComponent(userId)}/ratings`).then(({ data }) => {
      if (!data || !data.length) return;
      document.getElementById("reviews").innerHTML = data.slice(0, 5).map((r) => `<div class="review">
        <div class="review-head"><span>${esc(r.rater?.name || t("user"))}</span>${S.stars(r.score)}</div>
        ${r.comment ? `<p>${esc(r.comment)}</p>` : ""}
        <small class="muted">${esc(S.ago(r.created_at))}</small></div>`).join("");
      document.getElementById("reviews-block").hidden = false;
    }).catch(() => {});
  }
})();
