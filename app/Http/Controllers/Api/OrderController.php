<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderCollection;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('user', 'items.product')->latest()->get();

        return new OrderCollection($orders);
    }

    public function store(StoreOrderRequest $request)
    {
        DB::beginTransaction();

        try {
            $discount = 0;
            $discountAmount = 0;

            // Cek apakah ada pelanggan member -> dapat diskon 5%
            if ($request->pelanggan_id) {
                $discount = 5;
            }

            $order = Order::create([
                'user_id'          => $request->user_id,
                'pelanggan_id'     => $request->pelanggan_id,
                'order_code'       => 'ORD-' . time(),
                'total_price'      => 0,
                'discount'         => $discount,
                'discount_amount'  => 0,
                'shipping_address' => $request->shipping_address,
                'status'           => 'pending',
            ]);

            $subtotalBefore = 0;

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['produk_id']);

                $subtotal = $product->harga * $item['quantity'];
                $subtotalBefore += $subtotal;

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'price'      => $product->harga,
                    'subtotal'   => $subtotal,
                ]);
            }

            // Hitung diskon dan total akhir
            $discountAmount = $subtotalBefore * ($discount / 100);
            $totalAfterDiscount = $subtotalBefore - $discountAmount;

            $order->update([
                'total_price'     => $totalAfterDiscount,
                'discount_amount' => $discountAmount,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibuat',
                'data'    => new OrderResource($order->load('user', 'pelanggan', 'items.product'))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat order',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $order = Order::with('user', 'items.product')->findOrFail($id);

        return new OrderResource($order);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,shipped,completed,cancelled',
        ]);

        $order = Order::findOrFail($id);
        $order->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Status berhasil diupdate',
            'data' => $order
        ]);
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json([
            'message' => 'Order berhasil dihapus'
        ]);
    }
}
