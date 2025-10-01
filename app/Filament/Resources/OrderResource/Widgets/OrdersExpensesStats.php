<?php

namespace App\Filament\Resources\OrderResource\Widgets;

use App\Filament\Resources\OrderResource\Pages\ListOrders;
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
        return 4; // Mostrar 5 cards en una fila en pantallas grandes
    }

    protected function getStats(): array
    {
        // Totales de ventas facturadas
        $totalTransactions = Order::where('invoiced', 1)
            ->where('center_id', Auth::user()->center_id)
            ->count();

        $totalAmount = Order::where('invoiced', 1)
            ->where('center_id', Auth::user()->center_id)
            ->get()
            ->sum(fn($order) => $order->total);

        // Totales de gastos
        $totalTransactionsGastos = OtherExpense::where('center_id', Auth::user()->center_id)->count();

        $totalAmountGastos = OtherExpense::where('center_id', Auth::user()->center_id)
            ->get()
            ->sum(fn($expense) => $expense->total);

        // Ingresos netos = ventas - gastos
        $netIncome = $totalAmount - $totalAmountGastos;

        return [
            Stat::make('Total ordenes (facturadas)', '€ ' . number_format($totalAmount, 2))
                ->extraAttributes(['class' => 'w-full flex flex-col justify-center items-center text-center h-full text-green-500']),

            Stat::make('Núm. ordenes (facturadas)', $totalTransactions)
                ->extraAttributes(['class' => 'w-full flex flex-col justify-center items-center text-center h-full text-green-500']),

            Stat::make('Total gastos', '€ ' . number_format($totalAmountGastos, 2))
                ->extraAttributes(['class' => 'w-full flex flex-col justify-center items-center text-center h-full text-red-500']),

            Stat::make('Núm. gastos', $totalTransactionsGastos)
                ->extraAttributes(['class' => 'w-full flex flex-col justify-center items-center text-center h-full text-green-500']),

            Stat::make('Ingresos netos (facturados)', '€ ' . number_format($netIncome, 2))
                ->extraAttributes(['class' => 'w-full flex flex-col justify-center items-center text-center h-full ' . ($netIncome >= 0 ? 'text-blue-500' : 'text-red-600')]),
        ];
    }

    public static function canView(): bool
    {
        // Mostrar solo en /admin
        return request()->is('admin');
    }
}
