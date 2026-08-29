<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\SeoSetting;
use App\Models\PopularPost;

class BlogController extends Controller
{
    protected function resolveSlugCandidates(string $slug): array
    {
        return collect([
            $slug,
            rawurldecode($slug),
            rtrim($slug, '-'),
            rtrim(rawurldecode($slug), '-'),
        ])->filter()->unique()->values()->all();
    }

    protected function getSidebarData()
    {
        $popularPostIds = PopularPost::where('status', 1)
            ->orderBy('id', 'desc')
            ->pluck('blog_id');

        $popularPosts = Blog::with(['category:id,name,slug'])
            ->whereIn('id', $popularPostIds)
            ->where('status', 1)
            ->orderBy('views', 'desc')
            ->take(5)
            ->get();

        $latestBlogs = Blog::with(['category:id,name,slug'])
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        $categories = BlogCategory::withCount([
            'blogs' => function ($query) {
                $query->where('status', 1);
            }
        ])
            ->where('status', 1)
            ->whereHas('blogs', function ($query) {
                $query->where('status', 1);
            })
            ->orderBy('name', 'asc')
            ->get();

        return [
            'popularPosts' => $popularPosts,
            'latestBlogs' => $latestBlogs,
            'categories' => $categories,
        ];
    }

    public function index(Request $request)
    {
        $blogs = Blog::with(['category', 'admin'])->where('status', 1);

        if ($request->category) {
            $category = BlogCategory::where('slug', $request->category)->first();
            if ($category) {
                $blogs = $blogs->where('blog_category_id', $category->id);
            }
        }

        if ($request->search) {
            $blogs = $blogs->where('title', 'LIKE', '%' . $request->search . '%')
                           ->orWhere('description', 'LIKE', '%' . $request->search . '%');
        }

        $blogs = $blogs->orderBy('id', 'desc')->get();

        $seoSetting = SeoSetting::find(6); // Assuming 6 is for blog

        return response()->json([
            'blogs' => $blogs,
            'seoSetting' => $seoSetting
        ]);
    }

    public function blogCategory()
    {
        $categories = BlogCategory::withCount([
            'blogs' => function ($query) {
                $query->where('status', 1);
            }
        ])
            ->where('status', 1)
            ->whereHas('blogs', function ($query) {
                $query->where('status', 1);
            })
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'categories' => $categories
        ]);
    }

    public function blogCategoryDetail($slug)
    {
        $category = BlogCategory::where('status', 1)
            ->whereIn('slug', $this->resolveSlugCandidates($slug))
            ->first();

        if (!$category) {
            return response()->json(['message' => 'Blog category not found'], 404);
        }

        $blogs = Blog::with(['category:id,name,slug', 'admin:id,name'])
            ->where([
                'blog_category_id' => $category->id,
                'status' => 1,
            ])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'category' => $category,
            'blogs' => $blogs,
            ...$this->getSidebarData(),
        ]);
    }

    public function blogDetail($slug)
    {
        $blog = Blog::with(['category:id,name,slug', 'admin:id,name', 'gallery'])
            ->where('status', 1)
            ->whereIn('slug', $this->resolveSlugCandidates($slug))
            ->first();

        if (!$blog) {
            return response()->json(['message' => 'Blog not found'], 404);
        }

        $blog->increment('views');
        $blog->refresh();

        $relatedBlogs = Blog::with(['category:id,name,slug', 'admin:id,name'])
            ->where(['blog_category_id' => $blog->blog_category_id, 'status' => 1])
            ->where('id', '!=', $blog->id)
            ->orderBy('id', 'desc')
            ->take(4)
            ->get();

        return response()->json([
            'blog' => $blog,
            'gallery' => $blog->gallery,
            'relatedBlogs' => $relatedBlogs,
            ...$this->getSidebarData(),
        ]);
    }

    public function blogComment(Request $request)
    {
        $rules = [
            'blog_id' => 'required',
            'name' => 'required',
            'email' => 'required',
            'comment' => 'required',
        ];

        $this->validate($request, $rules);

        $comment = new BlogComment();
        $comment->blog_id = $request->blog_id;
        $comment->name = $request->name;
        $comment->email = $request->email;
        $comment->comment = $request->comment;
        $comment->save();

        return response()->json(['message' => 'Comment submitted successfully']);
    }
}
