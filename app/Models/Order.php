<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    //
    protected $guarded = ['id'];

    // Relasi: Pesanan milik siapa?
    public function user() {
        return $this->belongsTo(User::class);
    }

    // Relasi: 1 Pesanan punya banyak barang (items)
    public function items() {
        return $this->hasMany(OrderItem::class);
    }
    
    // Relasi ke tabel order_items
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
