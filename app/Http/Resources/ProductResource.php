<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'kode_barang' => $this->kode_barang,
            'nama_barang' => $this->nama_barang,
            'harga' => $this->harga,
            'stok' => $this->stok,
            'quantity' => $this->stok, // Fallback for frontend
            'deskripsi' => $this->deskripsi,
            'kategori' => $this->kategori,
            'gambar' => $this->gambar 
                ? url('storage/' . $this->gambar) 
                : ($this->images->first() ? url('storage/' . $this->images->first()->path) : null),
            'expiredDate' => $this->expired_date,
            'rating' => $this->rating,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
