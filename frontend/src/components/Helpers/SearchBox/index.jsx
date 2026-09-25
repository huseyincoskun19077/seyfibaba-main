import { useState, useEffect, useRef, useCallback } from "react";
import { useSelector } from "react-redux";
import { useRouter } from "next/navigation";
import Link from "next/link";
import ServeLangItem from "../ServeLangItem";
import ArrowDownIcoCheck from "../icons/ArrowDownIcoCheck";
import appConfig from "@/appConfig";
import { resolveProductImageUrl } from "@/utils/productImage";
import { buildProductPath } from "@/utils/url";
import { formatMoneyTR } from "@/utils/priceFormat";

export default function SearchBox({ className }) {
  const router = useRouter();
  const [toggleCat, setToggleCat] = useState(false);
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const [categories, setCategories] = useState(null);
  const [selectedCat, setSelectedCat] = useState(null);
  const [searchKey, setSearchkey] = useState("");
  const [suggestions, setSuggestions] = useState([]);
  const [showSuggestions, setShowSuggestions] = useState(false);
  const debounceRef = useRef(null);
  const searchBoxRef = useRef(null);

  const fetchSuggestions = useCallback(async (term) => {
    if (!term || term.length < 2) {
      setSuggestions([]);
      setShowSuggestions(false);
      return;
    }
    try {
      const res = await fetch(`${appConfig.BASE_URL}api/search-product?search=${encodeURIComponent(term)}&limit=5`);
      const data = await res.json();
      const items = data?.products?.data || data?.products || [];
      setSuggestions(Array.isArray(items) ? items.slice(0, 5) : []);
      setShowSuggestions(true);
    } catch {
      setSuggestions([]);
    }
  }, []);

  const handleSearchInput = (value) => {
    setSearchkey(value);
    if (debounceRef.current) clearTimeout(debounceRef.current);
    debounceRef.current = setTimeout(() => fetchSuggestions(value), 300);
  };

  useEffect(() => {
    const handler = (e) => {
      if (searchBoxRef.current && !searchBoxRef.current.contains(e.target)) {
        setShowSuggestions(false);
      }
    };
    document.addEventListener("mousedown", handler);
    return () => document.removeEventListener("mousedown", handler);
  }, []);
  useEffect(() => {
    if (router && router.route && router.route === "/search") {
      setSearchkey(router.query ? router.query.search : "");
    }
    return () => {
      setSearchkey("");
    };
  }, [router]);
  const categoryHandler = (value) => {
    setSelectedCat(value);
    setToggleCat(!toggleCat);
  };
  useEffect(() => {
    if (websiteSetup) {
      setCategories(
        websiteSetup.payload && websiteSetup.payload.productCategories
      );
    }
  }, [websiteSetup]);
  const searchHandler = () => {
    setShowSuggestions(false);
    if (searchKey !== "") {
      if (selectedCat) {
        router.push(`/search?search=${searchKey}&category=${selectedCat.slug}`);
      } else {
        router.push(`/search?search=${searchKey}`);
      }
    } else if (searchKey === "" && selectedCat) {
      router.push(`/products?category=${selectedCat.slug}`);
    } else {
      router.push("/search");
    }
  };

  return (
    <div
      ref={searchBoxRef}
      className={`w-full h-full flex items-center border-2 border-[#04334a]/15 bg-white relative overflow-hidden rounded-xl focus-within:border-qyellow focus-within:shadow-[0_0_0_3px_rgba(255,187,56,0.25)] transition-all ${
        className || ""
      }`}
    >
      <div className="flex-[1.4] h-full min-w-0">
        <div className="h-full">
          <input
            value={searchKey}
            onKeyDown={(e) => e.key === "Enter" && searchHandler()}
            onChange={(e) => handleSearchInput(e.target.value)}
            type="text"
            className="search-input"
            placeholder={ServeLangItem()?.Search_products + "..."}
          />
        </div>
      </div>
      <div className="w-px h-6 bg-qblack/10 shrink-0"></div>
      <div className="w-[140px] shrink-0 flex items-center px-3 relative">
        <button
          onClick={() => setToggleCat(!toggleCat)}
          type="button"
          className="w-full text-xs font-500 text-qblack/70 flex justify-between items-center capitalize gap-1"
        >
          <span className="line-clamp-1 text-left">
            {selectedCat ? selectedCat.name : ServeLangItem()?.category}
          </span>
          <span className="shrink-0">
            <ArrowDownIcoCheck fill="#8E8E8E" />
          </span>
        </button>
        {toggleCat && (
          <>
            <div
              className="w-full h-full fixed left-0 top-0 z-50"
              onClick={() => setToggleCat(!toggleCat)}
            ></div>
            <div
              className="w-[227px] h-auto absolute bg-white left-0 top-[32px] z-50 p-4 rounded-xl border border-qblack/10"
              style={{ boxShadow: "0px 12px 40px 0px rgba(0, 0, 0, 0.12)" }}
            >
              <ul className="flex flex-col space-y-2">
                {categories &&
                  categories.map((item, i) => (
                    <li onClick={() => categoryHandler(item)} key={i}>
                      <span className="text-qblack/70 text-sm font-400 border-b border-transparent hover:border-qyellow hover:text-qblack cursor-pointer">
                        {item.name}
                      </span>
                    </li>
                  ))}
              </ul>
            </div>
          </>
        )}
      </div>
      <button
        onClick={searchHandler}
        className="search-btn w-[96px] h-full text-sm font-700 shrink-0 hover:brightness-95 transition"
        type="button"
      >
        {ServeLangItem()?.Search}
      </button>

      {showSuggestions && suggestions.length > 0 && (
        <div className="absolute left-0 right-0 top-[calc(100%+4px)] z-50 bg-white border border-qblack/10 shadow-lg rounded-xl max-h-[300px] overflow-y-auto">
          {suggestions.map((item) => (
            <Link
              key={item.id}
              href={buildProductPath(item.slug)}
              onClick={() => { setShowSuggestions(false); setSearchkey(""); }}
              className="flex items-center gap-3 px-4 py-2.5 hover:bg-qyellow/20 transition-colors border-b border-qblack/5 last:border-0"
            >
              {item.thumb_image && (
                <img
                  src={resolveProductImageUrl(item.thumb_image)}
                  alt=""
                  className="w-10 h-10 object-cover rounded-lg"
                />
              )}
              <div className="flex-1 min-w-0">
                <p className="text-sm text-qblack truncate">{item.name}</p>
                <p className="text-xs text-qblack/50">{formatMoneyTR(item.price)}</p>
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
