<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_action_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_user_id')->nullable()->index();
            $table->string('admin_username', 100)->nullable()->index();
            $table->string('action', 60)->index();
            $table->string('target_type', 60)->index();
            $table->string('target_id', 60)->nullable()->index();
            $table->string('target_label', 180)->nullable();
            $table->string('summary', 255)->nullable();
            $table->longText('before_state')->nullable();
            $table->longText('after_state')->nullable();
            $table->longText('meta')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_action_logs');
    }
};
