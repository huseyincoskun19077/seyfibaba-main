import Image from "next/image";
import { useState, useEffect } from "react";
import ServeLangItem from "../ServeLangItem";
import appConfig from "@/appConfig";

export default function Selectbox({
  datas = [],
  defaultValue = "",
  className,
  action,
  children,
}) {
  const [item, setItem] = useState({ name: defaultValue });
  const [toggle, setToggle] = useState(false);
  const handler = (e, value) => {
    if (action) {
      action(value);
    }
    setItem(value);
    setToggle(!toggle);
  };
  useEffect(() => {
    if (defaultValue) {
      setItem({ name: defaultValue });
    } else {
      setItem({ name: "Seçiniz" });
    }
  }, [datas, defaultValue]);

  return (
    <>
      <div
        onClick={() => setToggle(!toggle)}
        className={`my-select-box h-full flex items-center cursor-pointer ${
          className || ""
        }`}
      >
        <button type="button" className="my-select-box-btn ">
          {children ? (
            children({ item: item && item.name })
          ) : (
            <span>{item && item.name}</span>
          )}
        </button>
        {toggle && (
          <div className="click-away" onClick={() => setToggle(!toggle)}></div>
        )}
        <div className={`my-select-box-section ${toggle ? "open" : ""}`}>
          <ul className="list">
            <li className="cursor-not-allowed selected pointer-events-none">
              {ServeLangItem()?.Select_One}
            </li>
            {datas &&
              datas.length > 0 &&
              datas.map((value, index) => (
                <li
                  className={item && item.name === value.name ? "selected" : ""}
                  key={Math.random()}
                  onClick={(e) => handler(e, value)}
                >
                  {value.image ? (
                    <div className="flex space-x-5 items-center p-2">
                      <div className="w-10 h-10">
                        <Image
                          width={40}
                          height={40}
                          src={appConfig.BASE_URL + value.image}
                          alt={value.name || "option image"}
                          className="w-full h-full object-cover"
                        />
                      </div>
                      <span>{value.name}</span>
                    </div>
                  ) : (
                    <span>{value.name}</span>
                  )}
                </li>
              ))}
          </ul>
        </div>
      </div>
    </>
  );
}
