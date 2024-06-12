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
        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_master_id')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->decimal('amount',10,2)->nullable();
            $table->decimal('cost',10,2)->nullable();
            $table->integer('quantity')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('order_master_id')->references('id')->on('order_masters')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
