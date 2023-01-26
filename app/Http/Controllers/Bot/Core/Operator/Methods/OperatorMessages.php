<?php

namespace App\Http\Controllers\Bot\Core\Operator\Methods;

use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

trait OperatorMessages
{
    // operator mode disabled
    public function greetUserDisabled() :string
    {
        $this->answerCallbackQuery();
        $text = __('client.disabled', ['place'  => env('TELEGRAM_BOT_NAME', 'Zumda')]);
        return $this->sendMessage($text, 'HTML', []);
    }

    public function greetOperator($from_id)
    {
        if ($this->user == null) {
            $user = $this->userService->getByTelegramID($from_id);
        } else {
            $user = $this->user;
        }
        if ($user !== null) {
            $text = __('operator.welcome_message_back', ['place'  => env('TELEGRAM_BOT_NAME', 'Zumda')]);
            $this->sendMessage($text, 'HTML', $markup = $this->getMainOperatorMenu());
        }else{
            $text = __('operator.welcome_message_back_failed', [
                'place'  => env('TELEGRAM_BOT_NAME', 'Zumda'),
                'code' => '404'
            ]);

            $params = [
                'chat_id' => $from_id,
                'text' => htmlspecialchars($text),
                'parse_mode' => 'HTML'
            ];

            $this->postRequest('sendMessage', $params);
        }

    }

    public function clearOperatorSteps()
    {
        $values = [
            'last_step' => null,
            'last_value' => null,
            'last_message_id' => null,
            'temp_client_id' => null,
        ];

        $this->userService->updateUserLastStep($this->user->telegram_id, $values);
    }

    public function getMainOperatorMenu(): string
    {
        $options = [
            [
                ['text' => __('operator.keyboard_order')],
            ],
            [
                ['text' => __('operator.keyboard_cart')],
                ['text' => __('operator.keyboard_statistics')],
            ],
//            [
//                ['text' => __('operator.keyboard_settings')]
//            ]
        ];
        return $this->getKeyboard($options, $resize = true);
    }

    public function sendMainOperatorMenu($text = '')
    {
        $this->userService->resetSteps($this->user->telegram_id);

        if ($text == ''){
            $text = __("operator.restaurants_main_page", [
                'place'  => env('TELEGRAM_BOT_NAME', 'Zumda2')
            ]);
        }
        $menu = $this->getMainOperatorMenu();

        $this->sendMessage($text, 'HTML', $menu);
    }


    public function getActiveRestaurantsKeyboard($restaurants){
        $j = 0;
        $k = 0;
        $restaurants = $restaurants->toArray();
        $keyboard = [];
        for ($i = 0; $i < count($restaurants); $i++) {
            $item = $restaurants[$i];
            if (isset($keyboard[$j]) && count($keyboard[$j]) == 2) {
                $k = 0;
                $j++;
                $keyboard[$j][$k]['text'] = $item['name'];
            } else {
                $keyboard[$j][$k]['text'] = $item['name'];
            }
            $k++;
        }
        $keyboard[count($keyboard)][0] = __('operator.go_to_home');
        return $this->getKeyboard($keyboard, true);
    }

    public function sendRestaurantsMenu($is_edit = false)
    {
        $restaurants = $this->restaurantService->getActiveRestaurants();
        $this->userService->resetSteps($this->user->telegram_id);
        if (count($restaurants) > 0) {
            $markup = $this->getActiveRestaurantsKeyboard($restaurants);
            $text = __('operator.restaurants', [
                'place' => env('TELEGRAM_BOT_NAME', 'Zumda')
            ]);
            if ($is_edit){
                $res = $this->editMessageText(null, $text, 'HTML', $markup);
                $this->answerCallbackQuery();
            }else{
                $res = $this->sendMessage($text, 'HTML', $markup);
            }
            return $res;

        } else {
            $text = __('operator.no_restaurant_found');
            return $this->sendMessage($text, 'HTML', []);
        }
    }

    public function sendRestaurantMenu($id)
    {
        $restaurant = $this->restaurantService->find($id);
        if ($restaurant) {
            return $this->sendOrderCategoryMenu($id);
        } else {
            $text = __('operator.no_restaurant_found');
            return $this->sendMessage($text, 'HTML', []);
        }
    }

    public function sendOrderCategoryMenu($restaurant_id)
    {

        $categories = $this->categoryService->getActiveCategories($restaurant_id, $is_first_level = true);
        $categories = $categories->values();
        $inline = [];
        if (count($categories) > 0) {
            $j = 0;
            $k = 0;
            $categories = $categories->toArray();
            for ($i = 0; $i < count($categories); $i++) {
                $item = $categories[$i];
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

            $text = __('operator.main_categories', [
                'menu_link' => env('BOT_MENU_LINK')
            ]);
            $markup = $this->getInlineKeyboard($inline);

            return $this->sendMessage($text, 'HTML', $markup);
        } else {
            $text = __('operator.no_restaurant_category_found');
            // $markup = $this->getInlineKeyboard($inline);
            return $this->sendMessage($text, 'HTML', []);
        }
    }
//

    public function orderCartItemKeyboard($orders){

    }
//    public function sendCartItemsByOperator($is_inline = false)
//    {
//
//        $text = __('operator.cart_items');
//
//        $this->user->cart->load('items.product.translation');
//        $items = $this->user->cart->items;
//
//        // dd($items);
//        $summary = 0;
//        $itemsText = "\n";
//        $rows = [];
//        if ($items->count() >= 0) {
//            foreach ($items as $key => $item) {
//                $itemSummary = ($item->quantity * $item->price);
//                $summary += $itemSummary;
//                $itemsText .= $item->quantity . ' x ' . $item->product->translation->name . ' = ' . $itemSummary .  __('operator.item_currency_lang');
//
//
//                $itemKeyboardRow = [
//                    [
//                        'text' => '-',
//                        'callback_data' => 'decrement_cart_item/' . $item->id . '/' . $item->cart->user_id
//                    ],
//                    [
//                        'text' => '-10x',
//                        'callback_data' => 'decrement_cart_item_by_10x/' . $item->id . '/' . $item->cart->user_id
//                    ],
//                    [
//                        'text' => $item->product->translation->name,
//                        'callback_data' => 'display_cart_item_quantity/' . $item->id . '/' . $item->cart->user_id
//                    ],
//                    [
//                        'text' => '+10x',
//                        'callback_data' => 'increment_cart_item_by_10x/' . $item->id . '/' . $item->cart->user_id
//                    ],
//                    [
//                        'text' => '+',
//                        'callback_data' => 'increment_cart_item/' . $item->id . '/' . $item->cart->user_id
//                    ]
//                ];
//                $rows[] = $itemKeyboardRow;
//            }
//
//            $text .= $itemsText;
//            $text .= __('operator.cart_items_summary', [
//                'summary' => number_format($summary, 0, '.', ',')
//            ]);
//
//
//            $inline =  [
//                [
//                    [
//                        'text' => __('operator.confirm_the_order'),
//                        'callback_data' => 'confirm_the_order'
//                    ],
//                ],
//                [
//                    [
//                        'text' => __('operator.continue_adding_to_cart'),
//                        'callback_data' => 'back_to_main_category_menu/' . $item->product->category->restaurant_id
//                    ],
//                    [
//                        'text' => __('operator.clear_the_cart'),
//                        'callback_data' => 'clear_the_cart'
//                    ]
//                ],
//            ];
//            $inline = array_merge($rows, $inline);
//            $markup = $this->getInlineKeyboard($inline);
//            if ($is_inline == true) {
//                $res = return $this->editMessageText($message_id = null, htmlspecialchars($text), 'HTML', $markup);
//            } else {
//
//                return $this->sendMessage($text, 'HTML', $markup);
//            }
//        } else {
//            $text = __('operator.cart_empty');
//            $restaurants = $this->restaurantService->getActiveRestaurants();
//            $markup = $this->getActiveRestaurantsKeyboard($restaurants);
//            return $this->sendMessage($text, 'HTML', $markup);
//        }
//    }
    public function cancelOrderByOperator($order_id = null)
    {
        if ($order_id){
            $order = $this->user->orders()->find($order_id);
        }else{
            $order = $this->user->orders()->find($this->user->last_value);
        }
        if (!$order instanceof Order){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }
        $items = $order->items;

        if ($items->count() > 0) {
            $cart = $this->user->cart;
            if ($order->client_id){
                $this->user->operator->temp_client_id = $order->client_id;
                $this->user->operator->save();
                $this->user->refresh();
            }
            $items->each(function ($item) use (&$cart) {
                $cart->items()->create([
                    'product_id' => $item->product->id,
                    'price' => $item->product->price,
                    'quantity' => $item->quantity,
                ]);
                $item->delete();
            });

            $order->delete();

            $text = __('operator.order_cancelled_and_returned_to_cart');

            $menu = $this->getMainOperatorMenu();
            $this->sendMessage($text, 'HTML', $menu);


            $values = [
                'last_step' => null,
                'last_value' => null
            ];
            $this->userService->updateUserLastStep($this->user->telegram_id, $values);
        } else {
            if ($order instanceof Order)
                $order->delete();
            $this->sendMessage(__('operator.no_order_items_found'), 'HTML', $markup = $this->getMainOperatorMenu());
            $values = [
                'last_step' => null,
                'last_value' => null
            ];
            $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);
        }
    }
//
    public function getOrderMenuByOperator($level = 1, $categories = [])
    {

        if ($level == 1) {
            $command = 'category/';
        } else {
            $command = 'subcategory/';
        }
        $row = intval(ceil(count($categories) / 2));

        $j = 0;
        $k = 0;

        $categories = $categories->toArray();
        for ($i = 0; $i < count($categories); $i++) {
            $item = $categories[$i];
            if (isset($inline[$j]) && count($inline[$j]) == 2) {
                $k = 0;
                $j++;
                $inline[$j][$k]['text'] = $item['translation']['name'];
                $inline[$j][$k]['callback_data'] = $command . $item['id'];
            } else {
                $inline[$j][$k]['text'] = $item['translation']['name'];
                $inline[$j][$k]['callback_data'] = $command . $item['id'];
            }
            $k++;
        }
        $category = $this->categoryService->find($item['id']);
        if ($level != 1) {
            $last_index = count($inline);
            $inline[$last_index][0]['text'] = __('operator.back_to_main_category_menu');
            $inline[$last_index][0]['callback_data'] = 'back_to_main_category_menu/' . $category->restaurant_id;
        }

        // dd($inline);
        $text = __('operator.main_categories', [
            'menu_link' => env('BOT_MENU_LINK')
        ]);
        return $markup = $this->getInlineKeyboard($inline);
    }

    public function getProductsMenuByOperator($products, $restaurant_id)
    {

        $command = 'product/';
        $row = intval(ceil(count($products) / 2));

        $j = 0;
        $k = 0;

        $products = $products->toArray();
        for ($i = 0; $i < count($products); $i++) {
            $item = $products[$i];
            if (isset($inline[$j]) && count($inline[$j]) == 2) {
                $k = 0;
                $j++;
                $inline[$j][$k]['text'] = $item['translation']['name'];
                $inline[$j][$k]['callback_data'] = $command . $item['id'];
            } else {
                $inline[$j][$k]['text'] = $item['translation']['name'];
                $inline[$j][$k]['callback_data'] = $command . $item['id'];
            }
            $k++;
        }
        $last_index = count($inline);
        $inline[$last_index][0]['text'] = __('operator.back_to_main_category_menu');
        $inline[$last_index][0]['callback_data'] = 'back_to_main_category_menu/' . $restaurant_id;

        return $this->getInlineKeyboard($inline);
    }

    public function storeOrderClientNameByOperator($name)
    {
        $order = $this->user->orders()->find($this->user->last_value);

        if (!$order instanceof Order){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $this->user->last_value
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        $order->client->name =  $name;
        $order->client->save();

        $values = [
            'last_step' => 'order_confirmation',
            'last_value' => $order->id,
        ];

        $this->userService->updateUserLastStep($this->user->telegram_id, $values);
        $this->user->operator->temp_client_id = $order->client->id;
        $this->user->operator->save();


        $options = [
            [
                [
                    'text' => __('operator.order_cancel_button'),
                ]
            ]
        ];
        $text = __('operator.order_enter_your_address_or_send_geo_location_text');
        $markup = $this->getKeyboard($options, true);

        $this->sendMessage($text, 'HTML', $markup);
    }

    public function storeOrderLocationByOperator()
    {
        $order = $this->user->orders()->find($this->user->last_value);

        if (!$order instanceof Order){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $this->user->last_value
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }
        $longitude = $this->message['location']['longitude'];
        $latitude = $this->message['location']['latitude'];

        $gmaps = new \yidas\googleMaps\Client(['key' => env('GOOGLE_API_KEY', 'AIzaSyCC1KHaC4OoUjXubpLSjvnI4ve9nE_YIiI')]);
        $gmaps->setLanguage('uz-UZ');
        $pickup_point_location = env('ORIGIN_POINT_LOCATION', '41.25511,69.31867');
        if ($order->restaurant){
            $pickup_point_location = $order->restaurant->latitude . ',' . $order->restaurant->longitude;
        }
        $distanceMatrixResult = $gmaps->distanceMatrix($pickup_point_location, $latitude . ',' . $longitude);

        if ($distanceMatrixResult['status'] !== 'OK') {
            $text = __('operator.location_not_determined_by_google_api');
            return $this->sendMessage($text, $parse_mode = 'HTML');
        }

        if ($order == null) {
            $text = __('operator.no_last_order_found');
            return $this->sendMessage($text, $parse_mode = 'HTML');
        }
        // dd($distanceMatrixResult['destination_addresses'][0]);
        $pickup_address = $distanceMatrixResult['origin_addresses'][0];
        $destination_address = $distanceMatrixResult['destination_addresses'][0];
        if (!isset($distanceMatrixResult['rows'][0]['elements'])
            || !isset($distanceMatrixResult['rows'][0]['elements'][0])
            || !isset($distanceMatrixResult['rows'][0]['elements'][0]['distance'])
            ){
            $text = __('operator.no_route_found');
            return $this->sendMessage($text, $parse_mode = 'HTML');
        }
        $distance = round($distanceMatrixResult['rows'][0]['elements'][0]['distance']['value']);
        $fixed_delivery_km = env('FIXED_DELIVERY_DISTANCE', 2.5);

        $delivery_price = env('FIXED_DELIVERY_PRICE', 5000);
        $distance_rounded = round($distance / 1000, 1);
        $remaning_distance = $distance_rounded -  $fixed_delivery_km;

        if ($distance_rounded > $fixed_delivery_km) {
            $remaining_distance_delivery_price = 0;
            if ($remaning_distance > 0 && $remaning_distance <= env('FIXED_DELIVERY_DISTANCE_ROUNDING_METR_VALUE', 0.5)){
                $remaining_distance_delivery_price = env('PRICE_DELIVERY_PER_ROUNDING_UNIT_VALUE', 700);
            }
            if ($remaning_distance > env('FIXED_DELIVERY_DISTANCE_ROUNDING_METR_VALUE', 0.5)){
//                dd($remaning_distance, 1);
                $delivery_price = (
                    env('PRICE_DELIVERY_PER_ROUNDING_UNIT_VALUE', 700)
                    * $this->MRound($remaning_distance)
                    + env('FIXED_DELIVERY_PRICE', 5000)
                );
            }
            $delivery_price += $remaining_distance_delivery_price;
        }
        $order->shipping_price =  $delivery_price;
        $order->per_km_price =  env('PRICE_DELIVERY_PER_KM', 1000);
        $order->distance =  $distance_rounded;
        $order->longitude =  $longitude;
        $order->latitude =  $latitude;
        $order->save();
        $order->refresh();


        $text = __('operator.received_address_title_vs_distance_and_delivery_price', [
            'pickup' => $pickup_address,
            'from_link' => 'https://google.com/maps/',
            'destination' => $destination_address,
            'to_link' => 'https://google.com/maps/',
            'route' => "https://www.google.com/maps/dir/?api=1&origin=$pickup_point_location&destination=$latitude,$longitude",
            'distance' => $distance_rounded,
            'delivery_price' => $delivery_price
        ]);


        $options = [
            [
                [
                    'text' => __('operator.confirm_the_received_address'),
                    'callback_data' => 'confirm_the_received_address'
                ],
                [
                    'text' => __('operator.disconfirm_the_received_address'),
                    'callback_data' => 'disconfirm_the_received_address'
                ]
            ]
        ];
        $markup = $this->getInlineKeyboard($options);

        if ($this->sendMessage($text, 'HTML', $markup) !== false){
            $values = [
                'last_step' => 'order_location_or_address',
                'last_value' => $order->id
            ];
            $this->userService->updateUserLastStep($this->user->telegram_id, $values);
        }
    }

    public function confirmDeleteOrderByOperator($order_id)
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
        $order->load(['customer', 'items.product.translation']);

        $order->status = 'canceled';
        $order->save();
        $order->refresh();

        $menu = $this->getOrderViewKeyboard($order, $this->user);
        $text = $this->getOrderViewText($order, $this->user);

        $order->delete();
        return $this->editMessageText($message_id = null, $text, 'HTML', $menu, disable_web_page_preview: true);
    }

    public function disconfirmDeleteOrderByOperator($order_id)
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
        $order->load(['customer', 'items.product.translation']);

        $menu = $this->getOrderViewKeyboard($order, $this->user);
        $text = $this->getOrderViewText($order, $this->user);

        return $this->editMessageText($message_id = null, $text, 'HTML', $menu, disable_web_page_preview: true);
    }

    public function deleteOrderByOperator($order_id)
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
        $order->load(['customer', 'items.product.translation']);

        $menu = $this->getOrderDeleteKeyboard($order);
        $text = $this->getOrderViewText($order, $this->user);
        $text .= __("operator.order_are_you_sure_you_want_to_delete");

        return $this->editMessageText($message_id = null, $text, 'HTML', $menu, disable_web_page_preview: true);
    }

    public function getClientViewText($client){
        $text = __('operator.client_view_text', [
            'name' => $client->name,
            'phone' => $client->phone_number,
        ]);

        return $text;

    }

    public function getOrderDeleteKeyboard($order){

        $options = [
            [
                [
                    'text' => __('operator.client_order_confirm_delete'),
                    'callback_data' => 'order_confirm_delete/' . $order->id
                ],
                [
                    'text' => __('operator.client_order_disconfirm_delete'),
                    'callback_data' => 'order_disconfirm_delete/' . $order->id
                ],
            ]
        ];

        return $this->getInlineKeyboard($options);

    }

    public function getClientViewKeyboard($client){

        $options = [
            [
                [
                    'text' => __('operator.client_preset_client_id'),
                    'callback_data' => 'order_preset_client_id/' . $client->id
                ],
            ],
            [
                [
                    'text' => __('operator.client_preset_new_client'),
                    'callback_data' => 'order_preset_new_client'
                ],
            ]
        ];

        return $this->getInlineKeyboard($options);

    }

    function MRound($num,$parts = 2) {
        $res = $num * $parts;
        $res = intdiv(intval($num * 100), intval(env('FIXED_DELIVERY_DISTANCE_ROUNDING_METR_VALUE', 0.5) * 100));
        if ($num * 100 % intval(env('FIXED_DELIVERY_DISTANCE_ROUNDING_METR_VALUE', 0.5) * 100) == 0){
            return $res;
        }
        return $res + 1;
    }

    public function storeOrderAddressByOperator($address_location)
    {

        if (!str_contains($address_location, ',')){
            $text = __('operator.invalid_location_string');
            return $this->sendMessage($text, $parse_mode = 'HTML');
        }
        $points = explode(',', $address_location);
        $latitude = $points[0];
        $longitude = $points[1];

        $order = $this->user->orders()->find($this->user->last_value);
        if (!$order instanceof Order){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $this->user->last_value
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }
        $description_location = $address_location;

        $gmaps = new \yidas\googleMaps\Client(['key' => env('GOOGLE_API_KEY', 'AIzaSyCC1KHaC4OoUjXubpLSjvnI4ve9nE_YIiI')]);
        $gmaps->setLanguage('uz-UZ');
        $pickup_point_location = env('ORIGIN_POINT_LOCATION', '41.25511,69.31867');
        if ($order->restaurant){
            $pickup_point_location = $order->restaurant->latitude . ',' . $order->restaurant->longitude;
        }
        $distanceMatrixResult = $gmaps->distanceMatrix($pickup_point_location, $description_location);

        if ($distanceMatrixResult['status'] !== 'OK') {
            $text = __('operator.location_not_determined_by_google_api');
            return $this->sendMessage($text, $parse_mode = 'HTML');
        }

        if ($order == null) {
            $text = __('operator.no_last_order_found');
            return $this->sendMessage($text, $parse_mode = 'HTML');
        }

        $pickup_address = $distanceMatrixResult['origin_addresses'][0];
        $destination_address = $distanceMatrixResult['destination_addresses'][0];

        if (!isset($distanceMatrixResult['rows'][0]['elements'])
            || !isset($distanceMatrixResult['rows'][0]['elements'][0])
            || !isset($distanceMatrixResult['rows'][0]['elements'][0]['distance'])
        ){
            $text = __('operator.no_route_found');
            return $this->sendMessage($text, $parse_mode = 'HTML');
        }
        $distance = round($distanceMatrixResult['rows'][0]['elements'][0]['distance']['value']);
        $meters = $distance;
        $fixed_delivery_km = env('FIXED_DELIVERY_DISTANCE', 2.5);

        $delivery_price = env('FIXED_DELIVERY_PRICE', 5000);
        $distance_rounded = round($distance / 1000, 1);
//        dd($distance_rounded);
        $remaning_distance = $distance_rounded -  $fixed_delivery_km;

        if ($distance_rounded > $fixed_delivery_km) {
            $remaining_distance_delivery_price = 0;
            if ($remaning_distance > 0 && $remaning_distance <= env('FIXED_DELIVERY_DISTANCE_ROUNDING_METR_VALUE', 0.5)){
                    $remaining_distance_delivery_price = env('PRICE_DELIVERY_PER_ROUNDING_UNIT_VALUE', 700);
            }
            if ($remaning_distance > env('FIXED_DELIVERY_DISTANCE_ROUNDING_METR_VALUE', 0.5)){
                $delivery_price = (
                    env('PRICE_DELIVERY_PER_ROUNDING_UNIT_VALUE', 700)
                    * $this->MRound($remaning_distance)
                    + env('FIXED_DELIVERY_PRICE', 5000)
                );
            }
            $delivery_price += $remaining_distance_delivery_price;
        }
        $order->shipping_price =  $delivery_price;
        $order->per_km_price =  env('PRICE_DELIVERY_PER_KM', 1000);
        $order->distance =  $distance_rounded;
        $order->longitude =  $longitude;
        $order->latitude =  $latitude;
        $order->save();
        $order->refresh();

        $text = __('operator.received_address_title_vs_distance_and_delivery_price', [
            'pickup' => $pickup_address,
            'from_link' => 'https://google.com/maps/',
            'destination' => $destination_address,
            'to_link' => 'https://google.com/maps/',
            'route' => "https://www.google.com/maps/dir/?api=1&origin=$pickup_point_location&destination=$latitude,$longitude",
            'distance' => $distance_rounded,
            'delivery_price' => $delivery_price
        ]);

        $options = [
            [
                [
                    'text' => __('operator.confirm_the_received_address'),
                    'callback_data' => 'confirm_the_received_address'
                ],
                [
                    'text' => __('operator.disconfirm_the_received_address'),
                    'callback_data' => 'disconfirm_the_received_address'
                ]
            ]
        ];
        $markup = $this->getInlineKeyboard($options);

        if ($this->sendMessage($text, 'HTML', $markup) !== false){
            $values = [
                'last_step' => 'order_location_or_address',
                'last_value' => $order->id
            ];
            $this->userService->updateUserLastStep($this->user->telegram_id, $values);
        }

    }

    public function storeOrderLandmarkByOperator($address_location)
    {
        $order = $this->user->orders()->find($this->user->last_value);

        if (!$order instanceof Order){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $this->user->last_value
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        $order->address =  $address_location;
        $order->save();
        $order->refresh();

        $values = [
            'last_step' => 'order_landmark',
            'last_value' => $order->id
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __('operator.order_customer_phone_number');

        $options = [];
        if ($order->client){
            $completedOrders = $order->client->orders()->where('status', 'completed')->distinct('phone_number')->get(['phone_number']);
//            dd($completedOrders);
            foreach($completedOrders as $index => $item){
                if ($item->phone_number !== $order->client?->phone_number){
                    $options[$index] = [
                        [
                            'text' => $item->phone_number
                        ]
                    ];
                }
            }
            if ($order->client?->phone_number){
                array_push($options, [
                    [
                        'text' => $order->client->phone_number
                    ]
                ]);
            }
            array_push($options, [
                [
                    'text' => __('operator.order_back_to_location_or_address_button')
                ]
            ]);
        }else{
            $options = [
                [
                    [
                        'text' => __('operator.order_back_to_location_or_address_button')
                    ]
                ]
            ];
        }

        $this->sendMessage($text, 'HTML', $markup = $this->getKeyboard($options, $resize = true));
    }

    public function backToOrderLandmarkByOperator()
    {

        $order = $this->user->orders()->find($this->user->last_value);

        if (!$order instanceof Order){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $this->user->last_value
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }
        $order->address =  null;
        $order->save();
        $order->refresh();

        $values = [
            'last_step' => 'confirm_order_location_or_address',
            'last_value' => $order->id
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __('operator.order_landmark');
        if ($order->client){
            $client = $order->client;
            $completedOrders = Order::query()->select('id', 'address')->where('status', 'completed')->where('client_id', $client->id)->groupBy('address')->get();
            $options = [];
            foreach($completedOrders as $index => $item){
                $options[$index] = [
                    [
                        'text' => $item->address
                    ]
                ];
            }
            array_push($options, [
                [
                    'text' => __('operator.order_back_to_location_or_address_button')
                ]
            ]);
        }else{
            $options = [
                [
                    [
                        'text' => __('operator.order_back_to_location_or_address_button')
                    ]
                ]
            ];
        }


        $this->sendMessage($text, 'HTML', $markup = $this->getKeyboard($options, $resize = true));

    }

    public function backToOrderOrLocationByOperator()
    {
        $order = Order::find($this->user->last_value);
        if (!$order instanceof Order){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $this->user->last_value
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }
        if ($order->client instanceof \App\Models\Client){
            $client = $order->client;
            $completedOrders = Order::query()->select('id', 'address')->where('status', 'completed')->where('client_id', $client->id)->groupBy('address')->get();

            $text = __('operator.order_select_your_address_or_send_geo_location_text', [
                'name' => $order->client->name
            ]);

            $options = [];
            foreach($completedOrders as $index => $item){
                $options[$index] =
                    [
                        [
                            'text' => $item->address,
                            'callback_data' => 'order_select_shipping_location/' . $item->id
                        ]
                    ];
            }
            $markup = $this->getInlineKeyboard($options);
        }else{
            $options = [
                [
                    [
                        'text' => __('operator.order_cancel_button'),
                    ]
                ]
            ];
            $text = __('operator.order_enter_your_address_or_send_geo_location_text');
            $markup = $this->getKeyboard($options, true);
        }

        $this->sendMessage($text, 'HTML', $markup);

        $options = [
            [
                [
                    'text' => __('operator.order_cancel_button'),
                ]
            ]
        ];
        $text = __('operator.order_cancel_message');
        $markup = $this->getKeyboard($options, true);
        $this->sendMessage($text, 'HTML', $markup);


        $values = [
            'last_step' => 'order_confirmation',
        ];
        $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);

    }

    public function backToOrderPhoneNumberByOperator()
    {

        $order = $this->user->orders()->find($this->user->last_value);

        if (!$order instanceof Order){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $this->user->last_value
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        $values = [
            'last_step' => 'order_landmark',
            'last_value' => $order->id
        ];
        $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __('operator.order_customer_phone_number');

        if ($order->client){
            $completedOrders = $order->client->orders()->where('status', 'completed')->distinct('address')->get(['phone_number']);
            $options = [];
            foreach($completedOrders as $index => $item){
                if ($item->phone_number !== $order->client?->phone_number){
                    $options[$index] = [
                        [
                            'text' => $item->phone_number
                        ]
                    ];
                }
            }
            if ($order->client?->phone_number){
                array_push($options, [
                    [
                        'text' => $order->client->phone_number
                    ]
                ]);
            }
            array_push($options, [
                [
                    'text' => __('operator.order_back_to_location_or_address_button')
                ]
            ]);
        }else{
            $options = [
                [
                    [
                        'text' => __('operator.order_back_to_location_or_address_button')
                    ]
                ]
            ];
        }

        $this->sendMessage($text, 'HTML', $markup = $this->getKeyboard($options, $resize = true));
        // dd($summary);
    }

    public function backToOrderCustomerNoteByOperator()
    {
        $order_id = $this->user->last_value;
        $order = $this->user->orders()->find($order_id);

        if (!$order instanceof Order){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        $values = [
            'last_step' => 'order_phone_number',
            'last_value' => $order->id
        ];
        $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __("operator.order_customer_note_text");
        $options = [
            [
                [
                    'text' => __('operator.order_customer_note_button')
                ]
            ],
            [
                [
                    'text' => __('operator.order_back_to_previous_step')
                ]
            ]
        ];


        $this->sendMessage($text, 'HTML', $markup = $this->getKeyboard($options, $resize = true));
    }

    public function storeOrderContactByOperator($contact = null)
    {
        $order_id = $this->user->last_value;
        $order = $this->user->orders()->find($order_id);
        if (!$order instanceof Order){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }
        $text = __("operator.order_customer_note_text");
        $options = [
            [
                [
                    'text' => __('operator.order_customer_note_button')
                ]
            ],
            [
                [
                    'text' => __('operator.order_back_to_previous_step')
                ]
            ]
        ];

        if ($contact) {
            $phone_number = $this->message['contact']['phone_number'];
            $order->phone_number = $phone_number;

            // dd($res);
            if ($order->save()) {
                // when orderer enters other than his own number
//                if ($order->client->phone_number !== $phone_number){
//                    $order->client->phone_number =  $phone_number;
//                    $order->client->save();
//                }

                $values = [
                    'last_step' => 'order_phone_number',
                    'last_value' => $order->id
                ];
                $this->userService->updateUserLastStep($this->user->telegram_id, $values);


                $menu = $this->getKeyboard($options, $resize = true);
                return $this->sendMessage($text, 'HTML', $menu);
            } else {
                return $this->sendMessage(__("operator.something_went_wrong"));
            }
        }


        $phone_number = str_ireplace(' ', '', $this->text);
//        dd($phone_number);


        if ((strlen($phone_number) === 9) && preg_match("/^([0-9]+){9}/", $phone_number)) {
            $order->phone_number = $phone_number;
            if ($order->save()) {
                // when orderer enters other than his own number
//                if ($order->client->phone_number !== $phone_number){
//                    $order->client->phone_number =  $phone_number;
//                    $order->client->save();
//                }

                $values = [
                    'last_step' => 'order_phone_number',
                    'last_value' => $order->id
                ];
                $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);

                $menu = $this->getKeyboard($options, $resize = true);
                $this->sendMessage($text, 'HTML', $menu);
            } else {
                // dd(1);
                return $this->sendMessage(__("operator.something_went_wrong"));
            }
        } else {
            return $this->sendMessage(__("operator.invalid_phone_number"));
        }
    }

    public function getOrderViewText($order, $user = null){


        $text = '-';
        $order->items->load('product.translation');
        $items = $order->items->load('product.translation');

        $summary = 0;
        $itemsText = '';

        $prices = [];
        foreach ($items as $key => $item) {
            $itemSummary = ($item->quantity * $item->price);
            $summary += $itemSummary;
            if ($item->product) {
                $itemsText .= $item->quantity . ' x ' . $item->product->translation->name . ' = ' . $itemSummary . __('operator.item_currency_lang');
                $prices[$key]['amount'] = $itemSummary;
                $prices[$key]['label'] = $item->product->translation->name;
            } else {
                continue;
            }
        }
        $shipping_price = $order->shipping_price;
        $destination_location = $order->latitude . ',' .  $order->longitude;
        $origin_location = $order->restaurant->latitude . ',' .  $order->restaurant->longitude;
        $delivery_details = "";
        if ($order->driver){
            $name = $order->driver?->first_name . ' ' . $order->driver?->last_name;
            $delivery_details .= __('operator.order_delivery_details', [
                'delivery_person' => htmlspecialchars($name),
                'car_plate' => htmlspecialchars($order->driver?->plate),
                'phone' => $order->driver?->phone_number,
            ]);
        }

        $customer_phone = $order->phone_number;
        if (str_contains($order->phone_number, '+')){
            $customer_phone = $order->phone_number;
        }

        if ($order->is_assigned_by_operator){
            if ($user && $user->role === 'driver'){
                if ($order->is_accepted_order_by_driver){
                    $text = __('operator.order_full_template_driver_assigned_by_operator_after_approved_by_driver', [
                        'order_id' => $order->id,
                        'operator' => htmlspecialchars($order->operator?->first_name . ' ' . $order->operator?->last_name),
                        'operator_phone' => $order->operator?->phone_number,
                        'restaurant' => htmlspecialchars($order->restaurant->name),
                        'customer' => htmlspecialchars($order->client?->name),
                        'customer_phone' => $customer_phone,
                        'customer_note' => htmlspecialchars($order->customer_note),
                        'pickup' => htmlspecialchars($order->restaurant->address),
                        'from_link' => "https://www.google.com/maps/place/$origin_location",
                        'destination' => htmlspecialchars($order->address),
                        'to_link' => "https://www.google.com/maps/place/$destination_location",
                        'route' => "https://www.google.com/maps/dir/?api=1&origin=$origin_location&destination=$destination_location",
                        'distance' => $order->distance,
                        'order_content' =>  $itemsText,
                        'order_sum' => number_format(($summary), 0, '.', ','),
                        'delivery_price' => number_format(($shipping_price), 0, '.', ','),
                        'total_sum' => number_format(($summary + $shipping_price), 0, '.', ','),
                        'order_status' => $this->getOrderStatus($order, $user),
                        'delivery_details' => $delivery_details
                    ]);
                }else{
                    $text = __('operator.order_full_template_driver_assigned_by_operator', [
                        'order_id' => $order->id,
                        'operator' => htmlspecialchars($order->operator?->first_name . ' ' . $order->operator?->last_name),
                        'operator_phone' => $order->operator?->phone_number,
                        'restaurant' => htmlspecialchars($order->restaurant->name),
                        'customer' => htmlspecialchars($order->client?->name),
                        'customer_phone' => $customer_phone,
                        'customer_note' => htmlspecialchars($order->customer_note),
                        'pickup' => htmlspecialchars($order->restaurant->address),
                        'from_link' => "https://www.google.com/maps/place/$origin_location",
                        'destination' => htmlspecialchars($order->address),
                        'to_link' => "https://www.google.com/maps/place/$destination_location",
                        'route' => "https://www.google.com/maps/dir/?api=1&origin=$origin_location&destination=$destination_location",
                        'distance' => $order->distance,
                        'order_content' =>  $itemsText,
                        'order_sum' => number_format(($summary), 0, '.', ','),
                        'delivery_price' => number_format(($shipping_price), 0, '.', ','),
                        'total_sum' => number_format(($summary + $shipping_price), 0, '.', ','),
                        'order_status' => $this->getOrderStatus($order, $user),
                        'delivery_details' => $delivery_details
                    ]);
                }

            }else{
                $text = __('operator.order_full_template', [
                    'order_id' => $order->id,
                    'operator' => htmlspecialchars($order->operator?->first_name . ' ' . $order->operator?->last_name),
                    'operator_phone' => $order->operator?->phone_number,
                    'restaurant' => htmlspecialchars($order->restaurant->name),
                    'customer' => htmlspecialchars($order->client?->name),
                    'customer_phone' => $customer_phone,
                    'customer_note' => htmlspecialchars($order->customer_note),
                    'pickup' => htmlspecialchars($order->restaurant->address),
                    'from_link' => "https://www.google.com/maps/place/$origin_location",
                    'destination' => htmlspecialchars($order->address),
                    'to_link' => "https://www.google.com/maps/place/$destination_location",
                    'route' => "https://www.google.com/maps/dir/?api=1&origin=$origin_location&destination=$destination_location",
                    'distance' => $order->distance,
                    'order_content' =>  $itemsText,
                    'order_sum' => number_format(($summary), 0, '.', ','),
                    'delivery_price' => number_format(($shipping_price), 0, '.', ','),
                    'total_sum' => number_format(($summary + $shipping_price), 0, '.', ','),
                    'order_status' => $this->getOrderStatus($order, $user),
                    'delivery_details' => $delivery_details
                ]);
            }

        }else{
//            dd($order->status, $order->is_sent_to_drivers);
//            dd($this->getOrderStatus($order->status, $order->is_sent_to_drivers));
            $text = __('operator.order_full_template', [
                'order_id' => $order->id,
                'operator' => htmlspecialchars($order->operator?->first_name . ' ' . $order->operator?->last_name),
                'operator_phone' => $order->operator?->phone_number,
                'restaurant' => htmlspecialchars($order->restaurant->name),
                'customer' => htmlspecialchars($order->client?->name),
                'customer_phone' => $customer_phone,
                'customer_note' => htmlspecialchars($order->customer_note),
                'pickup' => htmlspecialchars($order->restaurant->address),
                'from_link' => "https://www.google.com/maps/place/$origin_location",
                'destination' => htmlspecialchars($order->address),
                'to_link' => "https://www.google.com/maps/place/$destination_location",
                'route' => "https://www.google.com/maps/dir/?api=1&origin=$origin_location&destination=$destination_location",
                'distance' => $order->distance,
                'order_content' =>  $itemsText,
                'order_sum' => number_format(($summary), 0, '.', ','),
                'delivery_price' => number_format(($shipping_price), 0, '.', ','),
                'total_sum' => number_format(($summary + $shipping_price), 0, '.', ','),
                'order_status' => $this->getOrderStatus($order, $user),
                'delivery_details' => $delivery_details
            ]);
        }
        return $text;
    }

    public function sendCartItemsByOperator($is_inline = false, $restaurant_id = null)
    {

        $text = __('client.cart_items');

        $this->user->cart->load('items.product.translation');
        $this->user->cart->refresh();
        $items = $this->user->cart->items;

        $summary = 0;
        $itemsText = "\n";
        $rows = [];
        if ($items->count() >= 0) {
            foreach ($items as $key => $item) {
                $itemSummary = ($item->quantity * $item->price);
                $summary += $itemSummary;
                $itemsText .= $item->quantity . ' x ' . $item->product->translation->name . ' = ' . $itemSummary .  __('operator.item_currency_lang');

                $restaurant_id = $item->product->category->restaurant->id;

//                dd($restaurant);

                $itemKeyboardRow = [
                    [
                        'text' => '-1',
                        'callback_data' => 'decrement_cart_item/' . $item->id . '/' . $item->cart->user_id
                    ],
                    [
                        'text' => '-10',
                        'callback_data' => 'decrement_cart_item_by_10x/' . $item->id . '/' . $item->cart->user_id
                    ],
                    [
                        'text' => $item->product->translation->name,
                        'callback_data' => 'display_cart_item_quantity/' . $item->id . '/' . $item->cart->user_id
                    ],
                    [
                        'text' => '+10',
                        'callback_data' => 'increment_cart_item_by_10x/' . $item->id . '/' . $item->cart->user_id
                    ],
                    [
                        'text' => '+1',
                        'callback_data' => 'increment_cart_item/' . $item->id . '/' . $item->cart->user_id
                    ]
                ];
                $rows[] = $itemKeyboardRow;
            }
            $text .= $itemsText;
            $text .= __('client.cart_items_summary', [
                'summary' => number_format($summary, 0, '.', ',')
            ]);

            $inline =  [
                [
                    [
                        'text' => __('client.confirm_the_order'),
                        'callback_data' => 'confirm_the_order'
                    ],
                ],
                [
                    [
                        'text' => __('client.continue_adding_to_cart'),
                        'callback_data' => 'back_to_main_category_menu_from_product/' .  $restaurant_id
                    ],
                    [
                        'text' => __('client.clear_the_cart'),
                        'callback_data' => 'clear_the_cart'
                    ]
                ],
            ];
            $inline = array_merge($rows, $inline);
            $markup = $this->getInlineKeyboard($inline);
            if ($is_inline == true) {
                return $this->editMessageText($message_id = null, htmlspecialchars($text), 'HTML', $markup);
            } else {
                return $this->sendMessage($text, 'HTML', $markup);
            }
        } else {
            $text = __('client.cart_empty');
            $categories = $this->categoryService->getActiveCategories($level = 1);
            $markup = $this->getOrderMenu($level = 1, $categories);
            return $this->sendMessage($text, 'HTML', $markup);
        }
    }

    public function getOrderStatus($order, $user = null){

        $status = $order->status;

        switch ($user->role){
            case 'operator':
                if ($order->is_sent_to_drivers){
                    return match ($status) {
                        'created' => __('yaratilgan'),
                        'accepted' => __("Haydovchilarga jo'natildi"),
                        'preparing' => __('tayyorlanmoqda'),
                        'prepared' => __("ovqat obketishga tayyor"),
                        'delivering' => __('yetkazilmoqda'),
                        'completed' => __('tugatildi'),
                        'canceled' => __('bekor qilindi'),
                        default => __('default')
                    };
                }else{
                    return match ($status) {
                        'created' => __('yaratilgan'),
                        'accepted' => __('Operator qabul qildi'),
                        'preparing' => __('tayyorlanmoqda'),
                        'prepared' => __("ovqat obketishga tayyor"),
                        'delivering' => __('yetkazilmoqda'),
                        'completed' => __('tugatildi'),
                        'canceled' => __('bekor qilindi'),
                        default => __('default')
                    };
                }
                break;
            case 'driver':
                if ($order->is_sent_to_drivers){
                    return match ($status) {
                        'created' => __('yaratilgan'),
                        'accepted' => __('Tayyorlanmoqda'),
                        'preparing' => __('tayyorlanmoqda'),
                        'prepared' => __("ovqat obketishga tayyor"),
                        'delivering' => __('yetkazilmoqda'),
                        'completed' => __('tugatildi'),
                        'canceled' => __('bekor qilindi'),
                        default => __('default')
                    };
                }else{
                    return match ($status) {
                        'created' => __('yaratilgan'),
                        'accepted' => __('Tayyorlanmoqda'),
                        'preparing' => __('tayyorlanmoqda'),
                        'prepared' => __("ovqat obketishga tayyor"),
                        'delivering' => __('yetkazilmoqda'),
                        'completed' => __('tugatildi'),
                        'canceled' => __('bekor qilindi'),
                        default => __('default')
                    };
                }
                break;
            default:
                return match ($status) {
                    'created' => __('yaratilgan'),
                    'accepted' => __('operator qabul qildi'),
                    'preparing' => __('tayyorlanmoqda'),
                    'prepared' => __("ovqat obketishga tayyor"),
                    'delivering' => __('yetkazilmoqda'),
                    'completed' => __('tugatildi'),
                    'canceled' => __('bekor qilindi'),
                    default => __('default')
                };
                break;

        }
    }

    public function getOrderStatusForReport($status){
        return match ($status) {
            'created' => __('yaratilgan'),
            'accepted' => __('operator qabul qildi'),
            'preparing' => __('tayyorlanmoqda'),
            'prepared' => __("ovqat obketishga tayyor"),
            'delivering' => __('yetkazilmoqda'),
            'completed' => __('tugatildi'),
            'canceled' => __('bekor qilindi'),
            default => __('default')
        };
    }

    public function acceptCustomerNoteByOperator()
    {

        $order_id = $this->user->last_value;
        $order = $this->user->orders()->find($order_id);

        if (!$order instanceof Order){
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        $customer_note = $this->text;
        if ($this->text == __('operator.order_customer_note_button')) {
            $customer_note = '-';
        }
        if ($order == null) {
            $text = __('operator.no_last_order_found');
            return $this->sendMessage($text, $parse_mode = 'HTML');
        }

        $summary = 0;
        $itemsText = '';

        $order->items->load('product.translation');
        $items = $order->items->load('product.translation');

        $prices = [];
        foreach ($items as $key => $item) {
            $itemSummary = ($item->quantity * $item->price);
            $summary += $itemSummary;
            if ($item->product) {
                $itemsText .= $item->quantity . ' x ' . $item->product->translation->name . ' = ' . $itemSummary . __('operator.item_currency_lang');
                $prices[$key]['amount'] = $itemSummary;
                $prices[$key]['label'] = $item->product->translation->name;
            } else {
                continue;
            }
        }

        $order->customer_note = $customer_note;
        $order->summary = $summary;

        if ($order->save()) {

            $text = $this->getOrderViewText($order, $this->user);

            $options = [
                [
                    [
                        'text' => __('operator.confirm_order_and_send_to_restaurants_vs_drivers'),
                        'callback_data' => 'confirm_order_and_send_to_restaurants_vs_drivers/' . $order->id,
                    ]
                ],
                [
                    [
                        'text' => __('operator.confirm_order_and_send_to_restaurants'),
                        'callback_data' => 'confirm_order_and_send_to_restaurants_only/' . $order->id,
                    ]
                ],
                [
                    [
                        'text' => __('operator.order_back_to_last_step_button'),
                        'callback_data' => 'order_back_to_last_step/' . $order->id
                    ]
                ],
            ];
            $menu = $this->getInlineKeyboard($options);
            $res = $this->sendMessage($text, 'HTML', $menu, disable_web_page_preview: true);

            $this->userService->resetSteps($this->user->telegram_id);
            $this->user->operator->temp_client_id = null;
            $this->user->operator->save();

            $text = __('operator.order_successfully_placed');
            $this->sendMainOperatorMenu($text);

        } else {
            // dd(1);
            return $this->sendMessage(__("operator.something_went_wrong"));
        }
    }

    public function sendOrderToPartnerAndDrivers($order_id = null){

        $order = $this->user->orders()->find($order_id);

        if (!$order instanceof Order){
            $text = __('operator.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        if ($order->status == 'canceled' || $order->trashed()){
            $text = __('operator.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        $order->status = 'accepted';
        $order->save();
        $order->refresh();



        $operators = $order->restaurant?->operators()->where('partner_operators.status', 'active')->get();

        if (!$operators->count() > 0){
            $text = __('operator.no_active_restaurant_employee_found');
            $this->answerCallbackQuery($text);
            return false;
        }

//        dd($employees);

        // restaurant employee will be the last one
        $receivers = new Collection();
        $receivers = $receivers->merge($operators);


        foreach ($receivers as $user){
            $text = $this->getOrderViewText($order, $user);
            $keyboard = $this->getOrderViewKeyboard($order, $user);
            $res = $this->sendMessage2($user->telegram_id, $text, 'HTML', $keyboard, disable_web_page_preview: true);

            $order->details()->create([
                'chat_id' => $user->telegram_id,
                'message_id' => $res['message_id'],
                'sender_id' => $this->user->id
            ]);
        }

        $markup = $this->getOrderViewKeyboard($order, $this->user);
        return $this->editMessageText($message_id = null, $text, 'HTML', $markup, disable_web_page_preview: true);
    }

    public function sendOrderToPartnerOnly($order_id = null){
        $order = $this->user->orders()->find($order_id);
        if (!$order instanceof Order){
            $text = __('operator.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        if ($order->status == 'canceled' || $order->trashed()){
            $text = __('operator.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        $order->status = 'accepted';
        $order->is_assigned_by_operator = true;
        $order->save();
        $order->refresh();


        $employees = $order->restaurant?->employees()->where('status', 'active')->get();
        if (!$employees->count() > 0){
            $text = __('operator.no_active_restaurant_employee_found');
            $this->answerCallbackQuery($text);
            return false;
        }

        // restaurant employees will be added to receivers
        $receivers = new Collection();
        $receivers = $receivers->merge($employees);

        foreach ($receivers as $user){
            $text = $this->getOrderViewText($order, $user);
            $keyboard = $this->getOrderViewKeyboard($order, $user);
            $res = $this->sendMessage2($user->telegram_id, $text, 'HTML', $keyboard, disable_web_page_preview: true);
            echo json_encode($res) . "\n";
        }

        $markup = $this->getOrderViewKeyboard($order, $this->user);
        return $this->editMessageText($message_id = null, $text, 'HTML', $markup, disable_web_page_preview: true);
    }

}
