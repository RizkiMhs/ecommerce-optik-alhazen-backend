<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
// 💡 TAMBAHKAN 2 IMPORT INI:
// use Filament\Resources\Components\Tab;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }

    // 💡 FUNGSI BARU: Membuat Tab Filter Status di atas tabel
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Pesanan'),
            
            'unpaid' => Tab::make('Belum Bayar')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'unpaid')),
                
            'paid' => Tab::make('Sudah Bayar')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'paid')),
                
            'processing' => Tab::make('Diproses')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'processing')),
                
            'shipping' => Tab::make('Dikirim')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'shipping')),
                
            'completed' => Tab::make('Selesai')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),
                
            'cancelled' => Tab::make('Batal')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),
        ];
    }
}