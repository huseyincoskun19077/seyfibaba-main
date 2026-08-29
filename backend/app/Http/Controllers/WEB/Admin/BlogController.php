<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogGallery;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Image;
use File;

class BlogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {
        $blogs = Blog::with('category')->orderBy('id', 'desc')->get();
        return view('admin.blog', compact('blogs'));
    }

    public function create()
    {
        $categories = BlogCategory::where('status', 1)->orderBy('name')->get();
        $setting = Setting::first();
        $aiEnabled = (bool) ($setting->openai_enabled ?? false) || (bool) ($setting->claude_enabled ?? false);
        return view('admin.create_blog', compact('categories', 'aiEnabled'));
    }

    public function store(Request $request)
    {
        if ($msg = $this->uploadErrorMessage($request, 'image')) {
            return redirect()->back()->withErrors(['image' => $msg])->withInput();
        }

        $this->validate($request, [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:blogs',
            'category' => 'required',
            'description' => 'required',
            'image' => 'required|image|mimes:jpeg,jpg,png,gif,webp,bmp|max:8192',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:255',
        ], [
            'image.uploaded' => 'Görsel yüklenemedi. Dosya sunucu limitinden büyük olabilir (max '.ini_get('upload_max_filesize').').',
            'image.image' => 'Lütfen geçerli bir görsel seçin (JPG, PNG, WEBP).',
            'image.mimes' => 'Görsel formatı JPG, PNG veya WEBP olmalı.',
            'image.max' => 'Görsel en fazla 8 MB olabilir.',
        ]);

        try {
            $blog = new Blog();
            $blog->image = $this->storeBlogImage($request->file('image'), 'blog');
            $this->fillBlogFromRequest($blog, $request);
            $blog->admin_id = auth('admin')->user()->id;
            $blog->save();
        } catch (\Throwable $e) {
            \Log::error('Blog kaydı başarısız', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withErrors(['image' => 'Görsel kaydedilemedi: '.$e->getMessage()])
                ->withInput();
        }

        $notification = array('messege' => 'Blog başarıyla oluşturuldu.', 'alert-type' => 'success');
        return redirect()->route('admin.blog.index')->with($notification);
    }

    public function edit($id)
    {
        $blog = Blog::with('gallery')->find($id);
        $categories = BlogCategory::where('status', 1)->orderBy('name')->get();
        $setting = Setting::first();
        $aiEnabled = (bool) ($setting->openai_enabled ?? false) || (bool) ($setting->claude_enabled ?? false);
        return view('admin.edit_blog', compact('blog', 'categories', 'aiEnabled'));
    }

    public function update(Request $request, $id)
    {
        if ($msg = $this->uploadErrorMessage($request, 'image')) {
            return redirect()->back()->withErrors(['image' => $msg])->withInput();
        }

        $this->validate($request, [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:blogs,slug,' . $id,
            'category' => 'required',
            'description' => 'required',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp,bmp|max:8192',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:255',
        ], [
            'image.uploaded' => 'Görsel yüklenemedi. Dosya sunucu limitinden büyük olabilir (max '.ini_get('upload_max_filesize').').',
            'image.image' => 'Lütfen geçerli bir görsel seçin (JPG, PNG, WEBP).',
            'image.mimes' => 'Görsel formatı JPG, PNG veya WEBP olmalı.',
            'image.max' => 'Görsel en fazla 8 MB olabilir.',
        ]);

        $blog = Blog::findOrFail($id);

        try {
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $oldImage = $blog->image;
                $blog->image = $this->storeBlogImage($request->file('image'), 'blog');
                $this->deletePublicFile($oldImage);
            }

            $this->fillBlogFromRequest($blog, $request);
            $blog->save();
        } catch (\Throwable $e) {
            \Log::error('Blog güncelleme başarısız', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()
                ->withErrors(['image' => 'Görsel kaydedilemedi: '.$e->getMessage()])
                ->withInput();
        }

        $notification = array('messege' => 'Blog başarıyla güncellendi.', 'alert-type' => 'success');
        return redirect()->route('admin.blog.index')->with($notification);
    }

    public function destroy($id)
    {
        $blog = Blog::find($id);
        $old_image = $blog->image;
        $blog->delete();
        if ($old_image && File::exists(public_path() . '/' . $old_image)) {
            unlink(public_path() . '/' . $old_image);
        }

        $notification = array('messege' => 'Blog başarıyla silindi.', 'alert-type' => 'success');
        return redirect()->route('admin.blog.index')->with($notification);
    }

    public function changeStatus($id)
    {
        $blog = Blog::find($id);
        $blog->status = $blog->status == 1 ? 0 : 1;
        $blog->save();
        return response()->json($blog->status == 1 ? 'Aktif edildi' : 'Pasif edildi');
    }

    public function storeGallery(Request $request)
    {
        $this->validate($request, [
            'images' => 'required',
            'images.*' => 'image|mimes:jpeg,jpg,png,gif,webp,bmp|max:8192',
            'blog_id' => 'required',
        ]);

        try {
            foreach ($request->file('images', []) as $image) {
                if (!$image || !$image->isValid()) {
                    continue;
                }
                $gallery = new BlogGallery();
                $gallery->blog_id = $request->blog_id;
                $gallery->image = $this->storeBlogImage($image, 'blog-gallery');
                $gallery->save();
            }
        } catch (\Throwable $e) {
            \Log::error('Blog galeri yükleme başarısız', ['error' => $e->getMessage()]);
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Görsel yüklenemedi.'], 422);
            }
            return redirect()->back()->withErrors(['images' => 'Görsel yüklenemedi. JPG, PNG veya WEBP deneyin.']);
        }

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Görseller yüklendi.']);
        }

        $notification = array('messege' => 'Görseller yüklendi.', 'alert-type' => 'success');
        return redirect()->back()->with($notification);
    }

    public function destroyGallery($id)
    {
        $gallery = BlogGallery::find($id);
        $old = $gallery->image;
        $gallery->delete();
        if ($old && File::exists(public_path() . '/' . $old)) {
            unlink(public_path() . '/' . $old);
        }

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Görsel silindi.']);
        }

        $notification = array('messege' => 'Görsel silindi.', 'alert-type' => 'success');
        return redirect()->back()->with($notification);
    }

    private function uploadErrorMessage(Request $request, string $field = 'image'): ?string
    {
        $uploadMax = ini_get('upload_max_filesize') ?: '?';
        $postMax = ini_get('post_max_size') ?: '?';

        // post_max_size aşılırsa PHP $_POST/$_FILES'ı boşaltır
        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
        if ($contentLength > 0 && empty($request->all()) && empty($_FILES)) {
            return "Form/dosya çok büyük. Sunucu limiti post_max_size={$postMax}. 1–2 MB JPG deneyin.";
        }

        if (!$request->hasFile($field)) {
            return null;
        }

        $file = $request->file($field);
        if ($file->isValid()) {
            return null;
        }

        return match ($file->getError()) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                "Görsel çok büyük. Sunucu upload_max_filesize={$uploadMax}. Daha küçük JPG/PNG yükleyin.",
            UPLOAD_ERR_PARTIAL => 'Görsel eksik yüklendi. Tekrar deneyin.',
            UPLOAD_ERR_NO_TMP_DIR => 'Sunucu geçici klasör hatası (tmp).',
            UPLOAD_ERR_CANT_WRITE => 'Görsel diske yazılamadı. uploads klasör izinlerini kontrol edin.',
            UPLOAD_ERR_EXTENSION => 'Yükleme bir PHP eklentisi tarafından engellendi.',
            default => "Görsel yüklenemedi (kod {$file->getError()}). Limit: {$uploadMax}.",
        };
    }

    private function fillBlogFromRequest(Blog $blog, Request $request): void
    {
        $blog->title = $request->title;
        $blog->slug = $request->slug;
        $blog->blog_category_id = $request->category;
        try {
            $blog->description = clean($request->description);
        } catch (\Throwable $e) {
            $blog->description = $request->description;
        }
        $blog->seo_title = $request->input('seo_title') ?: $request->title;
        $blog->seo_description = $request->input('seo_description') ?: Str::limit(strip_tags((string) $request->description), 160, '');
        $blog->status = (int) $request->input('status', 1);
        $blog->show_homepage = $request->show_homepage ? 1 : 0;
    }

    private function storeBlogImage($file, string $prefix = 'blog'): string
    {
        $dir = public_path('uploads/custom-images');
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true)) {
            $ext = 'jpg';
        }

        $filename = $prefix . '-' . date('Y-m-d-H-i-s') . '-' . rand(1000, 9999) . '.' . $ext;
        $relative = 'uploads/custom-images/' . $filename;
        $absolute = $dir . DIRECTORY_SEPARATOR . $filename;

        try {
            $img = Image::make($file->getRealPath() ?: $file);
            $img->resize(1920, 1920, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            $img->save($absolute, 85);
        } catch (\Throwable $e) {
            $file->move($dir, $filename);
        }

        return $relative;
    }

    private function deletePublicFile(?string $path): void
    {
        if (!$path) {
            return;
        }
        $full = public_path($path);
        if (File::exists($full)) {
            File::delete($full);
        }
    }
}
