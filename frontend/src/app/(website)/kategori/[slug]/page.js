import CategoryBrowseClient from "@/components/CategoryBrowse/CategoryBrowseClient";

export const metadata = {
  title: "Kategori",
  robots: { index: true, follow: true },
};

export default async function CategoryBrowsePage({ params }) {
  const { slug } = await params;
  return <CategoryBrowseClient categorySlug={slug} />;
}
