<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $datasetPath = database_path('data/turkey-cities.json');

        if (! File::exists($datasetPath)) {
            return;
        }

        $cities = json_decode(File::get($datasetPath), true);

        if (! is_array($cities) || $cities === []) {
            return;
        }

        DB::transaction(function () use ($cities) {
            $now = now();

            $country = DB::table('countries')->where('slug', 'turkiye')->first();

            if (! $country) {
                $countryId = DB::table('countries')->insertGetId([
                    'name' => 'Türkiye',
                    'slug' => 'turkiye',
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $countryId = $country->id;

                DB::table('countries')
                    ->where('id', $countryId)
                    ->update([
                        'name' => 'Türkiye',
                        'status' => 1,
                        'updated_at' => $now,
                    ]);
            }

            foreach ($cities as $stateIndex => $state) {
                $stateName = trim((string) ($state['label'] ?? $state['value'] ?? ''));

                if ($stateName === '') {
                    continue;
                }

                $stateRow = DB::table('country_states')
                    ->where('country_id', $countryId)
                    ->where('slug', Str::slug($stateName))
                    ->first();

                if (! $stateRow) {
                    $stateId = DB::table('country_states')->insertGetId([
                        'country_id' => $countryId,
                        'name' => $stateName,
                        'slug' => Str::slug($stateName),
                        'status' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    $stateId = $stateRow->id;

                    DB::table('country_states')
                        ->where('id', $stateId)
                        ->update([
                            'name' => $stateName,
                            'status' => 1,
                            'updated_at' => $now,
                        ]);
                }

                foreach (($state['districts'] ?? []) as $districtName) {
                    $districtName = trim((string) $districtName);

                    if ($districtName === '') {
                        continue;
                    }

                    $cityRow = DB::table('cities')
                        ->where('country_state_id', $stateId)
                        ->where('name', $districtName)
                        ->first();

                    if (! $cityRow) {
                        DB::table('cities')->insert([
                            'country_state_id' => $stateId,
                            'name' => $districtName,
                            'slug' => Str::slug($districtName),
                            'status' => 1,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);

                        continue;
                    }

                    DB::table('cities')
                        ->where('id', $cityRow->id)
                        ->update([
                            'name' => $districtName,
                            'status' => 1,
                            'updated_at' => $now,
                        ]);
                }
            }
        });
    }

    public function down(): void
    {
        $country = DB::table('countries')->where('slug', 'turkiye')->first();

        if (! $country) {
            return;
        }

        $stateIds = DB::table('country_states')
            ->where('country_id', $country->id)
            ->pluck('id');

        if ($stateIds->isNotEmpty()) {
            DB::table('cities')->whereIn('country_state_id', $stateIds)->delete();
            DB::table('country_states')->whereIn('id', $stateIds)->delete();
        }

        DB::table('countries')->where('id', $country->id)->delete();
    }
};
