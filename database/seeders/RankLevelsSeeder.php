<?php

namespace Database\Seeders;

use App\Models\RankLevel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RankLevelsSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['code' => 'INSTRUCTOR_C', 'title' => 'Instructor C', 'order_no' => 1],
            ['code' => 'INSTRUCTOR_B', 'title' => 'Instructor B', 'order_no' => 2],
            ['code' => 'INSTRUCTOR_A', 'title' => 'Instructor A', 'order_no' => 3],
            ['code' => 'ASST_PROF_C', 'title' => 'Assistant Professor C', 'order_no' => 4],
            ['code' => 'ASST_PROF_B', 'title' => 'Assistant Professor B', 'order_no' => 5],
            ['code' => 'ASST_PROF_A', 'title' => 'Assistant Professor A', 'order_no' => 6],
            ['code' => 'ASSOC_PROF_C', 'title' => 'Associate Professor C', 'order_no' => 7],
            ['code' => 'ASSOC_PROF_B', 'title' => 'Associate Professor B', 'order_no' => 8],
            ['code' => 'ASSOC_PROF_A', 'title' => 'Associate Professor A', 'order_no' => 9],
            ['code' => 'FULL_PROF_C', 'title' => 'Full Professor C', 'order_no' => 10],
            ['code' => 'FULL_PROF_B', 'title' => 'Full Professor B', 'order_no' => 11],
            ['code' => 'FULL_PROF_A', 'title' => 'Full Professor A', 'order_no' => 12],
        ];

        DB::transaction(function () use ($levels): void {
            // Move existing order_no values out of the 1..N range to avoid unique collisions during reorder.
            RankLevel::query()
                ->whereIn('code', array_column($levels, 'code'))
                ->get()
                ->each(function (RankLevel $rank): void {
                    $rank->update(['order_no' => $rank->order_no + 1000]);
                });

            foreach ($levels as $level) {
                RankLevel::updateOrCreate(
                    ['code' => $level['code']],
                    ['title' => $level['title'], 'order_no' => $level['order_no']]
                );
            }
        });
    }
}
