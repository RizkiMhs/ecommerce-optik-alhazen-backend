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
    Schema::create('consultation_messages', function (Blueprint $table) {
        $table->id();
        
        // Relasi ke tabel users (kustomer)
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        
        // Isi pesan dari kustomer atau admin
        $table->text('message');
        
        // Penanda siapa yang mengirim: 
        // 0 = Kustomer, 1 = Admin (Bisa pakai boolean/tinyInteger)
        $table->boolean('is_admin')->default(false);
        
        $table->timestamps(); // Otomatis membuat kolom created_at dan updated_at
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_messages');
    }
};
