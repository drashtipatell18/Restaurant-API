<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_master_id')->nullable();
            $table->string('rut')->nullable();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('business_name')->nullable();
            $table->string('ltda')->nullable();
            $table->string('tour')->nullable();
            $table->longText('address')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('type')->nullable();
            $table->decimal('amount',10,2)->nullable();
            $table->decimal('return',10,2)->nullable();
            $table->decimal('tax',10,2)->nullable();
            $table->foreign('order_master_id')->references('id')->on('order_masters')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
