import CategoryBrowseClient from "@/components/CategoryBrowse/CategoryBrowseClient";

export const metadata = {
  title: "Alt Kategori",
  robots: { index: true, follow: true },
};

export default async function SubCategoryBrowsePage({ params }) {
  const { slug, subSlug } = await params;
  return <CategoryBrowseClient categorySlug={slug} subSlug={subSlug} />;
}
