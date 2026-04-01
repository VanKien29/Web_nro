<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('post_id');
            $table->unsignedInteger('parent_comment_id')->nullable();
            $table->unsignedInteger('nro_account_id');
            $table->string('username', 50);
            $table->string('avatar_url', 255)->nullable();
            $table->text('content');
            $table->unsignedInteger('likes')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
            $table->index('nro_account_id');
        });

        Schema::create('comment_likes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('comment_id');
            $table->unsignedInteger('nro_account_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['comment_id', 'nro_account_id'], 'unique_like');
            $table->foreign('comment_id')->references('id')->on('comments')->onDelete('cascade');
        });

        Schema::create('post_likes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('post_id');
            $table->unsignedInteger('nro_account_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['post_id', 'nro_account_id'], 'unique_post_like');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_likes');
        Schema::dropIfExists('comment_likes');
        Schema::dropIfExists('comments');
    }
};
