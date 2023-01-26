<?php

namespace App\Exports;

use App\Http\Controllers\Bot\Core\Operator\Methods\OperatorMessages;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class OrdersExport implements FromQuery, WithHeadings, ShouldAutoSize, WithStyles, WithMapping, WithColumnFormatting
{
    use OperatorMessages;

    protected $params = [];

    function __construct($params = [])
    {
        $this->params = $params;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1 => [
                'font' => ['bold' => true],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ]
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function query()
    {
        if (isset($this->params['type']) && $this->params['type'] == 'restaurant') {

            return Order::query()
                ->withTrashed()
                ->where('orders.restaurant_id', $this->params['restaurant_id'])
                ->leftJoin('users as drivers', 'drivers.id', '=', 'orders.driver_id')
                ->leftJoin('users as operators', 'operators.id', '=', 'orders.user_id')
                ->select(
                    DB::raw("orders.id as `buyurtma raqami`"),
                    DB::raw("operators.name as operator"),
                    DB::raw("drivers.name as haydovchi"),
                    DB::raw('orders.summary as `buyurtma narxi`'),
                    DB::raw('orders.status as `holati`'),
                    DB::raw("DATE_FORMAT(orders.created_at, '%Y/%m/%d') as `sana`"),
                    DB::raw("DATE_FORMAT(orders.created_at, '%H:%i') as `vaqti`"),
                );
        }

        return Order::query()
            ->withTrashed()
            ->leftJoin('restaurants', 'restaurants.id', '=', 'orders.restaurant_id', 'full outer')
            ->leftJoin('users as drivers', 'drivers.id', '=', 'orders.driver_id')
            ->leftJoin('users as operators', 'operators.id', '=', 'orders.user_id')
            ->leftJoin('clients', 'clients.id', '=', 'orders.client_id')
            ->select(
                DB::raw("orders.id as `buyurtma raqami`"),
                DB::raw('restaurants.name as restoran'),
                DB::raw("operators.name as operator"),
                DB::raw("clients.name as `zakazchini ismi`"),
                DB::raw("drivers.name as haydovchi"),
                DB::raw('orders.phone_number as `zakazchini nomeri`'),
                DB::raw('orders.address as `zakazchini manzili`'),
                DB::raw('orders.summary as `buyurtma narxi`'),
                DB::raw('orders.shipping_price as `Yetkazish narxi`'),
                DB::raw('orders.status as `holati`'),
                DB::raw("DATE_FORMAT(orders.created_at, '%d/%m/%Y') as `sana`"),
                DB::raw("DATE_FORMAT(orders.created_at, '%H:%i') as `vaqti`"),
            );

    }

    public function map($order): array
    {
        $item = $order->toArray();
        if (isset($this->params['type']) && $this->params['type'] == 'restaurant') {
            return [
                    'buyurtma raqami' => $item['buyurtma raqami'],
                    'operator' => $item['operator'],
                    'haydovchi' => $item['haydovchi'],
                    'buyurtma narxi' => $item['buyurtma narxi'],
                    'holati' => $this->getOrderStatusForReport($item['holati']),
                    'sana' => $item['sana'],
                    'vaqti' => $item['vaqti'],
            ];
        }

        return [
                'buyurtma raqami' => $item['buyurtma raqami'],
                'restoran' => $item['restoran'],
                'operator' => $item['operator'],
                'haydovchi' => $item['haydovchi'],
                'zakazchini ismi' => (string) $item['zakazchini ismi'],
                'zakazchini nomeri' => (string) $item['zakazchini nomeri'],
                'zakazchini manzili' => $item['zakazchini manzili'],
                'buyurtma narxi' => $item['buyurtma narxi'],
                'Yetkazish narxi' => $item['Yetkazish narxi'],
                'holati' => $this->getOrderStatusForReport($item['holati']),
                'sana' => $item['sana'],
                'vaqti' => $item['vaqti'],
        ];
    }

    public function headings(): array
    {

        if (isset($this->params['type']) && $this->params['type'] == 'restaurant') {
            return [
                [
                    'buyurtma raqami',
                    'operator',
                    'haydovchi',
                    'buyurtma narxi',
                    'holati',
                    'sana',
                    'vaqti'
                ],
            ];
        }
        return [
            [
                'buyurtma raqami',
                'restoran',
                'operator',
                'haydovchi',
                'zakazchini ismi',
                'zakazchini nomeri',
                'zakazchini manzili',
                'buyurtma narxi',
                'Yetkazish narxi',
                'holati',
                'sana',
                'vaqti'
            ],
        ];
    }
}
