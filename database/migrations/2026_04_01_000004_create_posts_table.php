<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->longText('content');
            $table->text('excerpt')->nullable();
            $table->string('featured_image', 500)->nullable();
            $table->unsignedInteger('category_id');
            $table->unsignedInteger('nro_account_id');
            $table->string('author_username', 50);
            $table->string('author_avatar', 255)->nullable();
            $table->enum('status', ['published', 'draft', 'trash'])->default('draft');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('likes')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->foreign('category_id')->references('id')->on('categories');
            $table->index('status');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
