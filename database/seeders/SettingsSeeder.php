<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key_name' => 'site_name', 'value' => 'Ngọc Rồng HDPE', 'description' => 'Tên website'],
            ['key_name' => 'site_description', 'value' => 'Website chính thức của Ngọc Rồng HDPE', 'description' => 'Mô tả website'],
            ['key_name' => 'site_keywords', 'value' => 'game, nro, dragon ball', 'description' => 'Từ khóa SEO'],
            ['key_name' => 'facebook_url', 'value' => 'https://facebook.com', 'description' => 'Link Facebook fanpage'],
            ['key_name' => 'facebook_group_url', 'value' => 'https://facebook.com/groups', 'description' => 'Link Facebook group'],
            ['key_name' => 'ios_download_url', 'value' => '#', 'description' => 'Link tải iOS'],
            ['key_name' => 'android_download_url', 'value' => '#', 'description' => 'Link tải Android'],
            ['key_name' => 'apk_download_url', 'value' => '#', 'description' => 'Link tải APK'],
            ['key_name' => 'payment_url', 'value' => '#', 'description' => 'Link nạp tiền'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key_name' => $setting['key_name']],
                $setting
            );
        }
    }
}
