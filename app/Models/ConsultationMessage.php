<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConsultationMessage extends Model
{
    //
    use HasFactory;

    // Kolom yang diizinkan untuk diisi secara otomatis
    protected $fillable = [
        'user_id',
        'message',
        'is_admin',
    ];

    // Relasi balik ke model User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
