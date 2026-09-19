<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CategorySerialService
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  list<int|string>  $ids  Sıralı id listesi (ilk = en üst)
     * @param  array<string, mixed>  $scope  Örn: ['category_id' => 5]
     */
    public function reorder(string $modelClass, array $ids, array $scope = []): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids), fn ($id) => $id > 0));
        if ($ids === []) {
            return;
        }

        DB::transaction(function () use ($modelClass, $ids, $scope) {
            $query = $modelClass::query()->whereIn('id', $ids);
            foreach ($scope as $column => $value) {
                if ($value !== null && $value !== '') {
                    $query->where($column, $value);
                }
            }
            $allowed = $query->pluck('id')->map(fn ($id) => (int) $id)->all();
            $ordered = array_values(array_filter($ids, fn ($id) => in_array($id, $allowed, true)));

            foreach ($ordered as $index => $id) {
                $modelClass::query()
                    ->where('id', $id)
                    ->update(['serial' => $index + 1]);
            }
        });
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $scope
     */
    public function nextSerial(string $modelClass, array $scope = []): int
    {
        $query = $modelClass::query();
        foreach ($scope as $column => $value) {
            if ($value !== null && $value !== '') {
                $query->where($column, $value);
            }
        }

        return ((int) $query->max('serial')) + 1;
    }
}
