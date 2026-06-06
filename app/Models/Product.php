<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'harga',
        'stok',
        'deskripsi',
        'gambar',
        'kategori',
        'expired_date',
        'rating',
    ];

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
}
