const PLACEHOLDER_PATTERNS = [
  "lorem ipsum",
  "dummy text",
  "printing and typesetting industry standard dummy text",
  "iphone 14",
  "wordpress plugins",
  "newspaper themes",
  "e.g. john doe",
  "e.g. john@example.com",
  "write your thoughts here",
];

export function stripHtml(value = "") {
  return String(value).replace(/<[^>]*>/g, " ").replace(/\s+/g, " ").trim();
}

export function isPlaceholderBlogContent(blog) {
  if (!blog) {
    return false;
  }

  const haystack = [
    blog.title,
    blog.slug,
    blog.seo_title,
    blog.seo_description,
    stripHtml(blog.description),
  ]
    .filter(Boolean)
    .join(" ")
    .toLowerCase();

  return PLACEHOLDER_PATTERNS.some((pattern) => haystack.includes(pattern));
}

export function getBlogReadingTime(blog) {
  const wordCount = stripHtml(blog?.description || "")
    .split(/\s+/)
    .filter(Boolean).length;

  return Math.max(1, Math.ceil(wordCount / 200));
}
