<?php

namespace App\Http\Controllers\Bot\Core\Driver\Methods;

use App\Models\Review;
use App\Models\User;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Models\Order;

trait DriverCallbacks
{
    public function activateDriverSelfStatus($user = null)
    {
        if (!$user){
            $text = __("No driver found");
            $menu = $this->getInlineKeyboard([]);
            $this->sendMessage($text, 'HTML', $menu);
        }else{
            $user->self_status = 'active';
            $user->save();
            $user->refresh();
            $text = __('driver.availability_text');
            $menu = $this->getDriverTurnOnOfMenu($user);
            return $this->editMessageText($message_id = null, $text, 'HTML', $menu, disable_web_page_preview: true);
        }
    }

    public function diactivateDriverSelfStatus($user = null)
    {
        if (!$user){
            $text = __("No driver found");
            $menu = $this->getInlineKeyboard([]);
            $this->sendMessage($text, 'HTML', $menu);
        }else{
            $user->self_status = 'inactive';
            $user->save();
            $user->refresh();
            $text = __('driver.availability_text');
            $menu = $this->getDriverTurnOnOfMenu($user);
            return $this->editMessageText($message_id = null, $text, 'HTML', $menu, disable_web_page_preview: true);
        }
    }

    public function moveOrderToDriver($order_id = null)
    {
        $order = Order::find($order_id);
        if (!$order instanceof Order){
            $text = __('driver.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

//        dd($this->user->id, $order->driver_id);

        if ($order->driver_id !== null) {
            $text = __('driver.order_is_already_accepted_by_other_driver', [
                'order_id' => $order->id
            ]);
            $this->answerCallbackQuery($text, false);
//            $this->deleteMessage($this->user->telegram_id, $this->callback_query['message']['message_id']);

            $menu = $this->getOrderViewKeyboard($order, $this->user);
            $text = $this->getOrderViewText($order, $this->user);
            return $this->editMessageText($message_id = null, $text, 'HTML', $menu, disable_web_page_preview: true);

            return false;
        }
        if ($this->user->driver_orders()->whereIn('status', ['accepted', 'preparing', 'delivering'])->count() >= 2){
            $text = __('driver.order_is_limit_exceed_by_driver', [
                'order_id' => $order->id
            ]);
            $this->answerCallbackQuery($text);
            return false;
        }
//        if ($order->status == 'accepted'){
//            $text = __('operator.order_is_not_accepted_yet', [
//                'order_id' => $order->id
//            ]);
//            $this->answerCallbackQuery($text, false);
//            return false;
//        }
        $order->load(['operator', 'items.product.translation']);

        $partner = $order->restaurant->employee;

//        dd($this->user);

        $customer = $order->operator;
        $order->driver_id = $this->user->id;
        $order->save();
        $order->refresh();

        $text = __('client.order_your_order_was_processed_step3', [
            'order_id' => $order->id
        ]);

        $data = [
            'chat_id' => $customer->telegram_id,
            'text' => $text
        ];

        $this->postRequest('sendMessage', $data);


        $receivers = $order->restaurant->employees;
        $text = __('admin.cook_informed_about_driver', [
            'order_id' => $order->id,
            'driver' => $order->driver->name,
            'phone' => $order->driver->phone_number
        ]);
        foreach ($receivers as $item){
            $data = [
                'chat_id' => $item->telegram_id,
                'text' => $text,
                'parse_mode' => 'HTML'
            ];
            $this->postRequest('sendMessage', $data);
        }



        $menu = $this->getOrderViewKeyboard($order, $this->user);
        $text = $this->getOrderViewText($order, $this->user);
        return $this->editMessageText($message_id = null, $text, 'HTML', $menu, disable_web_page_preview: true);
        return true;
    }


    public function sendDriverConfirmTheReceivedOrder($order_id = null)
    {
        $order = Order::find($order_id);
        if (!$order instanceof Order){
            $text = __('driver.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

//        dd($this->user->id, $order->driver_id);

        if ($order->driver_id !== null && $order->driver->telegram_id !== $this->user->telegram_id) {
            $text = __('driver.order_is_already_accepted_by_other_driver', [
                'order_id' => $order->id
            ]);
            $this->answerCallbackQuery($text, false);
            $this->deleteMessage($this->user->telegram_id, $this->callback_query['message']['message_id']);
            return false;
        }
//        if ($order->status == 'accepted'){
//            $text = __('operator.order_is_not_accepted_yet', [
//                'order_id' => $order->id
//            ]);
//            $this->answerCallbackQuery($text, false);
//            return false;
//        }
        $order->load(['operator', 'items.product.translation']);

        $partner = $order->restaurant->employee;

        $customer = $order->operator;
        $order->is_accepted_order_by_driver = true;
        $order->save();
        $order->refresh();

        $text = __('client.order_your_order_was_processed_step3', [
            'order_id' => $order->id
        ]);

        $data = [
            'chat_id' => $customer->telegram_id,
            'text' => $text
        ];

        $this->postRequest('sendMessage', $data);

        $menu = $this->getOrderViewKeyboard($order, $this->user);
        $text = $this->getOrderViewText($order, $this->user);
        return $this->editMessageText($message_id = null, $text, 'HTML', $menu, disable_web_page_preview: true);
    }

    public function sendDriverDisconfirmTheReceivedOrder($order_id = null)
    {
        $order = Order::find($order_id);
        if (!$order instanceof Order){
            $text = __('driver.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

//        dd($this->user->id, $order->driver_id);

        if ($order->driver_id !== null && $order->driver->telegram_id !== $this->user->telegram_id) {
            $text = __('driver.order_is_already_accepted_by_other_driver', [
                'order_id' => $order->id
            ]);
            $this->answerCallbackQuery($text, false);
            $this->deleteMessage($this->user->telegram_id, $this->callback_query['message']['message_id']);
            return false;
        }
//        if ($order->status == 'accepted'){
//            $text = __('operator.order_is_not_accepted_yet', [
//                'order_id' => $order->id
//            ]);
//            $this->answerCallbackQuery($text, false);
//            return false;
//        }
        $order->load(['operator', 'items.product.translation']);

        $partner = $order->restaurant->employee;

        $customer = $order->operator;
        $order->driver_id = null;
        $order->save();
        $order->refresh();


        $text = __('driver.order_driver_cannot_delivery_notification', [
            'order_id' => $order->id,
            'driver' => $this->user->name
        ]);
        $data = [
            'chat_id' => $customer->telegram_id,
            'text' => $text
        ];

        $this->postRequest('sendMessage', $data);

        $drivers = $this->userService->getActiveDrivers(true);

        $receivers = $drivers->filter(function($item) {
            if ($item->telegram_id === $this->user->telegram_id){
                return false;
            }
            if ($item->telegram_id){
                if ($item->driver_orders()->whereIn('status', ['accepted', 'preparing', 'delivering'])->count() > 2){
                    return false;
                }else{
                    return true;
                }
            }
            return false;
        });

        $order->is_sent_to_drivers = true;
        $order->is_assigned_by_operator = false;

        foreach ($receivers as $user){
            $text = $this->getOrderViewText($order, $user);
            $keyboard = $this->getOrderViewKeyboard($order, $user);
            $this->sendMessage2($user->telegram_id, $text, 'HTML', $keyboard);
        }
        $order->save();
        $order->refresh();

        $menu = $this->getOrderViewKeyboard($order, $this->user);
        $text = $this->getOrderViewText($order, $this->user);
        $res = $this->editMessageText($message_id = null, $text, 'HTML', $menu, disable_web_page_preview: true);
        return $res;
    }


    public function moveOrderToPicked($order_id = null)
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
        if ($order->driver_id !== null && $order->driver->id != $this->user->id) {
            $text = __('driver.order_is_already_accepted_by_other_driver', [
                'order_id' => $order->id
            ]);
            $this->answerCallbackQuery($text);
            $menu = $this->getOrderViewKeyboard($order, $this->user);
            $text = $this->getOrderViewText($order, $this->user);
            return $this->editMessageText($message_id = null, $text, 'HTML', $menu, disable_web_page_preview: true);
//            $this->deleteMessage($this->user->telegram_id, $this->callback_query['message']['message_id']);
            return false;
        }

        if ($order->status == 'accepted'){
            $text = __('operator.order_is_not_accepted_yet', [
                'order_id' => $order->id
            ]);
            $this->answerCallbackQuery($text, false);
//            return false;
        }
//        if (!$order->restaurant_operator_id){
//            $text = __('operator.order_is_not_accepted_yet', [
//                'order_id' => $order->id
//            ]);
//            $this->answerCallbackQuery($text, false);
//            return false;
//        }

        $order->load(['operator', 'items.product.translation']);

        $order->driver_id = $this->user->id;
        $order->status = 'delivering';
        $order->save();
        $order->refresh();

        $name = $this->user->first_name . ' ' . $this->user->last_name;
        $text = __('client.order_your_order_was_processed_step6', [
            'order_id' => $order->id,
            'name' => $name
        ]);

        $data = [
            'chat_id' => $order->operator->telegram_id,
            'text' => $text
        ];

        $this->postRequest('sendMessage', $data);

        $menu = $this->getOrderViewKeyboard($order, $this->user);
        $text = $this->getOrderViewText($order, $this->user);
        return $this->editMessageText($message_id = null, $text, 'HTML', $menu, disable_web_page_preview: true);
        return true;
    }


    public function moveOrderToCompleted($order_id = null)
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

        if ($order->driver_id !== null && $order->driver->id != $this->user->id) {
//            dd($this->user);
            $text = __('driver.order_is_already_accepted_by_other_driver', [
                'order_id' => $order->id
            ]);
            $this->answerCallbackQuery($text, false);
//            $this->deleteMessage($this->user->telegram_id, $this->callback_query['message']['message_id']);
            $menu = $this->getOrderViewKeyboard($order, $this->user);
            // dd('111');
            $text = $this->getOrderViewText($order, $this->user);

            return $this->editMessageText($message_id = null, $text, 'HTML', $menu, disable_web_page_preview: true);
            return false;
        }

        if ($order->status !== 'delivering'){
            $text = __('operator.order_is_not_being_delivered', [
                'order_id' => $order->id
            ]);
            $this->answerCallbackQuery($text);
            return false;
        }

        $order->load(['operator', 'items.product.translation']);
        $customer = $order->operator;

        $order->status = 'completed';
        $order->save();
        $order->refresh();

        $text = __('client.order_your_order_was_processed_step4', [
            'order_id' => $order->id
        ]);

        $data = [
            'chat_id' => $customer->telegram_id,
            'text' => $text
        ];


        $this->postRequest('sendMessage', $data);

        $menu = $this->getOrderViewKeyboard($order, $this->user);
        // dd('111');
        $text = $this->getOrderViewText($order, $this->user);

        return $this->editMessageText($message_id = null, $text, 'HTML', $menu, disable_web_page_preview: true);
        return true;
    }


}
