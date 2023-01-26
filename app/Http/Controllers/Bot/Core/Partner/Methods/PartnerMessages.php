<?php

namespace App\Http\Controllers\Bot\Core\Partner\Methods;

use App\Exports\OrdersExport;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

trait PartnerMessages
{

    public function sendMainPartnerMenu()
    {
        $values = [
            'last_step' => null,
            'last_value' => null
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __("partner.welcome_to_the_team", [
            'name'  => htmlspecialchars($this->user->first_name . ' ' . $this->user->last_name),
            'role' => $this->user->role
        ]);
        $menu = $this->getMainPartnerMenu($this->user);
        $this->sendMessage($text, 'HTML', $menu);
    }

    public function sendEnableRestaurantEmployeeAvailibilityMessage()
    {
        $text = __('driver.availability_text_keyboard_on_message');
        $markup = $this->getMainPartnerMenu($this->user->partner_operator);
        $res = $this->sendMessage($text, 'HTML', $markup);
    }

    public function sendDisableRestaurantEmployeeAvailibilityMessage()
    {
        $text = __('driver.availability_text_keyboard_off_message');
        $markup = $this->getMainPartnerMenu($this->user->partner_operator);
        $res = $this->sendMessage($text, 'HTML', $markup);
    }

    public function sendPartnerStatistics()
    {
        $this->user->partner->load('restaurant');
        if (!$this->user->partner || !$this->user->partner->restaurant){
            $text = __('partner.no_attached_restaurant');
            $this->sendMessage($text, 'HTML');
            return true;
        }
        $orders = Order::whereDate('created_at', Carbon::today())
            ->withTrashed()
            ->where('status', 'completed')
            ->where('restaurant_id', $this->user->restaurant?->id)
            ->get();

        $orders_sum = $orders->pluck('summary')->sum();

        $text = '';
        $text .= __('partner.daily_report', [
            'orders_qty' => $orders->count(),
            'orders_sum' => number_format($orders_sum, 0, '.', ','),
        ]);

        $orders = Order::whereMonth('created_at', date('m'))
            ->withTrashed()
            ->where('restaurant_id', $this->user->restaurant?->id)
            ->whereYear('created_at', date('Y'))
            ->where('status', 'completed')
            ->get();

        $orders_sum = $orders->pluck('summary')->sum();
        $deliveries_sum = $orders->pluck('shipping_price')->sum();


        $text .= __('partner.monthly_report', [
            'orders_qty' => $orders->count(),
            'orders_sum' => number_format($orders_sum, 0, '.', ','),
        ]);

        $res = $this->sendMessage($text, $parse_mode = 'HTML');
        return $res;
    }

    public function sendRestaurantReports()
    {
        $file_name = 'public/orders-' . Carbon::now()->format('Y-m-d-h-m-s') . '.xlsx';
        if (!$this->user->partner || !$this->user->partner->restaurant){
            $text = __('partner.no_attached_restaurant');
            $this->sendMessage($text);
            return true;
        }
        $params = [
            'type' => 'restaurant',
            'restaurant_id' =>  $this->user->restaurant?->id
        ];
        Excel::store(new OrdersExport($params),  $file_name);
        $path = Storage::disk('local')->path($file_name);
        $text = $file_name;
        return $this->sendDocument($this->message['from']['id'], $path, $text);
    }

    public function getMainPartnerMenu($employee = null)
    {
        $options = [];
        if ($employee){
            if ($employee->self_status){
                if ($employee->self_status == 'active'){
                    $options = [
                        [
                            ['text' => __('partner.keyboard_statistics')],
//                            ['text' => __('partner.keyboard_on_activated')],
                        ],
                        [
                            ['text' => __('partner.keyboard_report')],
                        ],
                    ];
                }else{
                    $options = [
                        [
                            ['text' => __('partner.keyboard_statistics')],
//                            ['text' => __('partner.keyboard_off_activated')],
                        ],
                        [
                            ['text' => __('partner.keyboard_report')],
                        ],
                    ];
                }
            }else{
                $options = [
                    [
                        ['text' => __('partner.keyboard_statistics')],
//                        ['text' => __('partner.keyboard_off_activated')],
                    ],
                    [
                        ['text' => __('partner.keyboard_report')],
                    ],
                ];
            }
        }
        return $this->getKeyboard($options, $resize = true);
    }

}
