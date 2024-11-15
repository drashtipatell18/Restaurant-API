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
        Schema::create('box_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('box_id')->nullable();
            $table->decimal('open_amount',10,2)->nullable();
            $table->dateTime('open_time')->nullable();
            $table->unsignedBigInteger('open_by')->nullable();
            $table->unsignedBigInteger('close_by')->nullable();
            $table->dateTime('close_time')->nullable();
            $table->decimal('close_amount',10,2)->nullable();
            $table->decimal('collected_amount', 10, 2)->nullable();
            $table->string('payment_id')->nullable();
            $table->string('order_master_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('box_id')->references('id')->on('boxs')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('open_by')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('close_by')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            // $table->foreign('payment_id')->references('id')->on('payments')->onUpdate('cascade')->onDelete('cascade');
            // $table->foreign('order_master_id')->references('id')->on('order_masters')->onUpdate('cascade')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('box_logs');
    }
};
