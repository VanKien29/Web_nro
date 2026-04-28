<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_badge_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('badge_id')->unique();
            $table->string('image_url')->nullable();
            $table->longText('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_badge_assets');
    }
};
