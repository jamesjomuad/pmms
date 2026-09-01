<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_rules', function (Blueprint $table) {
            $table->id();
            $table->string('model_type'); // e.g. 'submittal', 'shop_drawing', 'change_order'
            $table->string('from_status');
            $table->string('to_status');
            $table->string('trigger_event'); // e.g. 'approval_approved', 'manual', 'auto'
            $table->boolean('requires_approval')->default(false);
            $table->string('approver_role')->nullable(); // role required to approve
            $table->json('conditions')->nullable(); // additional conditions
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['model_type', 'from_status', 'to_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_rules');
    }
};
