import appConfig from "@/appConfig";

const normalizeUrlList = (urls = []) => {
  const host = new URL(appConfig.APPLICATION_URL).host;

  return [...new Set(
    urls
      .filter(Boolean)
      .map((item) => String(item).trim())
      .map((item) => {
        if (item.startsWith("http://") || item.startsWith("https://")) {
          return item;
        }

        return `${appConfig.APPLICATION_URL}${item.startsWith("/") ? "" : "/"}${item}`;
      })
      .filter((item) => {
        try {
          return new URL(item).host === host;
        } catch (error) {
          return false;
        }
      })
  )];
};

export const getIndexNowConfig = () => {
  const key = process.env.INDEXNOW_KEY || "";
  const endpoint = process.env.INDEXNOW_ENDPOINT || "https://api.indexnow.org/indexnow";
  const keyLocation =
    process.env.INDEXNOW_KEY_LOCATION ||
    `${appConfig.APPLICATION_URL}/indexnow/key`;

  return {
    key,
    endpoint,
    keyLocation,
    host: new URL(appConfig.APPLICATION_URL).host,
  };
};

export const submitIndexNowUrls = async (urls = []) => {
  const normalizedUrls = normalizeUrlList(urls);
  const config = getIndexNowConfig();

  if (!config.key) {
    return {
      ok: false,
      skipped: true,
      message: "INDEXNOW_KEY tanimli degil.",
      submittedUrls: normalizedUrls,
    };
  }

  if (!normalizedUrls.length) {
    return {
      ok: false,
      skipped: true,
      message: "Gonderilecek uygun URL bulunamadi.",
      submittedUrls: [],
    };
  }

  const response = await fetch(config.endpoint, {
    method: "POST",
    headers: {
      "Content-Type": "application/json; charset=utf-8",
    },
    body: JSON.stringify({
      host: config.host,
      key: config.key,
      keyLocation: config.keyLocation,
      urlList: normalizedUrls,
    }),
    cache: "no-store",
  });

  const responseText = await response.text();

  return {
    ok: response.ok,
    status: response.status,
    responseText,
    submittedUrls: normalizedUrls,
  };
};

