/*
 * ====================================================================
 *  إعدادات موقع سمسار — Simsar website settings
 *  This is the ONLY file you normally need to edit before uploading.
 * ====================================================================
 */
window.SIMSAR_CONFIG = {
  // Address of the Laravel API (the same one the Flutter app uses), ending in /api
  // Example: "https://api.simsar.sy/api"
  API_BASE_URL: "http://localhost:8000/api",

  SITE_NAME: "سمسار",
  SITE_TAGLINE: "بيع واشترِ واستأجر سيارات وعقارات ودراجات بسهولة",

  // Download links for the mobile app. Leave "" to show "قريباً" (coming soon).
  APP_LINKS: {
    android: "",   // e.g. "https://play.google.com/store/apps/details?id=..."
    ios: "",       // e.g. "https://apps.apple.com/app/id..."
    apk: ""        // optional direct APK link
  },

  // Contact details shown in the footer (leave "" to hide a line)
  CONTACT: {
    phone: "",
    whatsapp: "",   // international format without +, e.g. "963999999999"
    email: "",
    facebook: "",
    instagram: ""
  },

  // The API has no endpoint that lists governorates, so they are listed here.
  // The ids MUST match the `governorates` table on your server
  // (these are the ids produced by GeoSeeder on a fresh database).
  GOVERNORATES: [
    { id: 1, name: "دمشق" },
    { id: 2, name: "ريف دمشق" },
    { id: 3, name: "حلب" },
    { id: 4, name: "حمص" },
    { id: 5, name: "حماة" },
    { id: 6, name: "اللاذقية" },
    { id: 7, name: "طرطوس" },
    { id: 8, name: "إدلب" },
    { id: 9, name: "الرقة" },
    { id: 10, name: "دير الزور" },
    { id: 11, name: "الحسكة" },
    { id: 12, name: "درعا" },
    { id: 13, name: "السويداء" },
    { id: 14, name: "القنيطرة" }
  ],

  // Show the seller's phone number (behind a "show number" button) on ad pages
  SHOW_SELLER_PHONE: true
};
