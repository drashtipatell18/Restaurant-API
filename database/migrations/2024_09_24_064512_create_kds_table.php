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
        Schema::create('kds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('table_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('box_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('order_type')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('status')->nullable();
            $table->string('tip')->nullable();
            $table->string('discount')->nullable();
            $table->string('delivery_cost')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('person')->nullable();
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('order_id')->references('id')->on('order_masters')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('table_id')->references('id')->on('restauranttables')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('box_id')->references('id')->on('boxs')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kds');
    }
};
