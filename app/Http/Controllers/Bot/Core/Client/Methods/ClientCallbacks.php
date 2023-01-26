<?php

namespace App\Http\Controllers\Bot\Core\Client\Methods;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

trait ClientCallbacks
{
    public function setUserLanguage($action)
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
                        'text' => __('client.send_contact_button'),
                        'request_contact' => true
                    ]
                ]
            ];
            $text = __('client.send_contact');
            $this->sendMessage($text, 'HTML', $markup = $this->getKeyboard($options, $resize = true));
        }
        $res = $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
    }


    public function checkIfUserSubscribedToChannel()
    {
        $data = [
            'chat_id' => "@" . env('TELEGRAM_BOT_CHANNEL', "dasturchi_xizmati"),
            'user_id' => $this->callback_query['from']['id']
        ];
        $res = $this->postRequest('getChatMember', $data, []);
//        dd($res);
        if (isset($res['ok']) && $res['ok'] !== true){
            return $res;
        }
        if (isset($res['status']) && $res['status'] !== 'member'){
            return false;
        }
        $values = [
            'last_step' => 'channel_subscription_confirmed',
            'last_value' => null
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);
        return true;
    }


    public function editUserLanguages()
    {
        $text = __('client.settings_user_languages_text');
        $message_id = $this->callback_query['message']['message_id'];
        $languages = [
            [
                [
                    'text' =>  __('client.user_uzbek_language_button'),
                    'callback_data' => 'uz'
                ]
            ],
            [
                [
                    'text' =>  __('client.user_russian_language_button'),
                    'callback_data' => 'ru'
                ]
            ],
            [
                [
                    'text' =>  __('client.settings_back_to_user_settings'),
                    'callback_data' => 'back_to_user_settings'
                ]
            ]
        ];
        return $this->editMessageText($message_id, htmlspecialchars($text), 'HTML', $menu = $this->getInlineKeyboard($languages));
    }
    public function editUserPhone()
    {
        $values = [
            'last_step' => 'user_phone',
            'last_value' => null
        ];

        $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);



        $text = __('client.settings_enter_phone_number_or_send_contact');

        $options = [
            [
                [
                    'text' => __('client.settings_send_phone_number_button'),
                    'request_contact' => true
                ]
            ],
            [
                [
                    'text' => __('client.back_to_main_menu'),
                ]
            ]
        ];

        $res = $this->sendMessage($text, 'HTML', $this->getKeyboard($options, $resize = true));
        $res = $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
        return $res;
    }
    public function editUserName()
    {
        $values = [
            'last_step' => 'user_name',
            'last_value' => null
        ];

        $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);


        $text = __('client.settings_enter_name');

        $options = [
            [
                [
                    'text' => __('client.back_to_main_menu'),
                ]
            ]
        ];

        $res = $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);

        $res = $this->sendMessage($text, 'HTML', $this->getKeyboard($options, $resize = true));

        return $res;
    }

    public function updateUserName($name)
    {
        $values = [
            'name' => $name,
            'last_step' => null,
            'last_value' => null
        ];
        $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);
        $this->user = $res;
        if ($res) {
            $res = $this->sendUserSettings();
        } else {
            $res = $this->sendMessage(__("client.something_went_wrong"));
        }

        return $res;
    }

    public function sendStoreUserContactAndFindInDatabase($contact = null)
    {
        if ($contact) {
            $phone_number = $this->message['contact']['phone_number'];
            $values = [
                'phone_number' => $phone_number,
            ];
            $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);

            $offset = 0;
            if (strlen($phone_number) === 12 || strlen($phone_number) === 13){
                $offset = 4;
            }
            $shortened = substr($phone_number, $offset);


            if ($res instanceof User) {

                $values = [
                    'last_step' => 'phone_number_saved',
                    'last_value' => null
                ];
                $this->userService->updateUserLastStep($this->user->telegram_id, $values);
                $names = [];
                $predictions = \App\Models\Client::where('phone_number', 'LIKE', '%'. $shortened .'%')->get();
                if ($predictions->count() > 0){
                    foreach ($predictions as $key => $item){
                        $names[$key][] = [
                            'text' => $item->name
                        ];
                    }
                }

                $text = __("client.settings_user_phone_saved_enter_your_name");
                $this->deleteMessage($this->message['from']['id'], $this->message['message_id'] - 2);
                return $this->sendMessage($text, 'HTML', $this->getKeyboard($names, true));
            } else {
                return $this->sendMessage(__("client.something_went_wrong"));
            }
        }

        $phone_number = str_ireplace(' ', '', $this->text);

        if (preg_match("/^([0-9]+){9}/", $phone_number)) {
            // $res = $this->userService->updateContact($this->message['from']['id'], $phone_number);
            $this->user->phone_number = $phone_number;

            // dd($res);
            if ($this->user->save()) {
                $values = [
                    'last_step' => null,
                    'last_value' => null
                ];
                $this->userService->updateUserLastStep($this->user->telegram_id, $values);
                $this->deleteMessage($this->message['from']['id'], $this->message['message_id'] - 2);
                return $this->sendUserSettings();
            } else {
                // dd(1);
                return $this->sendMessage(__("client.something_went_wrong"));
            }
        } else {
            return $this->sendMessage(__("client.invalid_phone_number"));
        }
    }
    public function updateUserContact($contact = null)
    {

        if ($contact) {
            $phone_number = $this->message['contact']['phone_number'];
            $values = [
                'phone_number' => $phone_number,
                'last_step' => null,
                'last_value' => null
            ];
            $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);

            if ($res instanceof User) {

                $text = __("client.settings_user_phone_saved");

                $this->sendUserSettings();
                $res = $this->sendMessage($text, 'HTML', $this->getMainUserMenu());
                $this->deleteMessage($this->message['from']['id'], $this->message['message_id'] - 2);

                return true;
            } else {
                return $this->sendMessage(__("client.something_went_wrong"));
            }
            return false;
        }


        $phone_number = str_ireplace(' ', '', $this->text);

        if (preg_match("/^([0-9]+){9}/", $phone_number)) {
            // $res = $this->userService->updateContact($this->message['from']['id'], $phone_number);
            $this->user->phone_number = $phone_number;

            // dd($res);
            if ($this->user->save()) {
                $values = [
                    'last_step' => null,
                    'last_value' => null
                ];
                $this->userService->updateUserLastStep($this->user->telegram_id, $values);
                $this->deleteMessage($this->message['from']['id'], $this->message['message_id'] - 2);
                return $this->sendUserSettings();
            } else {
                // dd(1);
                return $this->sendMessage(__("client.something_went_wrong"));
            }
        } else {
            return $this->sendMessage(__("client.invalid_phone_number"));
        }
    }

    public function updateUserLanguage($action)
    {
        $userID = $this->user->telegram_id;
        $res = $this->userService->updateUserLanguage($userID, ['language' => $action]);
        if ($res->language == $action) {
            $this->setLang($action);
            return true;
//            $this->sendUserSettings();
        }
//        $this->sendMainUserMenu();
//        return $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
    }

    public function backToUserSettings()
    {
        $inline = [];

        $inline[0][0]['text'] = __('client.user_language_button');
        $inline[0][0]['callback_data'] = 'lang';
        $inline[1][0]['text'] = __('client.user_phone_number_button');
        $inline[1][0]['callback_data'] = 'phone';
        $inline[2][0]['text'] = __('client.user_name_button');
        $inline[2][0]['callback_data'] = 'name';

        // dd($inline);
        $text = __('client.user_settings_text');
        $markup = $this->getInlineKeyboard($inline);
        $message_id = $this->callback_query['message']['message_id'];
        return $this->editMessageText($message_id, htmlspecialchars($text), 'HTML', $markup);
    }

    public function processCategory($category_id)
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
                return $this->answerCallbackQuery(__('client.no_category_found'), false);
            }
        } else {
            return $this->sendMessage(__("client.something_went_wrong"));
        }
    }
    public function processCategoryForProducts($category_id, $from_product = false)
    {
        $relations = [
            'translation',
            'products.translation'
        ];
        $category = $this->categoryService->find($category_id, $relations);

//         dd($category);

        // $this->answerCallbackQuery();

        if ($category instanceof Category) {

            if ($category->translation) {
                $category_name = $category->translation->name;
                $category_description = $category->translation->description;
            }

            $text = $category_name . " - " . $category_description;

            $items = $category->products->pick('translation.name', 'id');
            // dd($items);

            if ($items->count() > 0) {
                $menu = $this->getProductsMenu($items);

                if ($from_product) {
                    return $this->sendMessage($text, 'HTML', $menu);
                } else {
                    return $this->editMessageText($message_id = null, htmlspecialchars($text), 'HTML', $menu);
                }
            } else {
                return $this->answerCallbackQuery(__('client.no_product_found'), false);
            }
        } else {
            return $this->sendMessage(__("client.something_went_wrong"));
        }
    }
    public function processProduct($product_id)
    {

        $relations = [
            'translation',
            'category'
        ];
        $product = $this->productService->find($product_id, $relations);

        // dd($product);

        // $res = $this->answerCallbackQuery();


        if ($product instanceof Product) {

            if ($product->translation) {
                $product_name = $product->translation->name;
                $product_description = $product->translation->description;
            }

            $caption = "<b>" . $product_name . "</b>" . " - " . $product_description;
            $caption .=  __('client.product_price_in_caption', [
                'price' => number_format($product->price , 0, '.', ',')
            ]);

            $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);

            $menu = $this->getProductQuantityMenu($product->id);

            $res = $this->sendPhoto($this->user->telegram_id, $product->photo_id, $caption, $menu);
            dd($res);

            // return $this->editMessageText($message_id = null, htmlspecialchars($text), 'HTML', $menu);

            // if ($items->count() > 0) {
            // } else {
            //     return $this->answerCallbackQuery(__('client.no_product_found'), false);
            // }
        } else {
            return $this->sendMessage(__("client.something_went_wrong"));
        }
    }

    public function addToCart($tokens)
    {

        $product = $this->productService->find($tokens[1]);
        $quantity = $tokens[2];
        $params = [
            'product_id' => $product->id,
            'cart_id' => $this->user->cart->id
        ];

        $values = [
            'price' => $product->price,
            'product_id' => $product->id,
            'quantity' => \DB::raw('quantity + ' .  $quantity)
        ];
        // dd($params, $values);
        $cartItem = CartItem::updateOrCreate(
            $params,
            $values
        );

        if ($cartItem instanceof CartItem) {

            $text = __('client.cart_item_added_to_cart');

            $this->user->cart->load('items.product.translation');
            $items = $this->user->cart->items;

            // dd($items);
            $summary = 0;
            $itemsText = "\n";
            if ($items->count() > 0) {
                foreach ($items as $key => $item) {
                    $itemSummary = ($item->quantity * $item->price);
                    $summary += $itemSummary;
                    $itemsText .= $item->quantity . ' x ' . $item->product->translation->name . ' = ' . $itemSummary . " so'm \n";
                }
            }



            // dd($itemsText);
            $text .= $itemsText;
            $text .= __('client.cart_items_summary', [
                'summary' => number_format($summary, 0, '.', ',')
            ]);

            $inline = [
                [
                    [
                        'text' => __('client.back_to_product_view'),
                        'callback_data' => 'back_to_product_view/' . $product->id
                    ],
                    [
                        'text' => __('client.continue_adding_to_cart'),
                        'callback_data' => 'back_to_main_category_menu_from_product'
                    ]
                ],
                [
                    [
                        'text' => __('client.go_to_cart'),
                        'callback_data' => 'go_to_cart'
                    ]
                ]
            ];
            $markup = $this->getInlineKeyboard($inline);
            $this->sendMessage($text, 'HTML', $markup);

            $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
        }
    }

    public function decrementCartItem($tokens)
    {
        $cartItemID = $tokens[1];
        $userID = $tokens[2];
        // dd($cartItemID);
        $item = $this->user->cart->items()->where('id', $cartItemID)->first();
        if ($item instanceof CartItem) {
            // dd($item);
            if ($item->quantity > 1) {
                $item->decrement('quantity');
            } else {
                $this->answerCallbackQuery(__('client.no_more_decrementing'));
            }
            // dd($item);
            $this->sendCartItems($is_inline = true);
        } else {
            $this->answerCallbackQuery(__('client.no_cart_item_found'));
        }
    }
    public function incrementCartItem($tokens)
    {
        $cartItemID = $tokens[1];
        $userID = $tokens[2];
        // dd($cartItemID);
        $item = $this->user->cart->items()->where('id', $cartItemID)->first();

        if ($item instanceof CartItem) {
            // dd($item);
            $item->increment('quantity');
            // dd($item);
            $this->sendCartItems($is_inline = true);
        } else {
            $this->answerCallbackQuery(__('client.no_cart_item_found'));
        }
    }
    public function clearUserCart()
    {

        $categories = $this->categoryService->getActiveCategories($level = 1);

        $this->user->cart->items()->delete();

        $menu = $this->getOrderMenu($level = 1, $categories);
        $text = __('client.main_categories', [
            'menu_link' => env('BOT_MENU_LINK')
        ]);

        return $this->editMessageText($message_id = null, htmlspecialchars($text), 'HTML', $menu);
        $this->answerCallbackQuery(__('client.cart_cleared'));
    }

    public function backToMainCategoryMenu()
    {
        $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
        $categories = $this->categoryService->getActiveCategories($level = 1);
        // dd($categories);

        $menu = $this->getOrderMenu($level = 1, $categories);
        $text = __('client.main_categories', [
            'menu_link' => env('BOT_MENU_LINK')
        ]);

        $res = $this->sendMessage($text, 'HTML', $menu);
        $this->answerCallbackQuery();
    }

    public function backToProductList($product_id)
    {
        $product = $this->productService->find($product_id);
        // dd($product);
        if ($product->category) {
            $category_id = $product->category->id;
            $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
            $res = $this->processCategoryForProducts($category_id, $from_product = true);
            $this->answerCallbackQuery();
        } else {
            $this->answerCallbackQuery();
        }
    }


    public function processSubcategory($category_id)
    {
        $category = $this->categoryService->find($category_id);

        $res = $this->answerCallbackQuery();

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
                return $this->answerCallbackQuery(__('client.no_category_found'), false);
            }
        } else {
            return $this->sendMessage(__("client.something_went_wrong"));
        }
    }

    public function confirmOrderByClient()
    {

        $items = $this->user->cart->items;

        if ($items->count() > 0) {
            $summaryItems = $items->pluck('price')->toArray();
            // dd($summaryItems->toArray());
            $summary = array_sum($summaryItems);
            // dd($summary);
            $orderItem = $this->user->orders()->create([
                'phone_number' => $this->user->phone_number,
                'summary' => $summary
            ]);


            $items->each(function ($item) use (&$orderItem) {
                $orderItem->items()->create([
                    'product_id' => $item->product->id,
                    'price' => $item->product->price,
                    'quantity' => $item->quantity,
                ]);
                $item->delete();
            });

            $text = __('client.order_enter_your_address_or_send_geo_location_text');

            $options = [
                [
                    [
                        'text' => __('client.send_geolocation'),
                        'request_location' => true
                    ]
                ],
                [

                    [
                        'text' => __('client.order_cancel_button'),
                    ]
                ]
            ];

            $res = $this->sendMessage($text, 'HTML', $this->getKeyboard($options, $resize = true));


            $values = [
                'last_step' => 'order_confirmation',
                'last_value' => $orderItem->id
            ];
            $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);

            $res = $this->answerCallbackQuery(__('client.cart_items_moved_to_order_items'), false);
            $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
        } else {
            $res = $this->answerCallbackQuery(__('client.cart_no_items_found_in_cart'));
            $this->sendMessage(__('client.cart_no_items_found_in_cart'), 'HTML', $markup = $this->getMainUserMenu());
            $values = [
                'last_step' => null,
                'last_value' => null
            ];
            $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);
        }

        return false;
    }

    public function cancelOrderByClient()
    {
        $order = $this->user->orders()->find($this->user->last_value);

        $items = $order->items;

        if ($items->count() > 0) {
            $cart = $this->user->cart;

            $items->each(function ($item) use (&$cart) {
                $cart->items()->create([
                    'product_id' => $item->product->id,
                    'price' => $item->product->price,
                    'quantity' => $item->quantity,
                ]);
                $item->delete();
            });

            $text = __('client.order_cancelled_and_returned_to_cart');

            $menu = $this->getMainUserMenu();
            $res = $this->sendMessage($text, 'HTML', $menu);

            $values = [
                'last_step' => null,
                'last_value' => null
            ];
            $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);
        } else {
            $res = $this->answerCallbackQuery(__('client.no_order_items_found'));
            $this->sendMessage(__('client.no_order_items_found'), 'HTML', $markup = $this->getMainUserMenu());
            $values = [
                'last_step' => null,
                'last_value' => null
            ];
            $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);
        }

        return false;
    }

    public function confirmOrderAddressOrLocation()
    {
        $order = $this->user->orders()->find($this->user->last_value);

        if ($order == null) {
            $text = __('client.no_last_order_found');
            return $this->sendMessage($text, $parse_mode = 'HTML');
        }

        $values = [
            'last_step' => 'confirm_order_location_or_address',
            'last_value' => $order->id
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);


        $this->answerCallbackQuery(__('client.order_address_or_location_confirmation_message'), $is_alert = false);

        $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);

        $text = __('client.order_customer_phone_number');

        $options = [
            [
                [
                    'text' => __('client.send_contact_button'),
                    'request_contact' => true
                ]
            ],
            [
                [
                    'text' => __('client.order_back_to_location_or_address_button')
                ]
            ]
        ];

        $this->sendMessage($text, 'HTML', $markup = $this->getKeyboard($options, $resize = true));


    }
    public function disconfirmOrderAddressOrLocation()
    {


        $order = $this->user->orders()->find($this->user->last_value);

        if ($order == null) {
            $text = __('client.no_last_order_found');
            return $this->sendMessage($text, $parse_mode = 'HTML');
        }

        $values = [
            'last_step' => 'order_confirmation',
            'last_value' => $order->id
        ];
        $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);

        $text = __('client.order_address_or_location_disconfirmation_message');
        $this->sendMessage($text, $parse_mode = 'HTML');
        $res = $this->answerCallbackQuery(__('client.order_address_or_location_disconfirmation_message'), $is_alert = false);

        // dd($summary);


    }
    public function choosePaymentMethod($method = 'cash')
    {


        // dd($method);

        // dd($this->user->last_value);
        $order = $this->user->orders()->find($this->user->last_value);

        // dd($order);

        if ($order == null) {
            $text = __('client.no_last_order_found');
            return $this->sendMessage($text, $parse_mode = 'HTML');
        }

        // $this->user->first_name = $name;
        if (!$order instanceof Order) {
            return $this->sendMessage(__("client.something_went_wrong"));
        }

        // dd($admins);

        $order->items->load('product.translation');
        $items = $order->items->load('product.translation');

        $payment_method = '';
        if ($method == 'cash') {
            $order_id = date('my') . '-' . $order->id;
            $order->id = $order_id;
            $order->payment_type = 'cash';
            $order->save();
            $payment_method = 'client.order_payment_method_cash';
            $values = [
                'last_step' => null,
                'last_value' => null
            ];
            $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);

            $text = __('client.order_received', [
                'order_id' => $order->id
            ]);

            $this->sendMessage($text, 'HTML', $reply_markup = $this->getMainMenu());
            $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
        }

        if ($method === 'click') {
            // dd($method);
            $payment_method = 'client.order_payment_method_click';
            $order_id = date('my') . '-' . $order->id;
            $order->payment_type = $method;
            $order->id = $order_id;
            $order->save();
            $order->refresh();
            // dd($order);
            // dd($order->payment_type);

            $orderTitle = __('client.order_title', [
                'order_id' => $order->id,
                'name' => env('TELEGRAM_BOT_NAME')
            ]);

            $itemsText = $this->getOrderInvoiceText($order);
            $prices = $this->getOrderInvoicePrices($order);

            // dd($order->payment_type);

            $orderData['order_id'] = $order->id;
            $orderData['payment_method'] = $order->payment_type;

            // dd($orderData);


            $res = $this->sendInvoice($this->callback_query['from']['id'], $orderData, $orderTitle, $itemsText, $prices);
            $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);


        }

        if ($method === 'payme') {

            $values = [
                'last_step' => 'pre_checkout_query',
                'last_value' => $order->id
            ];

            $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);

            $order->payment_type = 'payme';
            $order_id = date('my') . '-' . $order->id;
            $order->id = $order_id;
            $order->save();
            $order->refresh();

            $orderTitle = __('client.order_title', [
                'order_id' => $order->id,
                'name' => env('TELEGRAM_BOT_NAME')
            ]);

            $itemsText = $this->getOrderInvoiceText($order);
            $prices = $this->getOrderInvoicePrices($order);

            $orderData['order_id'] = $order->id;
            $orderData['payment_method'] = 'payme';

            $res = $this->sendInvoice($this->callback_query['from']['id'], $orderData, $orderTitle, $itemsText, $prices);

            $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
            // die;
        }

        // dd($method);

        if ($method == 'cash') {

            $text = $this->getOrderText($order);

            $keyboard = $this->getOrderInlineMenu($order);

            $admins = $this->userService->getAllEmployees();

            $admins->each(function ($item) use ($text, $keyboard) {

                $data = [
                    'chat_id' => $item->telegram_id,
                    'text' => htmlspecialchars($text),
                    'reply_markup' => $keyboard
                ];

                $res = $this->postRequest('sendMessage', $data);

            });
        }


    }


    public function confirmOrderAddressOrLocationByClient()
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

    public function repeatOrder($order_id)
    {

        $order = $this->user->orders()->find($order_id);

        // dd($order);

        if ($order == null) {
            $text = __('client.no_last_order_found');
            return $this->sendMessage($text, $parse_mode = 'HTML');
        }

        // $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);

        $items = $order->items->load(['product.translation']);

        // dd($items);

        $is_error = false;
        foreach ($items as $key => $item) {
            if ($item->product->status != 'active') {
                $is_error = true;
            }
            $this->user->cart->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->product->price
            ]);
        }


        $text = __('client.order_repeated_text');
        $this->sendMessage($text, $parse_mode = 'HTML');
        if ($is_error) {
            $res = $this->answerCallbackQuery(__('client.order_repeated_text_alert_error'), $is_alert = false);
            dd($res);
        } else {
            $res = $this->answerCallbackQuery('', $is_alert = false);
        }
    }
    public function deleteOrder($order_id)
    {
        $order = $this->user->orders()->find($order_id);

        // dd($order);

        if ($order == null) {
            $text = __('client.no_last_order_found');
            return $this->sendMessage($text, $parse_mode = 'HTML');
        }

        $order->delete();
        // $order->delete();
        $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
    }
}
