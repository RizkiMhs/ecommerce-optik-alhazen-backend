<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends BaseWidget
{
    // 💡 Agar widget ini muncul paling atas di bawah ucapan "Welcome"
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // 1. Hitung total pendapatan (Hanya menjumlahkan pesanan yang bukan 'unpaid' atau 'cancelled')
        $totalPendapatan = Order::whereIn('status', ['paid', 'processing', 'shipping', 'completed'])->sum('total_amount');

        // 2. Hitung total semua pesanan yang pernah masuk
        $totalPesanan = Order::count();

        // 3. Hitung pesanan yang sudah dibayar (paid) dan menunggu diproses admin
        $pesananBaru = Order::where('status', 'paid')->count();

        return [
            // KOTAK 1: Total Pendapatan
            Stat::make('Total Pendapatan', 'Rp ' . number_format($totalPendapatan, 0, ',', '.'))
                ->description('Dari pesanan yang sudah dibayar')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'), // Warna Hijau

            // KOTAK 2: Total Pesanan
            Stat::make('Total Pesanan', $totalPesanan)
                ->description('Keseluruhan transaksi')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'), // Warna Biru

            // KOTAK 3: Perlu Diproses
            Stat::make('Perlu Diproses', $pesananBaru)
                ->description('Pesanan menunggu pengiriman')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'), // Warna Kuning/Oranye
        ];
    }
}