import ProductsCompare from "@/components/ProductsCompare/index";

// generate seo metadata
export function generateMetadata() {
  return {
    title: "Ürün Karşılaştır",
    description: "Ürünleri satın almadan önce yan yana karşılaştırın.",
    alternates: {
      canonical: "/products-compare",
    },
  };
}

// main page
export default function ProductsComparePage() {
  return <ProductsCompare />;
}
