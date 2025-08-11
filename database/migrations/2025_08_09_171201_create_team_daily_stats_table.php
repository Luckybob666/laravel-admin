<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('team_daily_stats', function (Blueprint $table) {
            $table->id();

            $table->date('date');                        // 日期
            $table->foreignId('team_id')->constrained('teams')->cascadeOnUpdate()->restrictOnDelete();

            // 基础费用与业务量（暂不计算，仅存储）
            $table->decimal('fixed_cost', 12, 2)->default(0);          // 固定费用
            $table->decimal('var_personnel_cost', 12, 2)->default(0);  // 浮动人员费用
            $table->decimal('var_server_ip_cost', 12, 2)->default(0);  // 服务器与IP费用
            $table->decimal('ad_cost', 12, 2)->default(0);             // 推广广告费用

            $table->unsignedBigInteger('msg_count')->default(0);       // 发送消息条数
            $table->decimal('unit_price', 10, 4)->nullable();          // 发送单价（可空，后续可用团队/全局默认）

            $table->decimal('withdraw_cost', 12, 2)->default(0);       // 挂机提款支出

            $table->unsignedInteger('online_today')->nullable();       // 今日在线人数
            $table->unsignedInteger('online_yesterday')->nullable();   // 昨日在线绑定人数

            $table->text('notes')->nullable();

            $table->timestamps();

            // 同一天+同团队建议唯一（可按需先加注释，视业务而定）
            $table->unique(['date', 'team_id'], 'uniq_date_team');

            $table->index(['team_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_daily_stats');
    }
};
