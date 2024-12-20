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
        Schema::create('production_centers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->integer('printer_code')->nullable();
            $table->string('order_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('admin_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_centers');
    }
};
