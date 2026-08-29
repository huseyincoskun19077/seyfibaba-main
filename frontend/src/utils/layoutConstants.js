// ===================== STORAGE KEYS =====================
export const STORAGE_KEYS = {
  CURRENCY: "shopoDefaultCurrency",
  LANGUAGE: "shopoDefaultLanguage",
  LEGACY_LANGUAGE: "language",
  SETTINGS: "settings",
  PUSHER: "pusher",
  THEME: "shopoTheme",
  USER_PREFERENCES: "shopoUserPreferences",
};

// ===================== TIMING CONSTANTS =====================
export const TIMING = {
  CURRENCY_CHANGE_DELAY: 500,
  ERROR_DISPLAY_DURATION: 5000,
  LOADING_TIMEOUT: 10000,
  DEBOUNCE_DELAY: 300,
};

// ===================== ERROR MESSAGES =====================
export const ERROR_MESSAGES = {
  WEBSITE_SETUP_FAILED: "Failed to load website configuration",
  CURRENCY_LOAD_FAILED: "Failed to load saved currency",
  CURRENCY_UPDATE_FAILED: "Failed to update currency",
  SUBSCRIPTION_LOAD_FAILED: "Failed to load subscription banner",
  NETWORK_ERROR: "Network error occurred",
  UNKNOWN_ERROR: "An unexpected error occurred",
};

// ===================== CSS CLASSES =====================
export const CSS_CLASSES = {
  LAYOUT_CONTAINER: "w-full overflow-x-hidden",
  MAIN_CONTENT: "w-full min-h-screen",
  DEFAULT_PADDING: "pt-[30px] pb-[60px]",
  ERROR_TOAST:
    "fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded shadow-lg z-50 max-w-sm",
  LOADING_TOAST: "fixed top-4 left-4 text-black text-sm z-50",
  SUCCESS_TOAST:
    "fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50 max-w-sm",
};
