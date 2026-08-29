import { useEffect, useState, useCallback } from "react";
import { STORAGE_KEYS, TIMING, ERROR_MESSAGES } from "../utils/layoutConstants";

/**
 * Custom hook to manage website setup data
 *
 * @param {Object} websiteSetup - Website setup data from Redux store
 * @returns {Object} Object containing settings, contact, languages, and currencies
 */
export const useWebsiteSetup = (websiteSetup) => {
  const [settings, setSettings] = useState(null);
  const [contact, setContact] = useState(null);
  const [languages, setLanguages] = useState(null);
  const [defaultLanguage, setDefaultLanguage] = useState(null);
  const [allCurrencies, setAllCurrencies] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (websiteSetup?.payload) {
      try {
        const {
          setting,
          language_list,
          default_language,
          footer,
          currencies,
          language,
        } =
          websiteSetup.payload;

        setSettings(setting);
        setLanguages(language_list);
        setDefaultLanguage(default_language);
        setAllCurrencies(currencies);

        if (typeof window !== "undefined" && language) {
          localStorage.setItem(STORAGE_KEYS.LEGACY_LANGUAGE, JSON.stringify(language));
          localStorage.setItem(STORAGE_KEYS.LANGUAGE, JSON.stringify(language));
        }

        setContact({
          phone: footer?.phone || null,
          email: footer?.email || null,
        });

        setError(null);
      } catch (err) {
        // silently handle setup processing errors
        setError(ERROR_MESSAGES.WEBSITE_SETUP_FAILED);
      }
    }
  }, [websiteSetup]);

  return {
    settings,
    contact,
    languages,
    defaultLanguage,
    allCurrencies,
    error,
    setError,
  };
};

/**
 * Custom hook to manage currency state and operations
 *
 * @param {string} storageKey - LocalStorage key for currency persistence
 * @param {number} changeDelay - Delay before page reload after currency change
 * @returns {Object} Object containing currency state and handlers
 */
export const useCurrencyManagement = (
  storageKey = STORAGE_KEYS.CURRENCY,
  changeDelay = TIMING.CURRENCY_CHANGE_DELAY
) => {
  const [defaultCurrency, setDefaultCurrency] = useState(null);
  const [toggleCurrency, setToggleCurrency] = useState(false);
  const [error, setError] = useState(null);

  // Load default currency from localStorage
  useEffect(() => {
    if (!defaultCurrency) {
      try {
        const storedCurrency = localStorage.getItem(storageKey);
        if (storedCurrency) {
          setDefaultCurrency(JSON.parse(storedCurrency));
        }
      } catch (err) {
        // silently handle currency load error
        localStorage.removeItem(storageKey);
        setError(ERROR_MESSAGES.CURRENCY_LOAD_FAILED);
      }
    }
  }, [defaultCurrency, storageKey]);

  // Currency change handler
  const handleCurrencyChange = useCallback(
    (newCurrency) => {
      try {
        localStorage.setItem(storageKey, JSON.stringify(newCurrency));
        setToggleCurrency(false);
        setError(null);

        // Reload page after a short delay to apply currency changes
        setTimeout(() => {
          window.location.reload();
        }, changeDelay);
      } catch (err) {
        // silently handle currency save error
        setError(ERROR_MESSAGES.CURRENCY_UPDATE_FAILED);
      }
    },
    [storageKey, changeDelay]
  );

  return {
    defaultCurrency,
    setDefaultCurrency,
    toggleCurrency,
    setToggleCurrency,
    handleCurrencyChange,
    error,
    setError,
  };
};

export const useSubscriptionBanner = (websiteSetup) => {
  const [subscribeData, setSubscribeData] = useState(null);

  useEffect(() => {
    // Only load subscription banner if feature is enabled
    if (!subscribeData && websiteSetup?.payload?.subscriptionBanner) {
      setSubscribeData(websiteSetup?.payload?.subscriptionBanner);
    }
  }, [subscribeData, websiteSetup]);

  return {
    subscribeData,
    setSubscribeData,
  };
};

/**
 * Custom hook to manage mobile drawer state
 *
 * @returns {Object} Object containing drawer state and toggle handler
 */
export const useDrawer = () => {
  const [drawer, setDrawer] = useState(false);

  const handleDrawerToggle = useCallback(() => {
    setDrawer((prev) => !prev);
  }, []);

  return {
    drawer,
    setDrawer,
    handleDrawerToggle,
  };
};
