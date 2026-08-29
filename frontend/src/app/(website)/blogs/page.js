import appConfig from "@/appConfig";
import Image from "next/image";
import Link from "next/link";
import { buildPageTitle } from "@/utils/pageTitle";

export const dynamic = "force-dynamic";

async function getBlogs() {
  const res = await fetch(`${appConfig.BASE_URL}api/blogs`, {
    next: { revalidate: 300 },
  });
  if (!res.ok) return { blogs: [], seoSetting: null };
  return res.json();
}

export async function generateMetadata() {
  const data = await getBlogs();
  return {
    title: buildPageTitle(data?.seoSetting?.seo_title || "Blog"),
    description:
      data?.seoSetting?.seo_description ||
      "Kuaför ve berber sektörüne dair bilgilendirici yazılar.",
    alternates: { canonical: "/blogs" },
  };
}

export default async function BlogsPage() {
  const data = await getBlogs();
  const blogs = data?.blogs || [];

  return (
    <div className="w-full min-h-screen bg-[#fdfdfd]">
      <div className="container-x mx-auto py-10">
        <h1 className="text-3xl font-bold text-qblack mb-8">Blog</h1>

        {blogs.length === 0 ? (
          <p className="text-qgray text-center py-20">
            Henüz blog yazısı bulunmuyor.
          </p>
        ) : (
          <div className="grid lg:grid-cols-3 md:grid-cols-2 grid-cols-1 gap-8">
            {blogs.map((blog) => (
              <Link
                key={blog.id}
                href={`/blog/${blog.slug}`}
                className="group"
              >
                <article className="bg-white rounded-2xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                  <div className="relative h-[200px] overflow-hidden">
                    <Image
                      src={
                        blog.image
                          ? `${appConfig.BASE_URL}${blog.image}`
                          : "/assets/images/server-error.png"
                      }
                      alt={blog.title}
                      fill
                      className="object-cover group-hover:scale-105 transition-transform duration-300"
                      sizes="(max-width: 768px) 100vw, 33vw"
                    />
                  </div>
                  <div className="p-5">
                    {blog.category && (
                      <span className="text-xs font-medium text-qyellow uppercase tracking-wider">
                        {blog.category.name}
                      </span>
                    )}
                    <h2 className="text-lg font-semibold text-qblack mt-2 mb-3 line-clamp-2 group-hover:text-qyellow transition-colors">
                      {blog.title}
                    </h2>
                    <div className="flex items-center text-xs text-qgray space-x-3">
                      <span>
                        {new Date(blog.created_at).toLocaleDateString("tr-TR", {
                          day: "numeric",
                          month: "long",
                          year: "numeric",
                        })}
                      </span>
                    </div>
                  </div>
                </article>
              </Link>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
