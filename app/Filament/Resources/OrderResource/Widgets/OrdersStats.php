<?php

namespace App\Filament\Resources\OrderResource\Widgets;

use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\Pages\ListSales;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrdersStats extends StatsOverviewWidget
{
    use InteractsWithPageTable;
    protected static ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return ListOrders::class;
    }
    protected function getColumns(): int
    {
        return 4; // esto define 2 stats por fila
    }

    protected function getStats(): array
    {
        $totalTransactions = $this->getPageTableQuery()->count();
        $totalAmount = $this->getPageTableQuery()->get()->sum(function ($expense) {
            return $expense->total; // Usa el accesor
        });

        return [
            Stat::make('Total ordenes', '€ ' . number_format($totalAmount, 2))
                ->extraAttributes(['class' => 'flex flex-col justify-center items-center text-center h-full text-green-500']),
            Stat::make('Núm. transacciones', $totalTransactions)
                ->extraAttributes(['class' => 'flex flex-col justify-center items-center text-center h-full text-green-500']),
        ];
    }
    public static function canView(): bool
    {
        // Mostrar en cualquier página excepto en /admin
        return !request()->is('admin');
    }
}
