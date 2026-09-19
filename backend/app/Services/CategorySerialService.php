<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CategorySerialService
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  list<int|string>  $ids  Sıralı id listesi (ilk = en üst)
     */
    public function reorder(string $modelClass, array $ids): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids), fn ($id) => $id > 0));
        if ($ids === []) {
            return;
        }

        DB::transaction(function () use ($modelClass, $ids) {
            foreach ($ids as $index => $id) {
                $modelClass::query()
                    ->where('id', $id)
                    ->update(['serial' => $index + 1]);
            }
        });
    }

    public function nextSerial(string $modelClass): int
    {
        return ((int) $modelClass::query()->max('serial')) + 1;
    }
}
