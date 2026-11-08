/* Runs in <head> before the page paints: applies the saved language and
   light/dark theme so there is no flash of the wrong one. */
(function () {
  var d = document.documentElement, lang, theme;
  try { lang = localStorage.getItem("semsar_lang"); theme = localStorage.getItem("semsar_theme"); } catch (e) {}
  lang = lang === "en" || lang === "ar" ? lang : ((window.SIMSAR_CONFIG && window.SIMSAR_CONFIG.DEFAULT_LANG) || "ar");
  d.lang = lang;
  d.dir = lang === "ar" ? "rtl" : "ltr";
  var mq = window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)");
  var apply = function () {
    var t = theme === "dark" || theme === "light" ? theme : (mq && mq.matches ? "dark" : "light");
    d.setAttribute("data-theme", t);
  };
  apply();
  // follow the device setting until the visitor picks a theme themselves
  if (mq && mq.addEventListener) mq.addEventListener("change", function () { if (!theme) apply(); });
})();
