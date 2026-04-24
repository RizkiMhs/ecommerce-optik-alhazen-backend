<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    //
    use HasFactory;
    protected $guarded = ['id'];

    public function product() { return $this->belongsTo(Product::class); }
    public function lensType() { return $this->belongsTo(LensType::class); }
    
    // Relasi: 1 Barang kacamata punya 1 resep mata
    public function prescription() {
        return $this->hasOne(Prescription::class);
    }

    
}
