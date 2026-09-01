<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_item_id')->constrained()->cascadeOnDelete();
            $table->string('supplier_name');
            $table->decimal('quoted_cost', 12, 2);
            $table->integer('quoted_lead_time_days')->nullable();
            $table->date('quote_date')->nullable();
            $table->string('status')->default('received');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['equipment_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_quotations');
    }
};
