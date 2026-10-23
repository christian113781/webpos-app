<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_item_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_item_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('product_batch_id')
                ->nullable()
                ->constrained('product_batches')
                ->nullOnDelete();
            $table->string('batch_number');
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 12, 2);
            $table->date('received_date');
            $table->date('expiry_date')->nullable();
            $table->timestamps();

            $table->unique(['purchase_order_item_id', 'batch_number'], 'po_item_batch_number_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_item_batches');
    }
};
