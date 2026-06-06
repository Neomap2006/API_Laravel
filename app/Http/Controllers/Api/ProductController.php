<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Models\ProductImage;

class ProductController extends Controller
{
    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');

            $filename = time() . '.' . $file->getClientOriginalName();

            $destinationPath = storage_path('app/public/products/' . $filename);

            //engine resize image
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());

            $image->scale(width: 800);

            $image->save($destinationPath, quality: 80);

            $data['gambar'] = 'products/' . $filename;
        }

        $product = Product::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Produk Berhasil Dibuat',
            'data' => new ProductResource($product)
        ], 201);
    }

    public function index(Request $request)
    {
        $search = $request->query('search');
        $kategori = $request->query('kategori');
        $sort = $request->query('sort');

        $products = Product::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama_barang', 'like', '%' . $search . '%')
                        ->orWhere('kode_barang', 'like', '%' . $search . '%');
                });
            })
            ->when($kategori, function ($query, $kategori) {
                $query->where('kategori', $kategori);
            })
            ->when($sort, function ($query, $sort) {
                if ($sort == 'harga_asc') {
                    $query->orderBy('harga', 'asc');
                } elseif ($sort == 'harga_desc') {
                    $query->orderBy('harga', 'desc');
                }
            }, function ($query) {
                $query->latest();
            })
            ->with('images')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'List Produk',
            'data' => ProductResource::collection($products)
        ]);
    }

    public function update(StoreProductRequest $request, $id)
    {
        $product = Product::findOrFail($id);
        $data = $request->validated();


        if (empty($data)) {
            return response()->json([
                'message' => 'Tidak ada data yang diupdate',
            ]);
        }

        // cel apakah ada gambar baru
        if ($request->hasFile('gambar')) {

            // hapus gambar lama
            if ($product->gambar) {
                Storage::disk('public')->delete($product->gambar);
            }

            $file = $request->file('gambar');

            $filename = time() . '.' . $file->getClientOriginalName();

            $destinationPath = storage_path('app/public/products/' . $filename);

            // resize engine
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());

            // pakai scale
            $image->scale(width: 800);

            // save + compress
            $image->save($destinationPath, quality: 80);

            // simpan ke DB
            $data['gambar'] = 'products/' . $filename;
        }

        $product->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Produk Berhasil Diupdate',
            'data' => new ProductResource($product)
        ]);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Hapus gambar utama dari storage
        if ($product->gambar) {
            Storage::disk('public')->delete($product->gambar);
        }

        // Hapus semua gambar dari galeri (produk_images) di storage
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk Berhasil Dihapus'
        ]);
    }

    public function uploadImages(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'gambar' => 'required|array',
            'gambar.*' => 'image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $manager = new ImageManager(new Driver());

        $images = [];

        foreach ($request->file('gambar') as $file) {
            $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();

            // Path penyimpanan
            $destinationPath = storage_path('app/public/products/' . $filename);

            $image = $manager->read($file->getRealPath());

            if ($image->width() > 800) {
                $image->scale(width: 800);
            }

            $image->save($destinationPath, quality: 80);

            $path = 'products/' . $filename;

            // Simpan ke tabel relasi
            $img = ProductImage::create([
                'product_id' => $product->id,
                'path' => $path
            ]);

            $images[] = $img;
        }

        return response()->json([
            'success' => true,
            'message' => 'Multiple images berhasil diupload',
            'data' => $images
        ]);
    }

    public function updateImage(Request $request, $id)
    {
        $productImage = ProductImage::findOrFail($id);

        $request->validate([
            'gambar' => 'required|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        // Hapus gambar lama dari storage
        if (Storage::disk('public')->exists($productImage->path)) {
            Storage::disk('public')->delete($productImage->path);
        }

        $file = $request->file('gambar');
        $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
        $destinationPath = storage_path('app/public/products/' . $filename);

        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getRealPath());

        if ($image->width() > 800) {
            $image->scale(width: 800);
        }

        $image->save($destinationPath, quality: 80);

        $path = 'products/' . $filename;

        // Update ke database
        $productImage->update([
            'path' => $path
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gambar galeri berhasil diupdate',
            'data' => $productImage
        ]);
    }

    public function destroyImage($id)
    {
        $productImage = ProductImage::findOrFail($id);

        // Hapus gambar dari storage
        if (Storage::disk('public')->exists($productImage->path)) {
            Storage::disk('public')->delete($productImage->path);
        }

        // Hapus record dari database
        $productImage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Gambar galeri berhasil dihapus'
        ]);
    }

    public function bulkUpload(Request $request)
    {
        $request->validate([
            'gambar' => 'required|array',
            'gambar.*' => 'image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $manager = new ImageManager(new Driver());
        $results = [
            'success' => [],
            'failed' => []
        ];

        foreach ($request->file('gambar') as $file) {
            $originalName = $file->getClientOriginalName();
            $filenameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);

            // Cari produk berdasarkan kode_barang atau nama_barang
            $product = Product::where('kode_barang', $filenameWithoutExt)
                ->orWhere('nama_barang', 'like', $filenameWithoutExt)
                ->first();

            if ($product) {
                // Hapus gambar lama jika ada
                if ($product->gambar) {
                    Storage::disk('public')->delete($product->gambar);
                }

                $filename = time() . '_' . uniqid() . '_' . $originalName;
                $destinationPath = storage_path('app/public/products/' . $filename);

                $image = $manager->read($file->getRealPath());
                if ($image->width() > 800) {
                    $image->scale(width: 800);
                }
                $image->save($destinationPath, quality: 80);

                $path = 'products/' . $filename;

                // Update Gambar Utama
                $product->update([
                    'gambar' => $path
                ]);

                // Simpan ke Tabel Galeri (produk_images)
                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $path
                ]);

                $results['success'][] = [
                    'filename' => $originalName,
                    'product' => $product->nama_barang
                ];
            } else {
                $results['failed'][] = [
                    'filename' => $originalName,
                    'reason' => 'Produk tidak ditemukan'
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Proses bulk upload selesai',
            'results' => $results
        ]);
    }
}
