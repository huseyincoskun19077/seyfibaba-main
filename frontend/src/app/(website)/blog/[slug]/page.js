import appConfig from "@/appConfig";
import Image from "next/image";
import Link from "next/link";
import BlogGallery from "./BlogGallery";

export const dynamic = "force-dynamic";

async function getBlog(slug) {
  const res = await fetch(`${appConfig.BASE_URL}api/blog/${slug}`, {
    next: { revalidate: 300 },
  });
  if (!res.ok) return null;
  return res.json();
}

export async function generateMetadata({ params }) {
  const { slug } = await params;
  const data = await getBlog(slug);
  const blog = data?.blog;
  return {
    title: blog?.seo_title || blog?.title || "Blog",
    description: blog?.seo_description || "",
    alternates: { canonical: `/blog/${slug}` },
  };
}

export default async function BlogDetailPage({ params }) {
  const { slug } = await params;
  const data = await getBlog(slug);
  const blog = data?.blog;
  const gallery = data?.gallery || [];
  const relatedBlogs = data?.relatedBlogs || [];

  if (!blog) {
    return (
      <div className="container-x mx-auto py-20 text-center">
        <h1 className="text-2xl font-bold text-qblack">
          Blog yazısı bulunamadı
        </h1>
        <Link href="/blogs" className="text-qyellow mt-4 inline-block">
          Bloglara dön
        </Link>
      </div>
    );
  }

  return (
    <div className="w-full min-h-screen bg-[#fdfdfd]">
      <div className="container-x mx-auto py-10">
        <div className="max-w-4xl mx-auto">
          {/* Breadcrumb */}
          <nav className="flex items-center text-sm text-qgray mb-6 space-x-2">
            <Link href="/" className="hover:text-qyellow">
              Ana Sayfa
            </Link>
            <span>/</span>
            <Link href="/blogs" className="hover:text-qyellow">
              Blog
            </Link>
            <span>/</span>
            <span className="text-qblack line-clamp-1">{blog.title}</span>
          </nav>

          {/* Blog Header */}
          <article>
            <div className="mb-4 flex items-center space-x-4 text-sm text-qgray">
              {blog.category && (
                <span className="text-xs font-medium text-qyellow uppercase tracking-wider bg-yellow-50 px-3 py-1 rounded-full">
                  {blog.category.name}
                </span>
              )}
              <span>
                {new Date(blog.created_at).toLocaleDateString("tr-TR", {
                  day: "numeric",
                  month: "long",
                  year: "numeric",
                })}
              </span>
            </div>

            <h1 className="text-3xl md:text-4xl font-bold text-qblack mb-6 leading-tight">
              {blog.title}
            </h1>

            {/* Featured Image */}
            {blog.image && (
              <div className="relative w-full h-[300px] md:h-[450px] rounded-2xl overflow-hidden mb-8">
                <Image
                  src={`${appConfig.BASE_URL}${blog.image}`}
                  alt={blog.title}
                  fill
                  className="object-cover"
                  sizes="(max-width: 768px) 100vw, 800px"
                  priority
                />
              </div>
            )}

            {/* Content */}
            <div
              className="prose prose-lg max-w-none text-[#4b5563] leading-8"
              dangerouslySetInnerHTML={{ __html: blog.description }}
            />

            {/* Gallery */}
            {gallery.length > 0 && <BlogGallery gallery={gallery} />}
          </article>

          {/* Related Blogs */}
          {relatedBlogs.length > 0 && (
            <div className="mt-16 border-t border-gray-100 pt-10">
              <h2 className="text-2xl font-bold text-qblack mb-6">
                İlgili Yazılar
              </h2>
              <div className="grid md:grid-cols-2 gap-6">
                {relatedBlogs.map((item) => (
                  <Link
                    key={item.id}
                    href={`/blog/${item.slug}`}
                    className="group flex gap-4 bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-shadow"
                  >
                    <div className="relative w-[120px] h-[90px] flex-shrink-0 rounded-lg overflow-hidden">
                      <Image
                        src={
                          item.image
                            ? `${appConfig.BASE_URL}${item.image}`
                            : "/assets/images/server-error.png"
                        }
                        alt={item.title}
                        fill
                        className="object-cover"
                        sizes="120px"
                      />
                    </div>
                    <div className="flex-1">
                      <h3 className="text-sm font-semibold text-qblack line-clamp-2 group-hover:text-qyellow transition-colors">
                        {item.title}
                      </h3>
                      <span className="text-xs text-qgray mt-2 block">
                        {new Date(item.created_at).toLocaleDateString("tr-TR", {
                          day: "numeric",
                          month: "long",
                          year: "numeric",
                        })}
                      </span>
                    </div>
                  </Link>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
