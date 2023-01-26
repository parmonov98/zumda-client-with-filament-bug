<?php

namespace App\Http\Livewire\Bot;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Traits\WithFilters;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Order;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\MultiSelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class OrderTable extends DataTableComponent
{
    protected $model = Order::class;
    public ?string $defaultSortColumn = 'order_id';
    public string $defaultSortDirection = 'desc';

    public function configure(): void
    {
        $this->setPrimaryKey('order_id');
        $this->setRememberColumnSelectionStatus(true);
        $this->setOfflineIndicatorEnabled();
        $this->setEmptyMessage("Tanlangan filter bo'yicha buyurtmalar mavjud emas!");
    }
    public function filters(): array
    {
        return [
            'status' => MultiSelectFilter::make('Holati')
                ->options([
                    'completed' => 'Tugatildi',
                    'delivering' => 'Yetkazilmoqda',
                    'preparing' => 'Tayyorlanmoqda',
                    'accepted' => 'Qabul qilindi',
                ])
                ->filter(function(Builder $builder, array $values) {
//                    dd($values);
                    if (count($values) !== 0) {
                        $builder->whereIn('orders.status', $values);
                    }else{
                        $builder;
                    }
                }),
//            DateFilter::make("Sana bo'yicha")
//                ->filter(function(Builder $builder, string $value) {
//                    $builder->where('orders.updated_at', '>=', $value);
//                }),
            'fromDate' => DateFilter::make('From Date')
                ->config([
                    'max' => now()->format('Y-m-d')
                ])
                ->filter(function(Builder $builder, $value) {
//                    dd($value);
                    if ($value !== '') {
                        $builder->where('orders.created_at', '>=', $value);
                    }else{
                        $builder;
                    }
                }),
            'toDate' => DateFilter::make('To Date')
                ->config([
                    'min' => isset($this->filters['fromDate']) && $this->filters['fromDate'] ? $this->filters['fromDate']:'',
                    'max' => now()->format('Y-m-d')
                ])
                ->filter(function(Builder $builder, $value) {
//                    dd($value);
                    if ($value !== '') {
                        $builder->where('orders.created_at', '<=', $value);
                    }else{
                        $builder;
                    }
                })

        ];
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),
            Column::make("Buyurtma Id", "id as order_id")
                ->sortable(),

            Column::make("Mijoz", "client.name")
                ->searchable()
                ->sortable(),
            Column::make("Haydovchi", "driver.first_name")
                ->sortable(),
            Column::make("Summasi", "summary")
                ->searchable()
                ->sortable(),
            Column::make("Masofa", "distance")
                ->sortable(),
            Column::make("Yetkazib narxi(km)", "per_km_price")
                ->sortable(),
            Column::make("Yetkazish hisobi", "shipping_price")
                ->sortable(),
            Column::make("Holati", "status")
                ->view('layouts.components.datatables.status-td')
//                ->set([
//                    '' => 'All',
//                    'created' => 'yaratilgan',
//                    'accepted' => 'operator qabul qildi',
//                    'preparing' => 'Tayyorlanmoqda',
//                    'prepared' => "ovqat obketishga tayyor",
//                    'delivering' => 'Yetkazilmoqda',
//                    'completed' => 'Yetkazildi',
//                    'canceled' => 'bekor qilindi',
//                ])
                ->sortable(),
            Column::make("Telefon", "phone_number")
                ->sortable(),
            Column::make("Address", "address")
                ->searchable()
                ->sortable(),
            Column::make("Longitude", "longitude")
                ->sortable(),
            Column::make("Latitude", "latitude")
                ->sortable(),
            Column::make("Mijoz xohishi", "customer_note")
                ->searchable()
                ->sortable(),
            Column::make("Yaratilgan", "created_at")
                ->sortable(),
            Column::make("Yangilangan", "updated_at")
                ->sortable(),
        ];
    }

    public function customView(): string
    {
        return 'includes.custom';
    }
}
