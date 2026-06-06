<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::insert([
            [
                'kode_barang' => 'BRG001',
                'nama_barang' => 'Batu Bacan',
                'harga' => 15000000,
                'stok' => 5,
                'kategori' => 'Aksesoris',
                'rating' => 4.5,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'kode_barang' => 'BRG002',
                'nama_barang' => 'Batu Lavender',
                'harga' => 200000,
                'stok' => 20,
                'kategori' => 'Aksesoris',
                'rating' => 4.2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'kode_barang' => 'BRG003',
                'nama_barang' => 'Batu Ruby',
                'harga' => 500000,
                'stok' => 15,
                'kategori' => 'Aksesoris',
                'rating' => 4.6,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'kode_barang' => 'BRG004',
                'nama_barang' => 'Batu Safir',
                'harga' => 2500000,
                'stok' => 8,
                'kategori' => 'Elektronik',
                'rating' => 4.3,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'kode_barang' => 'BRG005',
                'nama_barang' => 'Batu Pirus',
                'harga' => 100000,
                'stok' => 50,
                'kategori' => 'Storage',
                'rating' => 4.1,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }
}