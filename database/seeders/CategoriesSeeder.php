<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Tin tức', 'slug' => 'tin-tuc', 'description' => 'Các tin tức mới nhất về game'],
            ['name' => 'Sự kiện', 'slug' => 'su-kien', 'description' => 'Các sự kiện đặc biệt trong game'],
            ['name' => 'Hướng dẫn', 'slug' => 'huong-dan', 'description' => 'Hướng dẫn chơi game'],
        ];

        foreach ($categories as $cat) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $cat['slug']],
                $cat
            );
        }
    }
}
