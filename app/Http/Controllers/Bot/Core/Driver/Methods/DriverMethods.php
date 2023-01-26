<?php

namespace App\Http\Controllers\Bot\Core\Driver\Methods;

use App\Models\Order;
use Carbon\Carbon;

trait DriverMethods
{

    public function sendDriverStatistics()
    {
        $orders = Order::whereDate('created_at', Carbon::today())
            ->withTrashed()
            ->where('status', 'completed')
            ->where('driver_id', $this->user->id)
            ->get();

        $orders_sum = $orders->pluck('summary')->sum();
        $deliveries_sum = $orders->pluck('shipping_price')->sum();

        $text = '';
        $text .= __('operator.daily_report', [
            'orders_qty' => $orders->count(),
            'orders_sum' => number_format($orders_sum, 0, '.', ','),
            'delivery_sum' => number_format($deliveries_sum, 0, '.', ','),
        ]);

        $orders = Order::whereMonth('created_at', date('m'))
            ->withTrashed()
            ->where('driver_id', $this->user->id)
            ->whereYear('created_at', date('Y'))
            ->where('status', 'completed')
            ->get();

        $orders_sum = $orders->pluck('summary')->sum();
        $deliveries_sum = $orders->pluck('shipping_price')->sum();


        $text .= __('operator.monthly_report', [
            'orders_qty' => $orders->count(),
            'orders_sum' => number_format($orders_sum, 0, '.', ','),
            'delivery_sum' => number_format($deliveries_sum, 0, '.', ','),
        ]);

        $this->sendMessage($text, $parse_mode = 'HTML');
    }
}