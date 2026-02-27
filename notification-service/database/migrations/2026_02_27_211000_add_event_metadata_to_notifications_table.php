<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->uuid('event_id')->nullable()->unique()->after('order_id');
            $table->timestamp('occurred_at')->nullable()->after('event_id');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropUnique(['event_id']);
            $table->dropColumn(['event_id', 'occurred_at']);
        });
    }
};
