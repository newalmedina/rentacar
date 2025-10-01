<?php

namespace App\Filament\CustomWidgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Order;
use App\Models\OtherExpense;
use Illuminate\Support\Carbon;

class IngresosNetosStats extends BaseWidget
{
    protected static bool $shouldRegisterNavigation = false;

    protected function getStats(): array
    {
        // Fechas clave
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $startOfWeek = Carbon::now()->startOfWeek();
        $startOfLastWeek = $startOfWeek->copy()->subWeek();

        $startOfMonth = Carbon::now()->startOfMonth();
        $startOfLastMonth = $startOfMonth->copy()->subMonth();
        $endOfLastMonth = $startOfMonth->copy()->subDay();

        // Funciones para calcular totales
        $getVentas = fn($start, $end = null) => Order::sales()
            ->invoiced()
            ->myCenter()
            ->when($end, fn($q) => $q->whereBetween('date', [$start, $end]))
            ->when(!$end, fn($q) => $q->whereDate('date', $start))
            ->get()
            ->sum(fn($order) => $order->total);

        $getGastos = fn($start, $end = null) => OtherExpense::query()->myCenter()
            ->when($end, fn($q) => $q->whereBetween('date', [$start, $end]))
            ->when(!$end, fn($q) => $q->whereDate('date', $start))
            ->get()
            ->sum(fn($expense) => $expense->total);

        // Ingresos netos = Ventas - Gastos
        $ingresosHoy = $getVentas($today) - $getGastos($today);
        $ingresosAyer = $getVentas($yesterday) - $getGastos($yesterday);

        $ingresosSemana = $getVentas($startOfWeek, Carbon::now()) - $getGastos($startOfWeek, Carbon::now());
        $ingresosSemanaPasada = $getVentas($startOfLastWeek, $startOfWeek->copy()->subDay()) - $getGastos($startOfLastWeek, $startOfWeek->copy()->subDay());

        $ingresosMes = $getVentas($startOfMonth, Carbon::now()) - $getGastos($startOfMonth, Carbon::now());
        $ingresosMesPasado = $getVentas($startOfLastMonth, $endOfLastMonth) - $getGastos($startOfLastMonth, $endOfLastMonth);

        return [
            Stat::make('Ingresos netos en el día de hoy', '€' . number_format($ingresosHoy, 2))
                ->description("Ingresos netos ayer €" . number_format($ingresosAyer, 2))
                ->descriptionIcon($ingresosHoy - $ingresosAyer > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($ingresosHoy - $ingresosAyer > 0 ? 'success' : 'danger')
                ->chart($this->getDailyNetIncome($yesterday)),

            Stat::make('Ingresos netos en esta semana', '€' . number_format($ingresosSemana, 2))
                ->description("Ingresos netos semana pasada €" . number_format($ingresosSemanaPasada, 2))
                ->descriptionIcon($ingresosSemana - $ingresosSemanaPasada > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($ingresosSemana - $ingresosSemanaPasada > 0 ? 'success' : 'danger')
                ->chart($this->getDailyNetIncome($startOfWeek)),

            Stat::make('Ingresos netos en este mes', '€' . number_format($ingresosMes, 2))
                ->description("Ingresos netos mes pasado €" . number_format($ingresosMesPasado, 2))
                ->descriptionIcon($ingresosMes - $ingresosMesPasado > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($ingresosMes - $ingresosMesPasado > 0 ? 'success' : 'danger')
                ->chart($this->getDailyNetIncome($startOfMonth)),
        ];
    }

    private function getDailyNetIncome(Carbon $startDate): array
    {
        $endDate = Carbon::today();

        $salesByDay = [];

        if ($startDate->gt($endDate)) {
            return $salesByDay;
        }

        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateString = $currentDate->format('Y-m-d');

            $ventas = Order::sales()
                ->invoiced()
                ->whereDate('date', $dateString)
                ->get()
                ->sum(fn($order) => $order->total);

            $gastos = OtherExpense::whereDate('date', $dateString)
                ->get()
                ->sum(fn($expense) => $expense->total);

            $salesByDay[] = ($ventas - $gastos) ?: 0;

            $currentDate->addDay();
        }

        return $salesByDay;
    }
}
