<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //
    protected $guarded = ['id'];
    // 💡 Pastikan image_url ikut dikirim
    protected $appends = ['image_url'];

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    // 💡 FUNGSI YANG DIPERBAIKI: Menggunakan image_name sesuai database Anda
    public function getImageUrlAttribute()
    {
        // Ambil data dari relasi primaryImage
        $primary = $this->primaryImage;

        if ($primary && $primary->image_name) {
            return asset('storage/' . $primary->image_name);
        }

        // Jika tidak ada gambar utama, ambil gambar apa saja yang pertama
        $firstImage = $this->images->first();
        if ($firstImage && $firstImage->image_name) {
            return asset('storage/' . $firstImage->image_name);
        }

        return null;
    }
}
