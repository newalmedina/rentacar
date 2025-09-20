<?php

namespace App\Filament\Resources\OrderResource\Widgets;

use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\Pages\ListSales;
use App\Models\Order;
use App\Models\OtherExpense;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class OrdersExpensesStats extends StatsOverviewWidget
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

        $totalTransactions = Order::where('invoiced', 1)
            ->where('center_id', Auth::user()->center_id)
            ->count();

        $totalAmount = Order::where('invoiced', 1)
            ->where('center_id', Auth::user()->center_id)
            ->get()
            ->sum(fn($expense) => $expense->total);

        $totalTransactionsGastos = OtherExpense::where('center_id', Auth::user()->center_id)->count();
        $totalAmountGastos = OtherExpense::where('center_id', Auth::user()->center_id)->get()->sum(function ($expense) {
            return $expense->total; // Usa el accesor
        });


        return [
            Stat::make('Total ordenes (facturadas)', '€ ' . number_format($totalAmount, 2))
                ->extraAttributes(['class' => 'flex flex-col justify-center items-center text-center h-full text-green-500']),
            Stat::make('Núm. ordenes (facturadas)', $totalTransactions)
                ->extraAttributes(['class' => 'flex flex-col justify-center items-center text-center h-full text-green-500']),
            Stat::make('Total gastos', '€ ' . number_format($totalAmountGastos, 2))
                ->extraAttributes(['class' => 'flex flex-col justify-center items-center text-center h-full text-red-500']),

            Stat::make('Núm. gastos', $totalTransactionsGastos)
                ->extraAttributes(['class' => 'flex flex-col justify-center items-center text-center h-full text-green-500']),
        ];
    }
    public static function canView(): bool
    {
        // Mostrar solo cuando la ruta sea exactamente /admin/orders
        return request()->is('admin');
    }
}
