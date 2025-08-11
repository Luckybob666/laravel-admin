<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();          // 团队名称，唯一
            $table->string('code')->nullable();        // 可选：团队编码
            $table->boolean('is_active')->default(true); // 启用/停用
            $table->foreignId('leader_id')             // 可选：负责人（users 表）
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->json('settings')->nullable();      // 预留：默认单价/汇率/策略等
            $table->timestamps();

            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
