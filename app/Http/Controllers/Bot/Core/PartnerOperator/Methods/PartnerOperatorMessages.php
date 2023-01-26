<?php

namespace App\Http\Controllers\Bot\Core\PartnerOperator\Methods;

use App\Exports\OrdersExport;
use App\Models\Order;
use App\Models\Restaurant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

trait PartnerOperatorMessages
{

    public function sendMainPartnerOperatorMenu()
    {
        $values = [
            'last_step' => null,
            'last_value' => null
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __("keyboard_statistics.welcome_to_the_team", [
            'name'  => htmlspecialchars($this->user->first_name . ' ' . $this->user->last_name),
            'role' => $this->user->role
        ]);
        $menu = $this->getMainPartnerOperatorMenu($this->user->partner_operator);
        $this->sendMessage($text, 'HTML', $menu);
    }

    public function sendPartnerOperatorStatistics()
    {
        if (!$this->user->partner_operator->restaurant){
            $text = __('partner_operator.no_attached_restaurant');
            return $this->sendMessage($text, 'HTML');
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
    public function getMainPartnerOperatorMenu($employee = null)
    {
        $options = [];
        if ($employee){
            if ($employee->status === 'active'){
                $options = [
                    [
                        ['text' => __('partner_operator.keyboard_menu')],
                    ],
                    [
                        ['text' => __('partner_operator.keyboard_statistics')],
                        ['text' => __('partner_operator.keyboard_report')],

                    ],
//                    ['text' => __('partner_operator.keyboard_on_activated')],
                ];
            }else{
                $options = [ ];
            }
        }
        if (count($options) > 0){
            return $this->getKeyboard($options, $resize = true);
        }else{
            return $this->getKeyboard($options, $resize = true);
        }
    }


    public function getEditPartnerOperatorText($partner_operator){
        $restaurantText = '-';
        if ($partner_operator->restaurant){
            $restaurantText = $partner_operator->restaurant->translation->name;
        }
        $text = __('admin.users_partner_template', [
            'restaurant' => htmlspecialchars($restaurantText),
            'name' => htmlspecialchars($partner_operator->name),
            'phone_number' => $partner_operator?->user?->phone_number,
            'joined_at' => $partner_operator->created_at,
        ]);

        return $text;
    }

    public function getEditPartnerOperatorViewKeyboard($partner_operator){

        if ($partner_operator->status == 'active'){
            $statusButton = [
                'text' => __('admin.users_partner_on'),
                'callback_data' => 'partner_operator_off/' . $partner_operator->id
            ];
        }else{
            $statusButton = [
                'text' => __('admin.users_partner_off'),
                'callback_data' => 'partner_operator_on/' . $partner_operator->id
            ];
        }
        $options = [
//            [
//                [
//                    'text' => __('admin.restaurant_partner_operator_update_button'),
//                    'callback_data' => 'update_restaurant_operator/' . $partner_operator->id
//                ]
//            ],
            [
                $statusButton,
                [
                    'text' => __('admin.restaurant_partner_operator_delete_button'),
                    'callback_data' => 'delete_partner_operator/' . $partner_operator->id
                ]
            ],
            [
                [
                    'text' => __('admin.users_back_to_partner_operators'),
                    'callback_data' => 'partner_operators'
                ]
            ]
        ];

        return $this->getInlineKeyboard($options);
    }


    public function sendRestaurantCategoriesMenu($is_new_message = true){
        $partner_operator = $this->user->partner_operator;
        $restaurant = $partner_operator->restaurant;
        if (!$restaurant instanceof  Restaurant){
            return $this->sendMessage(__("partner_operator.no_attached_restaurant"));
        }
        $restaurant_id = $restaurant->id;

        $restaurant = $this->restaurantService->find($restaurant_id);
        $categories = $this->categoryService->getAll($restaurant_id);

        if (count($categories) > 0) {
            $j = 0;
            $k = 0;
            $categories = $categories->toArray();
            $inline = [];
            for ($i = 0; $i < count($categories); $i++) {
                $item = $categories[$i];
                if (!$item['translation']['name']) continue;
                if (isset($inline[$j]) && count($inline[$j]) == 2) {
                    $k = 0;
                    $j++;
                    $inline[$j][$k]['text'] = $item['translation']['name'];
                    $inline[$j][$k]['callback_data'] = 'category/' . $item['id'];
                } else {
                    $inline[$j][$k]['text'] = $item['translation']['name'];
                    $inline[$j][$k]['callback_data'] = 'category/' . $item['id'];
                }
                $k++;
            }

            $text = __('admin.restaurant_page', [
                'name' => $restaurant->translation->name
            ]);
            $markup = $this->getInlineKeyboard($inline);

            if (!$is_new_message){
                return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
            }else{
                return $this->sendMessage($text, 'HTML', $markup);
            }

        } else {
            $text = __('admin.restaurant_page', [
                'name' => $restaurant->translation->name
            ]);


            if (!$is_new_message){
                return $this->editMessageText($this->callback_query['message']['message_id'], $text, $parse_mode = 'HTML');
            }else{
                return $this->sendMessage($text, 'HTML', []);
            }

        }
    }

    public function sendEnableEmployeeAvailibilityMessage()
    {
        $text = __('driver.availability_text_keyboard_on_message');
        $markup = $this->getMainDriverMenu($this->user);
        $res = $this->sendMessage($text, 'HTML', $markup);
    }

    public function sendDisableEmployeeAvailibilityMessage()
    {
        $text = __('driver.availability_text_keyboard_off_message');
        $markup = $this->getMainDriverMenu($this->user);
        $res = $this->sendMessage($text, 'HTML', $markup);
    }

    // restaurant employee off by admin
    public function greetOffRestrauntEmployee()
    {
        $text = __('partner.inactive_mode', ['name'  => $this->user?->first_name]);
        $this->sendMessage($text, 'HTML', []);
        return false;
    }

    public function promptMoveOrderToCanceledByRestaurantEmployeeMessage($order_id = null)
    {
        $order = Order::find($order_id);
        if (!$order instanceof Order){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        if ($order->trashed()){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        if (!$order->status == 'canceled' || $order->trashed()){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        $order->load(['operator', 'driver', 'restaurant_operator', 'items.product.translation']);

        $order->restaurant_operator_id = $this->user->id;
        $order->save();
        $order->refresh();

        $options = [
            [
                [
                    'text' => __("partner.confirm_order_cancel_and_call_to_operator_button"),
                    'callback_data' => 'confirm_order_cancel_and_call_to_operator' . "/" . $order->id
                ],
                [
                    'text' => __("partner.disconfirm_order_cancel_and_call_to_operator_button"),
                    'callback_data' => 'disconfirm_order_cancel_and_call_to_operator' . "/" . $order->id
                ],
            ]
        ];

        $menu = $this->getInlineKeyboard($options);
        $text = $this->getOrderViewText($order, $this->user);
        $text .= "\n" . __("partner.prompt_order_message");
        return $this->editMessageText($message_id = null, $text, 'HTML', $menu, disable_web_page_preview: true);

        return true;
    }

    public function backtToMoveOrderToCanceledByRestaurantEmployee($order_id = null)
    {
        $order = Order::find($order_id);
        if (!$order instanceof Order){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        if ($order->trashed()){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        if (!$order->status == 'canceled' || $order->trashed()){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        $order->load(['operator', 'driver', 'restaurant_operator', 'items.product.translation']);

        $order->save();
        $order->refresh();

        $menu = $this->getOrderViewKeyboard($order, $this->user);
        $text = $this->getOrderViewText($order, $this->user);
        return $this->editMessageText($message_id = null, $text, 'HTML', $menu, disable_web_page_preview: true);

        return true;
    }

    public function moveOrderToCookAndSendDrivers($order_id = null)
    {

        $order = Order::find($order_id);
        if (!$order instanceof Order){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        if (!$order->status == 'canceled' || $order->trashed()){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        if ($order->status == 'accepted' || $order->status == 'preparing'){
            $order->load(['operator', 'items.product.translation']);
            $operator = $order->operator;

            $text = __('client.order_your_order_was_processed_step1', [
                'order_id' => $order->id
            ]);

            $data = [
                'chat_id' => $operator->telegram_id,
                'text' => $text
            ];
            $this->postRequest('sendMessage', $data);

            if ($order->status == 'accepted'){
                $order->status = 'preparing';
                $order->restaurant_operator_id = $this->user->id;
                $order->save();
                $order->refresh();

                if ($order->is_assigned_by_operator != true && !$order->driver_id && $order->is_sent_to_drivers != true){

                    $order->is_sent_to_drivers = true;
                    $order->save();
                    $order->refresh();

                    $drivers = $this->userService->getActiveDrivers(true);

                    $receivers = $drivers->filter(function($item) {
                        if ($item->telegram_id){
                            if ($item->driver_orders()->whereIn('status', ['accepted', 'preparing', 'delivering'])->count() > 2){
                                return false;
                            }else{
                                return true;
                            }
                        }
                        return false;
                    });

                    foreach ($receivers as $user){
                        $text = $this->getOrderViewText($order, $user);
                        $keyboard = $this->getOrderViewKeyboard($order, $user);
                        $this->sendMessage2($user->telegram_id, $text, 'HTML', $keyboard);
                    }
                }
            }

            $menu = $this->getOrderViewKeyboard($order, $this->user);
            $text = $this->getOrderViewText($order, $this->user);
            return $this->editMessageText($message_id = null, $text, 'HTML', $menu, disable_web_page_preview: true);
        }else{
            $menu = $this->getOrderViewKeyboard($order, $this->user);
            $text = $this->getOrderViewText($order, $this->user);
            return $this->editMessageText($message_id = null, $text, 'HTML', $menu, disable_web_page_preview: true);
            return true;
        }


        return true;
    }

    public function moveOrderToCanceledByRestaurantEmployee($order_id = null)
    {
        $order = Order::find($order_id);
        if (!$order instanceof Order){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }
        if ($order->trashed()){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }
        if ($order->status == 'delivering' || $order->status == 'completed'){
            $text = __('client.order_cannot_be_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }
        if (!$order->status == 'canceled'){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        $order->load(['operator', 'driver', 'restaurant_operator', 'items.product.translation']);

        $order->status = 'canceled';
        $order->restaurant_operator_id = $this->user->id;
        $order->save();
        $order->refresh();

        $operator = $order->operator;
        $restaurant_operator = $order->restaurant_operator;

        $text = __('operator.order_your_order_was_canceled_step1', [
            'order_id' => $order->id,
            'restaurant' => $order->restaurant->name,
            'restaurant_operator_name' => $restaurant_operator->name,
            'restaurant_operator_phone' => $restaurant_operator->phone_number,
        ]);


        $data = [
            'chat_id' => $operator->telegram_id,
            'text' => $text
        ];
        $this->postRequest('sendMessage', $data);

        // updating restaurant employee cheque
        $menu = $this->getOrderViewKeyboard($order, $this->user);
        $text = $this->getOrderViewText($order, $this->user);

        $res = $this->editMessageText($message_id = null, $text, 'HTML', $menu, disable_web_page_preview: true);

        $cancel_message =  __("partner.order_your_order_was_canceled_step1", [
            'order_id' => $order->id,
            'operator_name' => $order->operator->name,
            'operator_phone' => $order->operator->phone_number,
        ]);
        $res = $this->sendMessage($cancel_message, 'HTMl', $this->getInlineKeyboard([]), $this->callback_query['message']['message_id']);

        if ($order->is_assigned_by_operator && $order->driver){
            $text = $this->getOrderViewText($order, $order->driver);
            $keyboard = $this->getOrderViewKeyboard($order, $order->driver);
            $this->sendMessage2($order->driver->telegram_id, $text, 'HTML', $keyboard);
        }
        return $res;
    }
}
