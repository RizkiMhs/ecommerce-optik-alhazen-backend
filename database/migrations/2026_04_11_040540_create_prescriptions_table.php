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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->string('sph_right')->nullable(); // Minus/Plus Kanan
            $table->string('sph_left')->nullable();  // Minus/Plus Kiri
            $table->string('cyl_right')->nullable(); // Silinder Kanan
            $table->string('cyl_left')->nullable();  // Silinder Kiri
            $table->string('axis_right')->nullable();
            $table->string('axis_left')->nullable();
            $table->string('pd')->nullable();        // Pupillary Distance
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
