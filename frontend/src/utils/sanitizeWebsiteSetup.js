/**
 * website-setup yanıtından istemciye gitmemesi gereken alanları temizler.
 */
export function sanitizePusherInfo(pusher) {
  if (!pusher || typeof pusher !== "object") {
    return null;
  }

  return {
    app_key: pusher.app_key ?? null,
    app_cluster: pusher.app_cluster ?? null,
  };
}

export function sanitizeWebsiteSetup(data) {
  if (!data || typeof data !== "object") {
    return data;
  }

  const setting = data.setting && typeof data.setting === "object"
    ? {
        ...data.setting,
        map_key: null,
        bank_transfer_info: null,
      }
    : data.setting;

  return {
    ...data,
    setting,
    googleAnalytic: null,
    facebookPixel: null,
    tawk_setting: null,
    pusher_info: sanitizePusherInfo(data.pusher_info),
  };
}
