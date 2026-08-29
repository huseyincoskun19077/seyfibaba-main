<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use App\Models\LegalDocumentConsent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LegalDocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {
        $documents = LegalDocument::orderBy('sort_order')->orderBy('title')->get();

        return view('admin.legal_documents.index', compact('documents'));
    }

    public function create()
    {
        return view('admin.legal_documents.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateDocument($request);

        LegalDocument::create($validated);

        $notification = ['messege' => trans('admin_validation.Created Successfully'), 'alert-type' => 'success'];

        return redirect()->route('admin.legal-documents.index')->with($notification);
    }

    public function edit(LegalDocument $legal_document)
    {
        return view('admin.legal_documents.edit', ['document' => $legal_document]);
    }

    public function update(Request $request, LegalDocument $legal_document)
    {
        $validated = $this->validateDocument($request, $legal_document->id);

        $legal_document->update($validated);

        $notification = ['messege' => trans('admin_validation.Updated Successfully'), 'alert-type' => 'success'];

        return redirect()->route('admin.legal-documents.index')->with($notification);
    }

    public function consents(LegalDocument $legal_document)
    {
        $consents = LegalDocumentConsent::where('legal_document_id', $legal_document->id)
            ->orWhere('document_slug', $legal_document->slug)
            ->orderByDesc('consented_at')
            ->paginate(50);

        return view('admin.legal_documents.consents', compact('legal_document', 'consents'));
    }

    private function validateDocument(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:64',
                Rule::unique('legal_documents', 'slug')->ignore($ignoreId),
            ],
            'content' => 'nullable|string',
            'version' => 'required|string|max:32',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'is_published' => 'nullable|boolean',
            'requires_consent' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'category' => 'nullable|string|max:32',
        ]);

        $validated['slug'] = Str::slug($validated['slug']);
        $validated['is_published'] = $request->boolean('is_published');
        $validated['requires_consent'] = $request->boolean('requires_consent');
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['category'] = $validated['category'] ?? 'legal';

        return $validated;
    }
}
