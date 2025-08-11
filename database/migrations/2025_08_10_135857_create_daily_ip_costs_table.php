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
        Schema::create('daily_ip_costs', function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('日期');
            $table->decimal('amount', 12, 2)->comment('IP+服务器费用');
            $table->timestamps();
        
            $table->unique('date'); // 每日唯一
            // 可选：非负约束（MySQL 8+）
            // DB::statement('ALTER TABLE daily_ip_costs ADD CONSTRAINT chk_amount_nonneg CHECK (amount >= 0)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_ip_costs');
    }
};
