<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_expenses', function (Blueprint $table) {
            $table->id();

            // 约定用该月第一天作为“月份”存储，便于唯一索引与查询
            $table->date('month_date')->comment('月份（存该月1日，如 2025-08-01 表示 2025-08 月）');

            // 当月固定费用总额
            $table->decimal('amount', 12, 2)->comment('当月固定费用总额');

            $table->string('note')->nullable()->comment('备注');
            $table->timestamps();

            // 每月仅一条；自定义索引名便于维护
            $table->unique('month_date', 'fixed_expenses_month_date_unique');
        });

        // 可选：非负约束（MySQL 8+ 有效；低版本会忽略，不报错）
        try {
            DB::statement('ALTER TABLE fixed_expenses
                ADD CONSTRAINT chk_fixed_expenses_amount_nonneg CHECK (amount >= 0)');
        } catch (\Throwable $e) {
            // 忽略旧版本或不支持 CHECK 的数据库
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_expenses');
    }
};
