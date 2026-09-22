<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(Request $request)
    {
        if (! Schema::hasTable('stories')) {
            return response(
                '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Stories</title></head><body style="font-family:sans-serif;padding:24px">'
                .'<h1>Stories tablosu yok</h1>'
                .'<pre>cd /opt/seyfibaba-main/backend'."\n".'php artisan migrate --force</pre>'
                .'</body></html>',
                503
            );
        }

        $stories = Story::query()->orderBy('serial')->orderBy('id')->get();
        $editStory = null;
        if ($request->filled('edit')) {
            $editStory = Story::query()->find((int) $request->query('edit'));
        }

        return view('admin.story', [
            'stories' => $stories,
            'editStory' => $editStory,
            'feeds' => Story::FEEDS,
            'types' => Story::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('stories')) {
            return redirect()->route('admin.dashboard')->with([
                'messege' => 'stories tablosu yok. Önce migrate çalıştırın.',
                'alert-type' => 'error',
            ]);
        }

        $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'image' => [$request->filled('id') ? 'nullable' : 'nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'type' => ['required', Rule::in(array_keys(Story::TYPES))],
            'feed' => ['nullable', Rule::in(array_keys(Story::FEEDS))],
            'link' => ['nullable', 'string', 'max:500'],
            'see_all_url' => ['nullable', 'string', 'max:500'],
            'serial' => ['nullable', 'integer', 'min:0'],
        ]);

        $story = $request->filled('id')
            ? Story::query()->findOrFail((int) $request->input('id'))
            : new Story();

        $story->title = trim((string) $request->input('title'));
        $story->type = (string) $request->input('type');
        $story->feed = $story->type === 'product_feed'
            ? (trim((string) $request->input('feed', '')) ?: 'popular')
            : null;
        $story->link = trim((string) $request->input('link', '')) ?: null;
        $story->see_all_url = trim((string) $request->input('see_all_url', '')) ?: null;
        $story->serial = (int) ($request->input('serial') ?: 1);
        $story->status = $request->boolean('status');

        if ($request->hasFile('image')) {
            $dir = public_path('uploads/website-images');
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            $file = $request->file('image');
            $name = 'story-'.date('Y-m-d-His').'-'.rand(1000, 9999).'.'.$file->getClientOriginalExtension();
            $file->move($dir, $name);
            if ($story->image && File::exists(public_path($story->image))) {
                File::delete(public_path($story->image));
            }
            $story->image = 'uploads/website-images/'.$name;
        }

        $story->save();

        return redirect()->route('admin.story.index')->with([
            'messege' => $request->filled('id') ? 'Story güncellendi' : 'Story eklendi',
            'alert-type' => 'success',
        ]);
    }

    public function destroy($id)
    {
        $story = Story::query()->findOrFail((int) $id);
        if ($story->image && File::exists(public_path($story->image))) {
            File::delete(public_path($story->image));
        }
        $story->delete();

        return redirect()->route('admin.story.index')->with([
            'messege' => 'Story silindi',
            'alert-type' => 'success',
        ]);
    }

    public function reorder(Request $request)
    {
        if (! Schema::hasTable('stories')) {
            return response()->json([
                'success' => false,
                'message' => 'stories tablosu yok.',
            ], 422);
        }

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:stories,id',
        ]);

        foreach (array_values($request->input('ids', [])) as $index => $id) {
            Story::query()->where('id', (int) $id)->update(['serial' => $index + 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sıralama güncellendi.',
        ]);
    }
}
