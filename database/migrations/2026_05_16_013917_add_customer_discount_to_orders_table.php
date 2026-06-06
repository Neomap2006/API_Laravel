<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Menambahkan pelanggan_id setelah user_id
            $table->foreignId('pelanggan_id')->after('user_id')->nullable()->constrained('pelanggan')->nullOnDelete();
            
            // Menambahkan discount dan discount_amount setelah total_price
            $table->decimal('discount', 5, 2)->default(0)->after('total_price'); // persentase, misal 5.00
            $table->decimal('discount_amount', 15, 2)->default(0)->after('discount'); // nominal diskon
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['pelanggan_id']);
            $table->dropColumn(['pelanggan_id', 'discount', 'discount_amount']);
        });
    }
};
