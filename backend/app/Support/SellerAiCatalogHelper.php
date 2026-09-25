<?php

namespace App\Support;

use App\Models\Category;
use App\Models\ChildCategory;
use App\Models\SubCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Satıcı AI için kategori adı → id eşleştirme ve ürün sorgu filtresi.
 */
class SellerAiCatalogHelper
{
    /**
     * @return array{
     *   category_id:?int,
     *   sub_category_id:?int,
     *   child_category_id:?int,
     *   label:string
     * }|null
     */
    public function resolveFromAction(array $action, ?array $fields = null): ?array
    {
        $fields = $fields ?? [];
        $src = array_merge($action, $fields);

        $childId = (int) ($src['child_category_id'] ?? 0);
        $subId = (int) ($src['sub_category_id'] ?? 0);
        $catId = (int) ($src['category_id'] ?? 0);

        $childName = trim((string) ($src['child_category_name'] ?? $src['child_name'] ?? ''));
        $subName = trim((string) ($src['sub_category_name'] ?? $src['subcategory_name'] ?? $src['alt_kategori'] ?? ''));
        $catName = trim((string) ($src['category_name'] ?? $src['kategori'] ?? ''));

        if ($childId > 0) {
            $child = ChildCategory::query()->where('status', 1)->find($childId);
            if ($child) {
                return [
                    'category_id' => (int) ($child->category_id ?: optional($child->subCategory)->category_id),
                    'sub_category_id' => (int) $child->sub_category_id,
                    'child_category_id' => (int) $child->id,
                    'label' => $child->name,
                ];
            }
        }

        if ($subId > 0) {
            $sub = SubCategory::query()->where('status', 1)->find($subId);
            if ($sub) {
                return [
                    'category_id' => (int) $sub->category_id,
                    'sub_category_id' => (int) $sub->id,
                    'child_category_id' => 0,
                    'label' => $sub->name,
                ];
            }
        }

        if ($catId > 0) {
            $cat = Category::query()->where('status', 1)->find($catId);
            if ($cat) {
                return [
                    'category_id' => (int) $cat->id,
                    'sub_category_id' => 0,
                    'child_category_id' => 0,
                    'label' => $cat->name,
                ];
            }
        }

        if ($childName !== '') {
            $child = $this->fuzzyFind(ChildCategory::query()->where('status', 1)->get(['id', 'name', 'sub_category_id', 'category_id']), $childName);
            if ($child) {
                $sub = SubCategory::query()->find((int) $child->sub_category_id);

                return [
                    'category_id' => (int) ($child->category_id ?: ($sub->category_id ?? 0)),
                    'sub_category_id' => (int) $child->sub_category_id,
                    'child_category_id' => (int) $child->id,
                    'label' => $child->name,
                ];
            }
        }

        if ($subName !== '') {
            $sub = $this->fuzzyFind(SubCategory::query()->where('status', 1)->get(['id', 'name', 'category_id']), $subName);
            if ($sub) {
                return [
                    'category_id' => (int) $sub->category_id,
                    'sub_category_id' => (int) $sub->id,
                    'child_category_id' => 0,
                    'label' => $sub->name,
                ];
            }
        }

        if ($catName !== '') {
            $cat = $this->fuzzyFind(Category::query()->where('status', 1)->get(['id', 'name']), $catName);
            if ($cat) {
                return [
                    'category_id' => (int) $cat->id,
                    'sub_category_id' => 0,
                    'child_category_id' => 0,
                    'label' => $cat->name,
                ];
            }
        }

        return null;
    }

    public function applyToQuery(Builder $query, array $resolved): Builder
    {
        if (! empty($resolved['child_category_id'])) {
            return $query->where('child_category_id', (int) $resolved['child_category_id']);
        }
        if (! empty($resolved['sub_category_id'])) {
            return $query->where('sub_category_id', (int) $resolved['sub_category_id']);
        }
        if (! empty($resolved['category_id'])) {
            return $query->where('category_id', (int) $resolved['category_id']);
        }

        return $query;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     */
    private function fuzzyFind($rows, string $query): ?object
    {
        $normalized = Str::lower(Str::ascii($query));
        $best = null;
        $bestScore = 0.0;

        foreach ($rows as $row) {
            $name = Str::lower(Str::ascii((string) ($row->name ?? '')));
            if ($name === '' ) {
                continue;
            }
            if ($name === $normalized || str_contains($name, $normalized) || str_contains($normalized, $name)) {
                similar_text($name, $normalized, $pct);
                if ($pct > $bestScore) {
                    $bestScore = $pct;
                    $best = $row;
                }
            }
        }

        if ($best && $bestScore >= 45) {
            return $best;
        }

        foreach ($rows as $row) {
            similar_text(Str::lower(Str::ascii((string) ($row->name ?? ''))), $normalized, $pct);
            if ($pct > $bestScore) {
                $bestScore = $pct;
                $best = $row;
            }
        }

        return $bestScore >= 60 ? $best : null;
    }
}
