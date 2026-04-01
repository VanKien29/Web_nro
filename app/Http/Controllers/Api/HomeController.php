<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Slide;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        $slides = Cache::remember('home_slides', 3600, function () {
            return Slide::active()->orderBy('sort_order')->get();
        });

        $tinTuc = Cache::remember('home_posts_tin_tuc', 1800, function () {
            return Post::published()
                ->whereHas('category', fn($q) => $q->where('slug', 'tin-tuc'))
                ->with('category:id,name')
                ->orderByDesc('published_at')
                ->limit(5)
                ->get(['id', 'title', 'slug', 'created_at', 'category_id']);
        });

        $suKien = Cache::remember('home_posts_su_kien', 1800, function () {
            return Post::published()
                ->whereHas('category', fn($q) => $q->where('slug', 'su-kien'))
                ->with('category:id,name')
                ->orderByDesc('published_at')
                ->limit(5)
                ->get(['id', 'title', 'slug', 'created_at', 'category_id']);
        });

        $huongDan = Cache::remember('home_posts_huong_dan', 1800, function () {
            return Post::published()
                ->whereHas('category', fn($q) => $q->where('slug', 'huong-dan'))
                ->with('category:id,name')
                ->orderByDesc('published_at')
                ->limit(5)
                ->get(['id', 'title', 'slug', 'created_at', 'category_id']);
        });

        $settings = Cache::remember('home_settings', 86400, function () {
            $keys = [
                'site_name', 'site_description', 'site_keywords',
                'facebook_url', 'facebook_group_url',
                'ios_download_url', 'android_download_url', 'apk_download_url',
                'payment_url',
            ];
            $result = [];
            foreach ($keys as $key) {
                $result[$key] = Setting::getValue($key);
            }
            return $result;
        });

        return response()->json([
            'slides' => $slides,
            'tin_tuc' => $tinTuc,
            'su_kien' => $suKien,
            'huong_dan' => $huongDan,
            'settings' => $settings,
        ]);
    }

    public function postDetail(string $slug)
    {
        $post = Post::published()
            ->with('category:id,name')
            ->where('slug', $slug)
            ->first();

        if (!$post) {
            return response()->json(['ok' => false, 'message' => 'Bài viết không tồn tại'], 404);
        }

        $post->increment('views');

        return response()->json(['ok' => true, 'data' => $post]);
    }
}
