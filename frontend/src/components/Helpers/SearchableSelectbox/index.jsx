import { useState, useEffect, useRef } from "react";
import ServeLangItem from "../ServeLangItem";

export default function SearchableSelectbox({
  datas = [],
  defaultValue = "",
  className,
  action,
  children,
  placeholder = "",
}) {
  const [item, setItem] = useState({ name: defaultValue });
  const [toggle, setToggle] = useState(false);
  const [search, setSearch] = useState("");
  const inputRef = useRef(null);

  const handler = (e, value) => {
    if (action) {
      action(value);
    }
    setItem(value);
    setToggle(false);
    setSearch("");
  };

  useEffect(() => {
    if (defaultValue) {
      setItem({ name: defaultValue });
    } else {
      setItem({ name: "Seçiniz" });
    }
  }, [datas, defaultValue]);

  useEffect(() => {
    if (toggle && inputRef.current) {
      inputRef.current.focus();
    }
  }, [toggle]);

  const filtered =
    datas && datas.length > 0
      ? search
        ? datas.filter((v) =>
            v.name.toLocaleLowerCase("tr").includes(search.toLocaleLowerCase("tr"))
          )
        : datas
      : [];

  return (
    <div
      className={`my-select-box h-full flex items-center cursor-pointer relative ${
        className || ""
      }`}
    >
      <button
        type="button"
        className="my-select-box-btn w-full text-left"
        onClick={() => setToggle(!toggle)}
      >
        {children ? (
          children({ item: item && item.name })
        ) : (
          <span>{item && item.name}</span>
        )}
      </button>

      {toggle && (
        <div className="click-away" onClick={() => { setToggle(false); setSearch(""); }}></div>
      )}

      <div className={`my-select-box-section ${toggle ? "open" : ""}`}>
        {/* Search Input */}
        <div className="sticky top-0 bg-white p-2 border-b border-qgray-border z-10">
          <input
            ref={inputRef}
            type="text"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder={placeholder || ServeLangItem()?.Search || "Ara..."}
            className="w-full h-[36px] px-3 text-sm border border-qgray-border rounded outline-none focus:border-qyellow"
          />
        </div>

        <ul className="list max-h-[250px] overflow-y-auto">
          {filtered.length === 0 ? (
            <li className="cursor-not-allowed text-center text-qgray py-3 text-sm">
              {ServeLangItem()?.No_results || "Sonuç bulunamadı"}
            </li>
          ) : (
            filtered.map((value) => (
              <li
                className={item && item.name === value.name ? "selected" : ""}
                key={value.id || value.name}
                onClick={(e) => handler(e, value)}
              >
                <span>{value.name}</span>
              </li>
            ))
          )}
        </ul>
      </div>
    </div>
  );
}
