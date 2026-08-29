import hexToRgb from "@/utils/hexToRgb";
import { STORAGE_KEYS } from "@/utils/layoutConstants";
import { sanitizePusherInfo } from "@/utils/sanitizeWebsiteSetup";

export function getDefaultCurrency(currencies) {
  return (
    currencies?.find((item) => item.is_default?.toLowerCase() === "yes") || {}
  );
}

export function applyThemeColors(themeSettings) {
  if (typeof window === "undefined") return;
  if (!themeSettings?.theme_one || !themeSettings?.theme_two) return;

  const root = document.documentElement;
  root.style.setProperty("--primary-color", hexToRgb(themeSettings.theme_one));
  root.style.setProperty(
    "--secondary-color",
    hexToRgb(themeSettings.theme_two)
  );
}

export function seedLanguageStorage(language) {
  if (typeof window === "undefined" || !language) return;

  const currentLanguage = localStorage.getItem(STORAGE_KEYS.LEGACY_LANGUAGE);
  if (!currentLanguage || currentLanguage === "{}") {
    const serializedLanguage = JSON.stringify(language);
    localStorage.setItem(STORAGE_KEYS.LEGACY_LANGUAGE, serializedLanguage);
    localStorage.setItem(STORAGE_KEYS.LANGUAGE, serializedLanguage);
  }
}

export function persistWebsiteSetupStorage(data) {
  if (typeof window === "undefined" || !data) return;

  const { currencies, setting, pusher_info, language } = data;
  const serializedLanguage = JSON.stringify(language || {});

  if (!localStorage.getItem(STORAGE_KEYS.CURRENCY)) {
    localStorage.setItem(
      STORAGE_KEYS.CURRENCY,
      JSON.stringify(getDefaultCurrency(currencies))
    );
  }

  localStorage.setItem(STORAGE_KEYS.SETTINGS, JSON.stringify(setting || null));
  localStorage.setItem(
    STORAGE_KEYS.PUSHER,
    JSON.stringify(sanitizePusherInfo(pusher_info))
  );
  localStorage.setItem(STORAGE_KEYS.LEGACY_LANGUAGE, serializedLanguage);
  localStorage.setItem(STORAGE_KEYS.LANGUAGE, serializedLanguage);

  applyThemeColors(setting);
}
