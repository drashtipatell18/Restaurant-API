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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->unsignedBigInteger('production_center_id')->nullable();
            $table->unsignedBigInteger('sub_family_id')->nullable();
            $table->unsignedBigInteger('family_id')->nullable();
            $table->decimal('cost_price',10,2)->nullable();
            $table->decimal('sale_price',10,2)->nullable();
            $table->longText('description')->nullable();
            $table->longText('image')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->timestamps();
            $table->foreign('sub_family_id')->references('id')->on('subfamilies')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('family_id')->references('id')->on('families')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('production_center_id')->references('id')->on('production_centers')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
