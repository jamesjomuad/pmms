<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('inspected_date')->nullable();
            $table->string('result')->default('passed');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['equipment_item_id', 'result']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_inspections');
    }
};
