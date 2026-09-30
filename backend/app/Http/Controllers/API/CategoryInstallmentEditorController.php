<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ChildCategory;
use App\Models\SubCategory;
use App\Services\CategoryInstallmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CategoryInstallmentEditorController extends Controller
{
    public const ALLOWED_EMAIL = 'iyzicotestekibi@gmail.com';

    public function index()
    {
        $denied = $this->denyUnlessAllowed();
        if ($denied) {
            return $denied;
        }

        if (! Schema::hasColumn('categories', 'max_installment')) {
            return response()->json(['message' => 'Taksit alanı yok.'], 500);
        }

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'max_installment'])
            ->map(fn (Category $row) => $this->row('category', $row->id, $row->name, $row->max_installment))
            ->values();

        $subs = collect();
        if (Schema::hasColumn('sub_categories', 'max_installment')) {
            $subs = SubCategory::query()
                ->with('category:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'category_id', 'max_installment'])
                ->map(fn (SubCategory $row) => $this->row(
                    'sub',
                    $row->id,
                    $row->name,
                    $row->max_installment,
                    $row->category->name ?? ''
                ))
                ->values();
        }

        $children = collect();
        if (Schema::hasColumn('child_categories', 'max_installment')) {
            $children = ChildCategory::query()
                ->with(['category:id,name', 'subCategory:id,name'])
                ->orderBy('name')
                ->get(['id', 'name', 'category_id', 'sub_category_id', 'max_installment'])
                ->map(fn (ChildCategory $row) => $this->row(
                    'child',
                    $row->id,
                    $row->name,
                    $row->max_installment,
                    trim(($row->category->name ?? '').' / '.($row->subCategory->name ?? ''), ' /')
                ))
                ->values();
        }

        return response()->json([
            'allowed_installments' => CategoryInstallmentService::VALID_INSTALLMENTS,
            'categories' => $categories,
            'sub_categories' => $subs,
            'child_categories' => $children,
        ]);
    }

    public function update(Request $request)
    {
        $denied = $this->denyUnlessAllowed();
        if ($denied) {
            return $denied;
        }

        $data = $request->validate([
            'level' => 'required|in:category,sub,child',
            'id' => 'required|integer|min:1',
            'max_installment' => 'nullable|integer|in:0,1,2,3,6,9,12',
        ]);

        $value = $data['max_installment'] ?? null;
        $stored = ($value === null || (int) $value === 0) ? null : (int) $value;

        $row = match ($data['level']) {
            'category' => Category::query()->find($data['id']),
            'sub' => SubCategory::query()->find($data['id']),
            default => ChildCategory::query()->find($data['id']),
        };

        if (! $row || ! Schema::hasColumn($row->getTable(), 'max_installment')) {
            return response()->json(['message' => 'Kategori bulunamadı.'], 404);
        }

        $row->max_installment = $stored;
        $row->save();

        return response()->json([
            'level' => $data['level'],
            'id' => (int) $row->id,
            'name' => (string) $row->name,
            'max_installment' => $row->max_installment === null ? null : (int) $row->max_installment,
        ]);
    }

    public static function isAllowedEmail(?string $email): bool
    {
        return hash_equals(
            self::ALLOWED_EMAIL,
            strtolower(trim((string) $email))
        );
    }

    private function denyUnlessAllowed()
    {
        $user = auth('api')->user();
        if (! $user || ! self::isAllowedEmail($user->email ?? null)) {
            return response()->json(['message' => 'Bu sayfaya erişiminiz yok.'], 403);
        }

        return null;
    }

    /**
     * @return array{level:string,id:int,name:string,parent:string,max_installment:?int}
     */
    private function row(string $level, int $id, ?string $name, mixed $max, string $parent = ''): array
    {
        $number = ($max === null || $max === '') ? null : (int) $max;
        if ($number === 0) {
            $number = null;
        }

        return [
            'level' => $level,
            'id' => $id,
            'name' => (string) $name,
            'parent' => $parent,
            'max_installment' => $number,
        ];
    }
}
