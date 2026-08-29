import JsonLd, { generateBreadcrumbSchema } from "./Helpers/JsonLd";
import Link from "next/link";
import ServeLangItem from "./Helpers/ServeLangItem";
import { displayTurkishLabel } from "@/utils/turkishDisplay";

export default function BreadcrumbCom({
  paths = [{ name: ServeLangItem()?.home || "ana sayfa", path: "/" }],
}) {
  return (
    <nav aria-label="Breadcrumb" className="breadcrumb-wrapper print:hidden">
      {/* JSON-LD Schema for Google Rich Results */}
      <JsonLd data={generateBreadcrumbSchema(paths)} />
      
      <div className="flex items-center flex-wrap font-400 text-[13px] text-[#6B7280] mb-[23px]">
        {paths.map((path, index) => (
          <span key={index} className="flex items-center">
            <Link href={path.path} className="hover:text-qblack transition-colors">
              <span>{displayTurkishLabel(path.name)}</span>
            </Link>
            {index < paths.length - 1 && (
              <span className="mx-2 text-[#D1D5DB]">/</span>
            )}
          </span>
        ))}
      </div>
    </nav>
  );
}
