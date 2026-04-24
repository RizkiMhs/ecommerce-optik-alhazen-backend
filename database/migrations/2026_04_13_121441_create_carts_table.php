<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            
            // Relasi Utama
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('lens_type_id')->nullable()->constrained('lens_types')->nullOnDelete();
            
            $table->integer('qty')->default(1);

            // Data Resep (Nullable karena bisa jadi pelanggan hanya beli cairan/aksesoris)
            $table->string('sph_right')->nullable();
            $table->string('sph_left')->nullable();
            $table->string('cyl_right')->nullable();
            $table->string('cyl_left')->nullable();
            $table->string('axis_right')->nullable();
            $table->string('axis_left')->nullable();
            $table->string('pd')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};