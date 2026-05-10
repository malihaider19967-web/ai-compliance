<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->string('merchant')->nullable();
            $table->dateTime('transaction_date')->nullable();
            $table->decimal('total', 12, 2)->nullable();
            $table->decimal('tax', 12, 2)->nullable();
            $table->string('currency', 8)->nullable();
            $table->string('category')->nullable();
            $table->string('payment_method')->nullable();
            $table->json('line_items')->nullable();
            $table->json('raw_extraction')->nullable();
            $table->string('receipt_path')->nullable();
            $table->json('policy_results')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
