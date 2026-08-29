import Link from "next/link";
import ServeLangItem from "../ServeLangItem";
import ShopNowIco from "../icons/ShopNowIco";
import { displayTurkishLabel } from "@/utils/turkishDisplay";

export default function CategoryCard({
  background,
  title,
  categories = [],
  changeIdHandler,
  productsInCategoryIds,
  moreUrl = "#",
}) {
  // Filter categories to only include those with products
  const filterCategory =
    categories && categories.length > 0
      ? categories.filter((category) => {
          const id = parseInt(category.category_id);
          return productsInCategoryIds.includes(id);
        })
      : [];

  return (
    <div
      className="category-card-wrapper w-full h-full relative rounded-2xl overflow-hidden shadow-sm group"
      style={{
        background: `url(${
          background || `/assets/images/section-category-1.jpg`
        }) no-repeat`,
        backgroundSize: "cover",
        backgroundPosition: "center",
      }}
    >
      <div className="absolute inset-0 bg-gradient-to-b from-white/90 via-white/70 to-white/40 pointer-events-none group-hover:from-white/95 transition-all duration-300"></div>
      <div className="relative z-10 p-8 h-full flex flex-col justify-between">
        <div>
          {/* Card Title */}
          <div className="mb-6">
            <h3 className="text-2xl font-black text-gray-900 leading-tight group-hover:scale-[1.02] transform transition-transform duration-300 uppercase italic">
              {title}
            </h3>
            <div className="w-12 h-1 bg-qyellow rounded-full mt-2"></div>
          </div>
          {/* List of filtered categories */}
          <div className="brands-list mb-8">
            <ul className="space-y-3">
              {filterCategory.map((category) => (
                <li key={category.id}>
                  <span
                    onClick={() => changeIdHandler(category.category_id)}
                    className="text-sm font-semibold text-gray-700 hover:text-gray-900 transition-colors duration-200 flex items-center group/item cursor-pointer"
                  >
                    <span className="w-1.5 h-1.5 bg-qyellow rounded-full mr-2 opacity-0 group-hover/item:opacity-100 transition-opacity"></span>
                    {category && displayTurkishLabel(category.category.name)}
                  </span>
                </li>
              ))}
            </ul>
          </div>
        </div>

        {/* 'Shop Now' link with icon */}
        <Link href={`${moreUrl}`}>
          <div className="inline-flex items-center space-x-2 text-gray-900 font-bold group/btn cursor-pointer">
            <span className="text-sm border-b-2 border-qyellow leading-relaxed">
              {ServeLangItem()?.Shop_Now}
            </span>
            <span className="transform transition-transform duration-300 group-hover/btn:translate-x-1">
              <ShopNowIco />
            </span>
          </div>
        </Link>
      </div>
    </div>
  );
}
