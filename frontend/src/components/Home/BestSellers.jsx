import Image from "next/image";
import Link from "next/link";
import appConfig from "@/appConfig";
export default function BestSellers({ className, sallers = [] }) {
  return (
    <div className={`w-full ${className || ""}`}>
      <div className="grid xl:grid-cols-6 lg:grid-cols-5 sm:grid-cols-3 grid-cols-2 xl:gap-[30px] sm:gap-5 gap-2">
        {sallers.map((saller) => (
          <div
            key={saller.id}
            data-aos="fade-left"
            data-aos-duration="500"
            className="item w-full flex flex-col items-center"
          >
            <Link
              href={`/seller/${saller.slug}`}
            >
              <div className="sm:w-[170px] p-[30px] sm:h-[170px] w-[140px] h-[140px] rounded-full bg-white flex justify-center items-center overflow-hidden mb-2 relative">
                <Image
                  width={170}
                  height={170}
                  className="w-full h-full object-contain"
                  src={saller.logo ? appConfig.BASE_URL + saller.logo : "/assets/images/server-error.png"}
                  alt={
                    saller.shop_name
                      ? `${saller.shop_name} satici logosu`
                      : "Seyfibaba satici magazasi logosu"
                  }
                  loading="lazy"
                />
              </div>
              <p className="text-base font-500 text-center cursor-pointer">
                {saller.shop_name}
              </p>
            </Link>
          </div>
        ))}
      </div>
    </div>
  );
}
