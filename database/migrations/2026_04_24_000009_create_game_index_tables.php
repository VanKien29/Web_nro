<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_item_indexes', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('name', 191);
            $table->string('normalized_name', 191)->index();
            $table->integer('type')->nullable()->index();
            $table->unsignedInteger('icon_id')->default(0)->index();
            $table->integer('part')->nullable();
            $table->integer('head')->nullable();
            $table->integer('body')->nullable();
            $table->integer('leg')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_up_to_up')->default(false);
            $table->timestamps();
        });

        Schema::create('game_item_option_indexes', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('name', 191);
            $table->string('normalized_name', 191)->index();
            $table->timestamps();
        });

        Schema::create('game_item_type_indexes', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('name', 191);
            $table->unsignedInteger('item_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_item_type_indexes');
        Schema::dropIfExists('game_item_option_indexes');
        Schema::dropIfExists('game_item_indexes');
    }
};
