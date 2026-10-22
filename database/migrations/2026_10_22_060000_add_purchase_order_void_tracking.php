<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('status', 20)->default('active');
            $table->foreignId('purchase_order_id')
                ->nullable()
                ->unique()
                ->constrained('purchase_orders')
                ->nullOnDelete();
        });

        foreach (DB::table('purchase_orders')->select(['id', 'reference'])->orderBy('id')->cursor() as $purchaseOrder) {
            $expenseId = DB::table('expenses')
                ->whereNull('purchase_order_id')
                ->where('description', 'like', 'Purchase order '.$purchaseOrder->reference.' — %')
                ->value('id');

            if ($expenseId) {
                DB::table('expenses')
                    ->where('id', $expenseId)
                    ->update(['purchase_order_id' => $purchaseOrder->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropUnique(['purchase_order_id']);
            $table->dropColumn(['purchase_order_id', 'status']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('voided_at');
        });
    }
};
