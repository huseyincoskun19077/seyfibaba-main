import { useEffect, useState } from "react";

/**
 * BFCache / geri-ileri navigasyonda bazı tarayıcılarda Next/Image boş kalabiliyor.
 * pageshow + persisted ile küçük bir key artırarak ilgili görselleri güvenli şekilde yeniden bağlar.
 */
export default function useBfCacheRemountKey() {
  const [key, setKey] = useState(0);

  useEffect(() => {
    const onPageShow = (event) => {
      if (event.persisted) {
        setKey((value) => value + 1);
      }
    };
    window.addEventListener("pageshow", onPageShow);
    return () => window.removeEventListener("pageshow", onPageShow);
  }, []);

  return key;
}
