import Link from "next/link";
import ServeLangItem from "./ServeLangItem";

export default function ViewMoreTitle({
  categoryTitle = "",
  className,
  children,
  seeMoreUrl = "",
}) {
  return (
    <div className={`section-wrapper w-full ${className || ""}`}>
      <div className="container-x mx-auto">
        <div className="section-title flex justify-between items-center mb-4 md:mb-6">
          <div className="relative">
            <h2 className="sm:text-2xl text-xl font-bold text-qblacktext leading-none relative z-10">
              {categoryTitle}
            </h2>
            <div className="absolute -bottom-2 left-0 w-1/2 h-1 bg-qyellow rounded-full"></div>
          </div>
          <div className="view-more-btn">
            <Link href={seeMoreUrl}>
              <div className="flex space-x-2 text-[#4B5563] hover:text-qblack transition-colors duration-300 items-center cursor-pointer group">
                <p className="text-base font-bold">
                  {ServeLangItem()?.View_More}
                </p>
                <span className="transform transition-transform duration-300 group-hover:translate-x-1">
                  <svg
                    className="w-5 h-5"
                    width="17"
                    height="14"
                    viewBox="0 0 17 14"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    <path 
                      className="fill-current"
                      d="M14.0225 6.00241C13.6262 5.60923 13.2243 5.22188 12.8336 4.82288C11.7552 3.72199 10.6796 2.61818 9.60971 1.50855C9.31816 1.20566 9.26721 0.77753 9.44837 0.448427C9.63518 0.113498 10.0116 -0.0787213 10.3711 0.0290382C10.5466 0.0814617 10.7278 0.192134 10.8608 0.326105C12.8195 2.32694 14.7697 4.33359 16.7199 6.34608C17.0879 6.72469 17.0936 7.26058 16.7228 7.64501C14.7584 9.67497 12.7884 11.6991 10.8155 13.7203C10.4475 14.0989 9.92106 14.0873 9.58706 13.7291C9.25589 13.3737 9.26155 12.8408 9.62952 12.4622C10.9825 11.0642 12.3383 9.67206 13.6998 8.28284C13.8017 8.1809 13.9404 8.11974 14.0649 8.0411C14.0338 7.98577 14.0055 7.93334 13.9743 7.87801C13.8526 7.87801 13.7281 7.87801 13.6064 7.87801C9.47101 7.8751 5.33848 7.8751 1.20311 7.87218C1.0814 7.87218 0.962519 7.87801 0.840808 7.86927C0.3483 7.84015 -0.00834304 7.45862 0.00014847 6.98098C0.00863998 6.515 0.351131 6.14512 0.832316 6.12764C1.3503 6.10726 1.86828 6.11891 2.38626 6.11891C6.16215 6.11599 9.93521 6.11599 13.7083 6.11599C13.7904 6.11599 13.8724 6.10726 13.9574 6.10143C13.9772 6.0694 13.9998 6.03445 14.0225 6.00241Z" />
                  </svg>
                </span>
              </div>
            </Link>
          </div>
        </div>
        <div className="section-content">{children && children}</div>
      </div>
    </div>
  );
}
