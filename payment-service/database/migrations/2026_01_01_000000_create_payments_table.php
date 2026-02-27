<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('status'); // approved, failed
            $table->integer('retry_count')->default(0);
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('payments');
    }
};
