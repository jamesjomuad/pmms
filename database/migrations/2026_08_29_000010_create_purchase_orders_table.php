<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_quotation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('po_number');
            $table->date('issued_date')->nullable();
            $table->decimal('cost', 12, 2);
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->string('status')->default('issued');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['equipment_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
