<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            // 只存日期
            $table->date('rate_date')->index();

            // 统一基准：USD
            $table->string('base_currency', 3)->default('USD')->index();

            // 三个目标币种（1 USD = ?）
            $table->decimal('usd_to_cny', 18, 6)->default(7);
            $table->decimal('usd_to_pkr', 18, 6)->default(290);
            $table->decimal('usd_to_inr', 18, 6)->default(87);

            // 元数据
            $table->string('source', 16)->default('system'); // system | manual
            $table->boolean('is_locked')->default(false);
            $table->text('notes')->nullable();

            $table->timestamps();

            // 每天一条（按基准币种唯一）
            $table->unique(['rate_date', 'base_currency'], 'uniq_date_base');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
