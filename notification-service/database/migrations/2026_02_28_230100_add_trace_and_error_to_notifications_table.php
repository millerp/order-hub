<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->uuid('trace_id')->nullable()->after('occurred_at');
            $table->text('error_message')->nullable()->after('status');
            $table->index('trace_id');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['trace_id']);
            $table->dropColumn(['trace_id', 'error_message']);
        });
    }
};
