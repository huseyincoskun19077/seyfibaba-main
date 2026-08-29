export default function languageModel() {
  if (typeof window !== "undefined") {
    // Try primary key first, then fallback key
    const raw = localStorage.getItem("language") || localStorage.getItem("shopoDefaultLanguage");
    if (raw && raw !== "{}" && raw !== "null") {
      try {
        const prevObj = JSON.parse(raw);
        if (!prevObj || typeof prevObj !== "object" || Object.keys(prevObj).length === 0) {
          return false;
        }
        const keys = Object.keys(prevObj).map((item) =>
          item
            .replaceAll("-", " ")
            .replaceAll(",", " ")
            .replaceAll(".", " ")
            .replaceAll("'", "")
            .replaceAll("!", "")
            .replaceAll("?", "")
            .split(" ")
            .join("_")
        );

        const values = Object.values(prevObj);
        const generateNewArr = values.map((item, i) => {
          let newObj = {};
          newObj[keys[i]] = item;
          return newObj;
        });
        return Object.assign.apply(Object, generateNewArr);
      } catch (e) {
        return false;
      }
    }
    return false;
  }
  return false;
}
