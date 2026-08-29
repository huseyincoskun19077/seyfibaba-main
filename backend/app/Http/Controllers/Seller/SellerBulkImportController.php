<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\UploadBulkProductImportRequest;
use App\Models\BulkImport;
use App\Models\Vendor;
use App\Services\BulkProductImportService;
use Illuminate\Support\Facades\Auth;

class SellerBulkImportController extends Controller
{
    public function __construct(private readonly BulkProductImportService $bulkImportService)
    {
        $this->middleware('auth:api');
        $this->middleware('checkseller');
    }

    public function upload(UploadBulkProductImportRequest $request)
    {
        $vendor = $this->resolveVendor();

        if ($vendor->kyc_status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Toplu yükleme için KYC doğrulamanız onaylanmış olmalı.',
            ], 403);
        }

        $bulkImport = $this->bulkImportService->createImportRecord(
            $vendor->user_id,
            'seller',
            $request->file('import_file')
        );
        $bulkImport = $this->bulkImportService->queueProcess($bulkImport, $vendor);

        return response()->json([
            'success' => $bulkImport->status !== 'failed',
            'message' => $this->bulkImportService->summaryMessage($bulkImport),
            'import' => $bulkImport,
        ], $bulkImport->success_count > 0 ? 201 : 422);
    }

    public function index()
    {
        $vendor = $this->resolveVendor();

        $imports = BulkImport::query()
            ->where('user_id', $vendor->user_id)
            ->where('user_type', 'seller')
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json(['imports' => $imports]);
    }

    public function show($id)
    {
        $vendor = $this->resolveVendor();

        $import = BulkImport::query()
            ->where('user_id', $vendor->user_id)
            ->where('user_type', 'seller')
            ->findOrFail($id);

        return response()->json(['import' => $import]);
    }

    public function template()
    {
        return response($this->bulkImportService->templateCsv(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="seyfibaba-urun-sablonu.csv"',
        ]);
    }

    public function sample()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ProductBulkImportSampleExport(),
            'seyfibaba-ornek-urun-yukleme.xlsx'
        );
    }

    private function resolveVendor(): Vendor
    {
        return Vendor::query()->where('user_id', Auth::guard('api')->id())->firstOrFail();
    }
}
