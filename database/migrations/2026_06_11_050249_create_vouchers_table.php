<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('vouchers', function (Blueprint $table) {
        $table->id();
        $table->string('code')->unique(); // Kode voucher (misal: ALHAZEN10)
        $table->enum('discount_type', ['percent', 'fixed']); // Persen atau nominal pas
        $table->decimal('discount_value', 15, 2); // Jumlah diskon (misal: 10 untuk 10%, atau 50000 untuk Rp50.000)
        $table->decimal('min_purchase', 15, 2)->default(0); // Syarat minimal belanja
        $table->dateTime('valid_until')->nullable(); // Batas waktu voucher
        $table->boolean('is_active')->default(true); // Status aktif/mati
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
