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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('category', 100);
            $table->string('description', 255);
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->string('payment_method', 30)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('purchase_order_id')
                ->nullable()
                ->unique()
                ->constrained('purchase_orders')
                ->nullOnDelete();
            $table->timestamps();
            $table->index(['expense_date', 'id'], 'expenses_date_id_index');
            $table->index(['amount', 'expense_date', 'id'], 'expenses_amount_date_id_index');
            $table->index(['status', 'expense_date', 'amount'], 'expenses_status_date_amount_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
