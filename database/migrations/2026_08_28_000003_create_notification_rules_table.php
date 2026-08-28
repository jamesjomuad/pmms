<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('event_key')->unique(); // e.g. 'project.stage_changed', 'approval.requested'
            $table->string('label'); // human-readable name
            $table->string('notify_role')->nullable(); // project_role to notify, null = all team members
            $table->string('notify_channel')->default('in-app'); // email, in-app, sms
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
