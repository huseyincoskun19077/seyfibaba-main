<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\UploadBulkProductImportRequest;
use App\Models\BulkImport;
use App\Models\Vendor;
use App\Services\BulkProductImportService;
use Illuminate\Support\Facades\Auth;

class ProductBulkImportController extends Controller
{
    public function __construct(private readonly BulkProductImportService $bulkImportService)
    {
        $this->middleware('auth:admin-api');
    }

    public function upload(UploadBulkProductImportRequest $request)
    {
        $adminId = (int) Auth::guard('admin-api')->id();
        $vendorId = (int) $request->input('vendor_id', 0);
        $vendor = $vendorId > 0 ? Vendor::query()->find($vendorId) : null;

        if ($vendorId > 0 && ! $vendor) {
            return response()->json(['message' => 'Seçilen satıcı bulunamadı.'], 422);
        }

        $bulkImport = $this->bulkImportService->createImportRecord($adminId, 'admin', $request->file('import_file'));
        $bulkImport = $this->bulkImportService->queueProcess($bulkImport, $vendor);

        return response()->json([
            'message' => $this->bulkImportService->summaryMessage($bulkImport),
            'import' => $bulkImport,
            'vendor_id' => $vendor?->id ?? 0,
        ], $bulkImport->success_count > 0 ? 201 : 422);
    }

    public function index()
    {
        $imports = BulkImport::query()
            ->where('user_type', 'admin')
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json(['imports' => $imports]);
    }
}
