<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PembelianController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Pembelian::with(['supplier', 'user', 'items.produk'])
            ->orderBy('tanggal_pembelian', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('no_pembelian', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn($s) => $s->where('nama', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('tanggal_pembelian', '>=', $request->dari_tanggal);
        }

        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('tanggal_pembelian', '<=', $request->sampai_tanggal);
        }

        $pembelians = $query->paginate(15);
        return response()->json($pembelians);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'supplier_id'              => 'required|exists:suppliers,id',
            'tanggal_pembelian'        => 'required|date',
            'keterangan'               => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.produk_id'        => 'nullable|exists:products,id',
            'items.*.quantity'         => 'required|integer|min:1',
            'items.*.harga_beli'       => 'required|numeric|min:0',
            // Field produk baru (opsional, jika produk_id kosong)
            'items.*.new_produk.kode_barang' => 'nullable|string|max:50',
            'items.*.new_produk.nama_barang' => 'nullable|string|max:255',
            'items.*.new_produk.kategori'    => 'nullable|string|max:100',
            'items.*.new_produk.harga'       => 'nullable|numeric|min:0',
            'items.*.new_produk.stok'        => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $noPembelian = 'PBL-' . date('Ymd') . '-' . str_pad(
                Pembelian::whereDate('created_at', today())->count() + 1,
                4, '0', STR_PAD_LEFT
            );

            $totalHarga = 0;
            $itemsData = [];

            foreach ($request->items as $item) {
                $produkId = $item['produk_id'] ?? null;

                // Buat produk baru jika tidak ada produk_id
                if (!$produkId && !empty($item['new_produk']['nama_barang'])) {
                    $np = $item['new_produk'];

                    // Auto-generate kode jika kosong
                    if (empty($np['kode_barang'])) {
                        $np['kode_barang'] = 'BRG-' . strtoupper(substr(md5(uniqid()), 0, 6));
                    }

                    $produkBaru = Product::create([
                        'kode_barang' => $np['kode_barang'],
                        'nama_barang' => $np['nama_barang'],
                        'kategori'    => $np['kategori'] ?? null,
                        'harga'       => $np['harga'] ?? $item['harga_beli'],
                        'stok'        => $np['stok'] ?? 0,
                        'rating'      => 0,
                    ]);

                    $produkId = $produkBaru->id;
                }

                if (!$produkId) continue;

                $subtotal   = $item['quantity'] * $item['harga_beli'];
                $totalHarga += $subtotal;
                $itemsData[] = [
                    'produk_id'  => $produkId,
                    'quantity'   => $item['quantity'],
                    'harga_beli' => $item['harga_beli'],
                    'subtotal'   => $subtotal,
                ];
            }

            if (empty($itemsData)) {
                DB::rollBack();
                return response()->json(['message' => 'Tidak ada item valid dalam pembelian'], 422);
            }

            $pembelian = Pembelian::create([
                'no_pembelian'      => $noPembelian,
                'supplier_id'       => $request->supplier_id,
                'user_id'           => auth()->id() ?? 1,
                'tanggal_pembelian' => $request->tanggal_pembelian,
                'total_harga'       => $totalHarga,
                'status'            => 'pending',
                'keterangan'        => $request->keterangan ?? null,
            ]);

            foreach ($itemsData as $item) {
                $pembelian->items()->create($item);
            }

            DB::commit();

            return response()->json([
                'data'    => $pembelian->load(['supplier', 'user', 'items.produk']),
                'message' => 'Pembelian berhasil dibuat',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal membuat pembelian: ' . $e->getMessage()], 500);
        }
    }

    public function show(Pembelian $pembelian): JsonResponse
    {
        return response()->json([
            'data' => $pembelian->load(['supplier', 'user', 'items.produk']),
        ]);
    }

    public function updateStatus(Request $request, Pembelian $pembelian): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,diterima,dibatalkan',
        ]);

        if ($pembelian->status === 'diterima') {
            return response()->json(['message' => 'Pembelian yang sudah diterima tidak dapat diubah statusnya'], 422);
        }

        DB::beginTransaction();
        try {
            $pembelian->update(['status' => $validated['status']]);

            if ($validated['status'] === 'diterima') {
                foreach ($pembelian->items as $item) {
                    $produk = Product::find($item->produk_id);
                    if ($produk) {
                        $stokField = $produk->stok !== null ? 'stok' : 'quantity';
                        $produk->increment($stokField, $item->quantity);
                    }
                }
            }

            DB::commit();
            return response()->json([
                'data'    => $pembelian->fresh(['supplier', 'user', 'items.produk']),
                'message' => 'Status pembelian berhasil diperbarui',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal update status: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Pembelian $pembelian): JsonResponse
    {
        if ($pembelian->status === 'diterima') {
            return response()->json(['message' => 'Pembelian yang sudah diterima tidak dapat dihapus'], 422);
        }

        $pembelian->delete();
        return response()->json(['message' => 'Pembelian berhasil dihapus']);
    }
}
