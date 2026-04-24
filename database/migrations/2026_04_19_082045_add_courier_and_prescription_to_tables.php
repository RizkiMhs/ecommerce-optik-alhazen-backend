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
        // 1. Tambah kolom courier di tabel orders
        Schema::table('orders', function (Blueprint $table) {
            // Kita beri nullable() untuk mencegah error jika di database sudah ada data order lama
            $table->string('courier')->nullable()->after('address_snapshot'); 
        });

        // 2. Tambah kolom prescription_data di tabel order_items
        Schema::table('order_items', function (Blueprint $table) {
            $table->text('prescription_data')->nullable()->after('qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Digunakan jika sewaktu-waktu kita ingin me-rollback (membatalkan) kolom ini
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('courier');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('prescription_data');
        });
    }
};