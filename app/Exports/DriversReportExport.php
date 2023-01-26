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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DriversReportExport implements FromQuery, WithHeadings, ShouldAutoSize, WithStyles, WithMapping, WithColumnFormatting
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
        return Order::query()
            ->leftJoin('users as drivers', 'drivers.id', '=', 'orders.driver_id')
            ->select(
                DB::raw("ANY_VALUE(drivers.name) as haydovchi"),
                DB::raw('SUM(orders.distance) as `Umumiy masofa`'),
                DB::raw('SUM(orders.shipping_price) as `Umumiy qiymati`'),
                DB::raw("DATE_FORMAT(MAX(orders.created_at), '%M') as `oy`"),
                DB::raw("DATE_FORMAT(MAX(orders.created_at), '%m') as `oy_raqami`"),
            )
            ->having('haydovchi', '!=', ' ')
            ->groupBy('haydovchi')
            ->orderBy('oy_raqami');

    }

    public function map($order): array
    {
        $item = $order->toArray();
        return [
            'haydovchi' => $item['haydovchi'],
            'Oy' => $item['oy'],
            'Umumiy masofa' => $item['Umumiy masofa'],
            'Umumiy qiymati' => $item['Umumiy qiymati'],
        ];
    }

    public function headings(): array
    {

        return [
            [
                'haydovchi',
                'Oy',
                'Umumiy masofa',
                'Umumiy qiymati',
            ],
        ];
    }
}
