<?php

namespace App\Http\Controllers\Bot\Core\Operator\Methods;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

trait OperatorCallbacks
{
    public function setOperatorLanguage($action)
    {


        $values = [
            'last_step' => 'signup_language',
            'last_value' => null
        ];
        $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);


        $userID = $this->user->telegram_id;
        $res = $this->userService->updateUserLanguage($userID, ['language' => $action]);
        if ($res->language == $action) {
            $this->setLang($action);
            $options = [
                [
                    [
                        'text' => __('operator.send_contact_button'),
                        'request_contact' => true
                    ]
                ]
            ];
            $text = __('operator.send_contact');
            $this->sendMessage($text, 'HTML', $markup = $this->getKeyboard($options, $resize = true));
        }
        $res = $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
    }

    public function processCategoryByOperator($category_id)
    {
        $category = $this->categoryService->find($category_id);

        if ($category instanceof Category) {

            if ($category->translation) {
                $category_name = $category->translation->name;
                $category_description = $category->translation->description;
            }

            $text = $category_name . " - " . $category_description;

            $items = $category->children->pick('translation.name', 'id');


            if ($items->count() > 0) {

                $menu = $this->getOrderMenu($level = 2, $items);

                return $this->editMessageText($message_id = null, htmlspecialchars($text), 'HTML', $menu);
            } else {
                return $this->answerCallbackQuery(__('operator.no_category_found'), false);
            }
        } else {
            return $this->sendMessage(__("operator.something_went_wrong"));
        }
    }

    public function processCategoryForProductsByOperator($category_id, $from_product = false)
    {
        $relations = [
            'translation',
            'products.translation'
        ];
        $category = $this->categoryService->find($category_id, $relations);

//        dd($category);

        // $this->answerCallbackQuery();

        if ($category instanceof Category) {

            if ($category->translation) {
                $category_name = $category->translation->name;
                $category_description = $category->translation->description;
            }

            $text = $category_name . " - " . $category_description;

            $items = $category->products->pick('translation.name', 'id');

            if ($items->count() > 0) {
                $menu = $this->getProductsMenuByOperator($items, $category->restaurant_id);

                if ($from_product) {
                    $res = $this->sendMessage($text, 'HTML', $menu);
                } else {
                    $res = $this->editMessageText($this->callback_query['message']['message_id'], htmlspecialchars($text), 'HTML', $menu);
                }
            } else {
                $res = $this->answerCallbackQuery(__('operator.no_product_found'), true);
            }
        } else {
            $res = $this->sendMessage(__("operator.something_went_wrong"));
        }

        return $res;
    }

    public function processProductByOperator($product_id)
    {

        $relations = [
            'translation',
            'category'
        ];
        $product = $this->productService->find($product_id, $relations);

        if ($product instanceof Product) {

            if ($product->translation) {
                $product_name = $product->translation->name;
                $product_description = $product->translation->description;
            }

            $caption = "<b>" . $product_name . "</b>" . " - " . $product_description;
            $caption .= __('operator.product_price_in_caption', [
                'price' => number_format($product->price, 0, '.', ',')
            ]);

            $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);

            $menu = $this->getProductQuantityMenu($product->id);

            $res = $this->sendPhoto($this->user->telegram_id, $product->photo_id, $caption, $menu);
            if (!$res) {
                $this->sendMessage($caption, 'HTML', $menu);
            }
            return $this->answerCallbackQuery(__('operator.back_to_product_view'), false);

        } else {
            return $this->sendMessage(__("operator.something_went_wrong"));
        }
    }

    public function addToCartByOperator($tokens)
    {

        $product = $this->productService->find($tokens[1]);
        $restaurant_id = $product->category->restaurant_id;
        $quantity = $tokens[2];
        $params = [
            'product_id' => $product->id,
            'cart_id' => $this->user->cart->id
        ];

        $values = [
            'cart_id' => $this->user->cart->id,
            'price' => $product->price,
            'product_id' => $product->id,
            'quantity' => \DB::raw('quantity + ' . $quantity)
        ];
        $cartItem = CartItem::updateOrCreate(
            $params,
            $values
        );

        if ($cartItem instanceof CartItem) {

            $text = __('operator.cart_item_added_to_cart');

            $this->user->cart->load('items.product.translation');
            $items = $this->user->cart->items;

            $summary = 0;
            $itemsText = "\n";
            if ($items->count() > 0) {
                foreach ($items as $key => $item) {
                    $itemSummary = ($item->quantity * $item->price);
                    $summary += $itemSummary;
                    $itemsText .= $item->quantity . ' x ' . $item->product->translation->name . ' = ' . $itemSummary . " so'm \n";
                }
            }


            $text .= $itemsText;
            $text .= __('operator.cart_items_summary', [
                'summary' => number_format($summary, 0, '.', ',')
            ]);

            $inline = [
                [
                    [
                        'text' => __('operator.back_to_product_view'),
                        'callback_data' => 'back_to_product_view/' . $product->id
                    ],
                    [
                        'text' => __('operator.continue_adding_to_cart'),
                        'callback_data' => 'back_to_main_category_menu_from_product/' . $restaurant_id
                    ]
                ],
                [
                    [
                        'text' => __('operator.go_to_cart'),
                        'callback_data' => 'go_to_cart'
                    ]
                ]
            ];
            $markup = $this->getInlineKeyboard($inline);

            $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);

            return $this->sendMessage($text, 'HTML', $markup);
        }
    }

    public function decrementCartItemByOperator($tokens)
    {
        $cartItemID = $tokens[1];
        $userID = $tokens[2];
        $item = $this->user->cart->items()->where('id', $cartItemID)->first();
        if ($item instanceof CartItem) {
            $restaurant_id = $item->product->category->restaurant->id;
            if ($item->quantity >= 1) {
                if ($item->quantity == 1) {
                    $item->delete();
                } else {
                    $item->decrement('quantity');
                }
            } else {
                $this->answerCallbackQuery(__('operator.no_more_decrementing'));
            }
            // dd($item);
            $this->sendCartItemsByOperator($is_inline = true, $restaurant_id);
        } else {
            $this->answerCallbackQuery(__('operator.no_cart_item_found'));
        }
    }

    public function decrementCartItemBy10XByOperator($tokens)
    {
        $cartItemID = $tokens[1];
        $userID = $tokens[2];
        $item = $this->user->cart->items()->where('id', $cartItemID)->first();

        if ($item instanceof CartItem) {
            if ($item->quantity >= 10) {
                if ($item->quantity == 10) {
                    $item->delete();
                } else {
                    $item->decrement('quantity', 10);
                }
            } else {
                $this->answerCallbackQuery(__('operator.no_more_decrementing'));
            }
            $restaurant_id = $item->product->category->restaurant->id;
            // dd($item);
            $this->sendCartItemsByOperator($is_inline = true, $restaurant_id);
        } else {
            $this->deleteMessage($this->user->telegram_id, $this->callback_query['message']['message_id']);
            $this->answerCallbackQuery(__('operator.no_cart_item_found'));
        }
    }

    public function incrementCartItemByOperator($tokens)
    {
        $cartItemID = $tokens[1];
        $userID = $tokens[2];
        // dd($cartItemID);
        $item = $this->user->cart->items()->where('id', $cartItemID)->first();

        if ($item instanceof CartItem) {
            // dd($item);
            $item->increment('quantity');
            // dd($item);
            $this->sendCartItemsByOperator($is_inline = true);
        } else {
            $this->answerCallbackQuery(__('operator.no_cart_item_found'));
        }
    }

    public function incrementCartItemBy10XByOperator($tokens)
    {
        $cartItemID = $tokens[1];
        $userID = $tokens[2];
        $item = $this->user->cart->items()->where('id', $cartItemID)->first();

        if ($item instanceof CartItem) {
            // dd($item);
            $item->increment('quantity', 10);
            // dd($item);
            $this->sendCartItemsByOperator($is_inline = true);
        } else {
            $this->answerCallbackQuery(__('operator.no_cart_item_found'));
        }
    }

    public function clearUserCartByOperator()
    {
        $this->user->cart->items()->delete();
        $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
        $this->sendRestaurantsMenu();
        $this->answerCallbackQuery(__('operator.cart_cleared'));
    }

    public function backToMainCategoryMenuByOperator($restaurant_id)
    {
        $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
        $categories = $this->categoryService->getActiveCategories($restaurant_id, $level = 1);
        // dd($categories);

        $menu = $this->getOrderMenuByOperator($level = 1, $categories);
        $text = __('operator.main_categories', [
            'menu_link' => env('BOT_MENU_LINK')
        ]);

        $res = $this->sendMessage($text, 'HTML', $menu);
        $this->answerCallbackQuery();
    }

    public function backToProductListByOperator($product_id)
    {
        $product = $this->productService->find($product_id);
//         dd($product);
        if ($product->category) {
            $category_id = $product->category->id;
            $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
            $res = $this->processCategoryForProductsByOperator($category_id, $from_product = true);
            $this->answerCallbackQuery();
        } else {
            $this->answerCallbackQuery();
        }
    }

    public function confirmOrderByOperator($is_inline = true)
    {

        // if this is going back
        if ($is_inline !== true) {
            $values = [
                'last_step' => 'order_confirmation',
            ];
            $this->userService->updateUserLastStep($this->user->telegram_id, $values);

            $text = __('operator.order_enter_your_address_or_send_geo_location_text');

            $options = [
                [

                    [
                        'text' => __('operator.order_cancel_button'),
                    ]
                ]
            ];

            $this->sendMessage($text, 'HTML', $this->getKeyboard($options, $resize = true));
        }

        $items = $this->user->cart->items;

        if ($items->count() > 0) {
            $summaryItems = $items->pluck('price')->toArray();
            $summary = array_sum($summaryItems);
            $orderData = [
                'summary' => $summary,
            ];
            if ($this->user->operator->temp_client_id !== null) {
                $client = Client::find($this->user->operator->temp_client_id);
                if ($client instanceof Client) {
                    $orderData['client_id'] = $client->id;
                }
            }
            else {
                $client = new Client;
                $client->name = '-';
                $client->save();
                $orderData['client_id'] = $client->id;
                $orderItem = $this->user->orders()->create($orderData);

                $options = [
                    [
                        [
                            'text' => __('operator.order_cancel_button'),
                        ]
                    ]
                ];
                $text = __('operator.order_enter_client_name');
                $markup = $this->getKeyboard($options, true);
                $this->sendMessage($text, 'HTML', $markup);

                $values = [
                    'last_step' => 'order_client_name',
                    'last_value' => $orderItem->id
                ];
                $this->userService->updateUserLastStep($this->user->telegram_id, $values);
                $tempItem = null;
                $items->each(function ($item) use (&$orderItem, &$tempItem) {
                    $orderItem->items()->create([
                        'product_id' => $item->product->id,
                        'price' => $item->product->price,
                        'quantity' => $item->quantity,
                    ]);
                    $tempItem = $item;
                    $item->delete();
                });

                if ($tempItem) {
                    $orderItem->restaurant_id = $tempItem->product->category->restaurant_id;
                    $orderItem->save();
                }

                $this->user->refresh();

                $this->answerCallbackQuery(__('operator.cart_items_moved_to_order_items'), false);
                return $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
            }

            $orderItem = $this->user->orders()->create($orderData);
//            $this->user->operator->temp_client_id = null;
//            $this->user->operator->save();
//            $this->user->operator->refresh();

            $tempItem = null;
            $items->each(function ($item) use (&$orderItem, &$tempItem) {
//                $orderItem->items()->create([
//                    'product_id' => $item->product->id,
//                    'price' => $item->product->price,
//                    'quantity' => $item->quantity,
//                ]);
//                $tempItem = $item;
//                $item->delete();
            });

            if ($tempItem) {
                $orderItem->restaurant_id = $tempItem->product->category->restaurant_id;
                $orderItem->save();
            }

            if ($orderItem->client instanceof Client) {
                $client = $orderItem->client;
                $completedOrders = Order::query()
                    ->select('id', 'address')
                    ->distinct('address')
                    ->where('status', 'completed')
                    ->where('client_id', $client->id)
                    ->get();
//                dd($client, $completedOrders);
                $text = __('operator.order_select_your_address_or_send_geo_location_text', [
                    'name' => $orderItem->client->name
                ]);
                $options = [];
                foreach ($completedOrders as $index => $item) {
                    $options[$index] =
                        [
                            [
                                'text' => $item->address,
                                'callback_data' => 'order_select_shipping_location/' . $item->id
                            ]
                        ];
                }
                $markup = $this->getInlineKeyboard($options);
            } else {
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

//            $values = [
//                'last_step' => 'order_confirmation',
//                'last_value' => $orderItem->id
//            ];
//            $this->userService->updateUserLastStep($this->user->telegram_id, $values);


            $this->user->refresh();

            $res = $this->answerCallbackQuery(__('operator.cart_items_moved_to_order_items'), false);
//            $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
        } else {
            $this->answerCallbackQuery(__('operator.cart_no_items_found_in_cart'));
            $res = $this->sendMessage(__('operator.cart_no_items_found_in_cart'), 'HTML', $markup = $this->getMainOperatorMenu());
            $values = [
                'last_step' => null,
                'last_value' => null
            ];
            $this->userService->updateUserLastStep($this->user->telegram_id, $values);
        }

        return $res;
    }

    public function sendOrderChooseLocationFromOrders($order_id = null)
    {
        $completeOrder = Order::find($order_id);
        $order = Order::find($this->user->last_value);
        if (!$order instanceof Order || !$completeOrder instanceof Order) {
            $text = __('operator.order_set_client_id_failed_message');
            return $this->answerCallbackQuery($text, false);
        }
        $order = $this->user->orders()->find($this->user->last_value);

        $values = [
            'last_step' => 'confirm_order_location_or_address',
            'last_value' => $order->id
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        try {
            $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        $latitude = $completeOrder->latitude;
        $longitude = $completeOrder->longitude;
        $gmaps = new \yidas\googleMaps\Client(['key' => env('GOOGLE_API_KEY', 'AIzaSyCC1KHaC4OoUjXubpLSjvnI4ve9nE_YIiI')]);
        $gmaps->setLanguage('uz-UZ');
        $pickup_point_location = env('ORIGIN_POINT_LOCATION', '41.25511,69.31867');
        if ($order->restaurant) {
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
        ) {
            $text = __('operator.no_route_found');
            return $this->sendMessage($text, $parse_mode = 'HTML');
        }
        $distance = round($distanceMatrixResult['rows'][0]['elements'][0]['distance']['value']);
        $fixed_delivery_km = env('FIXED_DELIVERY_DISTANCE', 2.5);

        $delivery_price = env('FIXED_DELIVERY_PRICE', 5000);
        $distance_rounded = round($distance / 1000, 1);
        $remaning_distance = $distance_rounded - $fixed_delivery_km;

        if ($distance_rounded > $fixed_delivery_km) {
            $remaining_distance_delivery_price = 0;
            if ($remaning_distance > 0 && $remaning_distance <= env('FIXED_DELIVERY_DISTANCE_ROUNDING_METR_VALUE', 0.5)) {
                $remaining_distance_delivery_price = env('PRICE_DELIVERY_PER_ROUNDING_UNIT_VALUE', 700);
            }
            if ($remaning_distance > env('FIXED_DELIVERY_DISTANCE_ROUNDING_METR_VALUE', 0.5)) {
                $delivery_price = (
                    env('PRICE_DELIVERY_PER_ROUNDING_UNIT_VALUE', 700)
                    * $this->MRound($remaning_distance)
                    + env('FIXED_DELIVERY_PRICE', 5000)
                );
            }
            $delivery_price += $remaining_distance_delivery_price;
        }
        $order->shipping_price = $delivery_price;
        $order->per_km_price = env('PRICE_DELIVERY_PER_KM', 1000);
        $order->distance = $distance_rounded;

        $order->latitude = $completeOrder->latitude;
        $order->longitude = $completeOrder->longitude;
        $order->save();
        $order->refresh();
        $text = __('operator.order_client_id_set_successful_message');
        $this->answerCallbackQuery($text, false);

        $text = __('operator.order_landmark');

        $text = __('operator.order_landmark');
        if ($order->client) {
            $client = $order->client;
            $completedOrders = Order::query()->select('id', 'address')->where('status', 'completed')->where('client_id', $client->id)->groupBy('address')->get();
            $options = [];
            foreach ($completedOrders as $index => $item) {
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
        } else {
            $options = [
                [
                    [
                        'text' => __('operator.order_back_to_location_or_address_button')
                    ]
                ]
            ];
        }
        $markup = $this->getKeyboard($options, true);
        $this->sendMessage($text, 'HTML', $markup);
    }

    public function resendOrderWithPreparedToDrivers($order_id = null)
    {
        $order = $this->user->orders()->find($order_id);
        if (!$order instanceof Order) {
            $text = __('operator.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }
//        $order->status = 'preparing';
        $order->is_sent_to_drivers = true;
        $order->is_assigned_by_operator = false;
        $order->driver_id = null;
        $order->save();
        $order->refresh();


        $drivers = $this->userService->getActiveDrivers(true);
        $receivers = $drivers->filter(function ($item) {
            if ($item->telegram_id)
                return $item->telegram_id;
            return false;
        });

        foreach ($receivers as $user) {
            $text = $this->getOrderViewText($order, $user);
            $keyboard = $this->getOrderViewKeyboard($order, $user);
            $res = $this->sendMessage2($user->telegram_id, $text, 'HTML', $keyboard);
            echo json_encode($res) . "\n";
        }

        $text = $this->getOrderViewText($order, $this->user);
        $markup = $this->getOrderViewKeyboard($order, $this->user);
        return $this->editMessageText($message_id = null, $text, 'HTML', $markup, disable_web_page_preview: true);
    }

    public function resendOrderToDrivers($order_id = null)
    {
        $order = $this->user->orders()->find($order_id);
        if (!$order instanceof Order) {
            $text = __('operator.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        if ($order->status == 'canceled' || $order->trashed()) {
            $text = __('operator.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        $order->driver_id = null;
        $order->save();
        $order->refresh();


        $drivers = $this->userService->getActiveDrivers(true);
        $receivers = $drivers->filter(function ($item) {
            if ($item->telegram_id)
                return $item->telegram_id;
            return false;
        });

        $text = $this->getOrderViewText($order, $this->user);
        foreach ($receivers as $user) {
            $keyboard = $this->getOrderViewKeyboard($order, $user);
            $res = $this->sendMessage2($user->telegram_id, $text, 'HTML', $keyboard);
            echo json_encode($res) . "\n";
        }

        $markup = $this->getOrderViewKeyboard($order, $this->user);
        return $this->editMessageText($message_id = null, $text, 'HTML', $markup, disable_web_page_preview: true);
    }

    public function updateOrder($order_id = null)
    {
        $order = Order::withTrashed()->find($order_id);
        if (!$order instanceof Order) {
            $text = __('client.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }
        $order->load(['operator', 'items.product.translation']);

        switch ($this->user->role) {
            case 'driver':
                $menu = $this->getOrderViewKeyboard($order, $this->user);
                $text = $this->getOrderViewText($order, $this->user);
                return $this->editMessageText($message_id = null, $text, 'HTML', $menu, disable_web_page_preview: true);

                if ($order->status == 'canceled') {
                    $text =  __("driver.order_your_order_was_canceled_step1", [
                        'order_id' => $order->id,
                        'restaurant' => $order->restaurant->name,
                        'operator_name' => $order->operator->name,
                        'operator_phone' => $order->operator->phone_number,
                    ]);
                    $this->sendMessage($text, 'HTMl');
                }
                break;
            default:
                $menu = $this->getOrderViewKeyboard($order, $this->user);
                $text = $this->getOrderViewText($order, $this->user);
                return $this->editMessageText($message_id = null, $text, 'HTML', $menu, disable_web_page_preview: true);
                break;
        }

        return $this->answerCallbackQuery(__('admin.order_updated'), $show_alert = false);
    }

    public function sendDriversListForOperator($order_id = null)
    {
        $order = $this->user->orders()->find($order_id);
        if (!$order instanceof Order) {
            $text = __('operator.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        if ($order->status == 'canceled' || $order->trashed()) {
            $text = __('operator.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

//        $order->driver_id = null;
//        $order->save();
//        $order->refresh();


        $drivers = $this->userService->getActiveDrivers(true);
        $text = $this->getOrderViewText($order, $this->user);
        $driver_items = [];

        foreach ($drivers as $item) {
            $driver_items[] = [
                [
                    'text' => $item->name,
                    'callback_data' => 'order_set_driver_by_operator/' . $order_id . "/" . $item->id
                ]
            ];
        }
        $driver_items[] = [
            [
                'text' => __("operator.back_to_order_view_button"),
                'callback_data' => 'back_to_order_view/' . $order_id
            ]
        ];

        $markup = $this->getInlineKeyboard($driver_items);
        return $this->editMessageText($message_id = null, $text, 'HTML', $markup, disable_web_page_preview: true);
    }

    public function setOrderDriverByOperator($order_id = null, $driver_id = null)
    {
        $order = $this->user->orders()->find($order_id);
        if (!$order instanceof Order) {
            $text = __('operator.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        if ($order->status == 'canceled' || $order->trashed()) {
            $text = __('operator.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

//        $order->status = 'accepted';
//        $order->save();
//        $order->refresh();


        $employees = $order->restaurant?->employees()->where('status', 'active')->get();
        if (!$employees->count() > 0) {
            $text = __('operator.no_active_restaurant_employee_found');
            $this->answerCallbackQuery($text);
            return false;
        }
        $driver = User::find($driver_id);
        $prev_driver = User::find($order->driver_id);
        if ($prev_driver instanceof   User){
            $text = __("driver.order_driver_changed_to_another_driver_by_operator",[
                'order_id' => $order->id
            ]);
            $res = $this->sendMessage2($prev_driver->telegram_id, $text, 'HTML', disable_web_page_preview: true);
        }
        if (!$driver->telegram_id) {
            $text = __('operator.selected_driver_does_not_have_telegram_id');
            $this->answerCallbackQuery($text);
            return false;
        }
        $order->driver_id = $driver->id;
        $order->is_assigned_by_operator = true;
        $order->save();
        $order->refresh();


        $text = $this->getOrderViewText($order, $driver);
        $keyboard = $this->getOrderViewKeyboard($order, $driver);
        $res = $this->sendMessage2($driver->telegram_id, $text, 'HTML', $keyboard, disable_web_page_preview: true);


        $text = $this->getOrderViewText($order, $this->user);
        $markup = $this->getOrderViewKeyboard($order, $this->user);
        return $this->editMessageText($message_id = null, $text, 'HTML', $markup, disable_web_page_preview: true);
    }

    public function sendOrderViewByOperator($order_id = null)
    {
        $order = $this->user->orders()->find($order_id);

//        dd($order);

        if (!$order instanceof Order) {
            $text = __('operator.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        if ($order->status == 'canceled' || $order->trashed()) {
            $text = __('operator.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        $text = $this->getOrderViewText($order, $this->user);

        $markup = $this->getOrderViewKeyboard($order, $this->user);
        return $this->editMessageText($message_id = null, $text, 'HTML', $markup, disable_web_page_preview: true);
    }
//
//    public function cancelOrderByOperator()
//    {
//
//
//        $order = $this->user->orders()->find($this->user->last_value);
//
////         dd($order);
//
//        $items = $order->items;
//
////         dd($items);
//
//        if ($items->count() > 0) {
//            // $summaryItems = $items->pluck('price')->toArray();
//            // // dd($summaryItems->toArray());
//            // $summary = array_sum($summaryItems);
//
//            $cart = $this->user->cart;
//
//            $items->each(function ($item) use (&$cart) {
//                $cart->items()->create([
//                    'product_id' => $item->product->id,
//                    'price' => $item->product->price,
//                    'quantity' => $item->quantity,
//                ]);
//                $item->delete();
//            });
//
//            $text = __('operator.order_cancelled_and_returned_to_cart');
//
//            $menu = $this->getMainMenu();
//            $res = $this->sendMessage($text, 'HTML', $menu);
//
//
//            $values = [
//                'last_step' => null,
//                'last_value' => null
//            ];
//            $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);
//            // $this->user->refresh();
//            // dd($this->user);
//        } else {
//            $this->answerCallbackQuery(__('operator.no_order_items_found'));
//            $this->sendMessage(__('operator.no_order_items_found'), 'HTML', $markup = $this->getMainMenu());
//            $values = [
//                'last_step' => null,
//                'last_value' => null
//            ];
//            $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);
//        }
//    }
//
//
    public function confirmOrderAddressOrLocationByOperator()
    {
        $order_id = $this->user->last_value;
        $order = $this->user->orders()->find($order_id);

        if (!$order instanceof Order) {
            $text = __('operator.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        $values = [
            'last_step' => 'confirm_order_location_or_address',
            'last_value' => $order->id
        ];
        $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);


        $res = $this->answerCallbackQuery(__('operator.order_address_or_location_confirmation_message'), $is_alert = false);

        $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);

        $text = __('operator.order_landmark');

        if ($order->client) {
            $completedOrders = $order->client->orders()->where('status', 'completed')->distinct('address')->get(['address']);
            $addressArray = [];
            foreach ($completedOrders as $index => $item) {
                $addressArray[$index] = [
                    'text' => $item->address
                ];
            }
            $options = [
                $addressArray,
                [
                    [
                        'text' => __('operator.order_back_to_location_or_address_button')
                    ]
                ]
            ];
        } else {
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
//    public function confirmOrderAddressOrLocationByOperator()
//    {
//        $order = $this->user->orders()->find($this->user->last_value);
//
//        if ($order == null) {
//            $text = __('operator.no_last_order_found');
//            return $this->sendMessage($text, $parse_mode = 'HTML');
//        }
//
//        $values = [
//            'last_step' => 'confirm_order_location_or_address',
//            'last_value' => $order->id
//        ];
//        $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);
//
//
//        $res = $this->answerCallbackQuery(__('operator.order_address_or_location_confirmation_message'), $is_alert = false);
//
//        $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
//
//        $text = __('operator.order_customer_phone_number');
//
//        $options = [
//            [
//                [
//                    'text' => __('operator.order_back_to_location_or_address_button')
//                ]
//            ]
//        ];
//
//        $this->sendMessage($text, 'HTML', $markup = $this->getKeyboard($options, $resize = true));
//    }

    public function disconfirmOrderAddressOrLocationByOperator()
    {
        $order_id = $this->user->last_value;
        $order = $this->user->orders()->find($order_id);

        if (!$order instanceof Order) {
            $text = __('operator.order_not_found_or_cancelled', [
                'order_id' => $order_id
            ]);
            $this->sendMessage($text);
            $this->answerCallbackQuery($text);
            return true;
        }

        $values = [
            'last_step' => 'order_confirmation',
            'last_value' => $order->id
        ];
        $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);

        $text = __('operator.order_address_or_location_disconfirmation_message');
        $this->sendMessage($text, $parse_mode = 'HTML');
        $res = $this->answerCallbackQuery(__('operator.order_address_or_location_disconfirmation_message'), $is_alert = false);
        // dd($summary);

    }
}
