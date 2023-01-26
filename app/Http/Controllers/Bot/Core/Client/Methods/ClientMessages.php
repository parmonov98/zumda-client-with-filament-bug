<?php

namespace App\Http\Controllers\Bot\Core\Client\Methods;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\CommonCategory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Review;
use App\Models\User;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

trait ClientMessages
{
    public function greetUser(Request $request)
    {
        $params = $request->all();
        $options = [
            [
                [
                    'callback_data' => 'uz',
                    'text' => __('client.user_uzbek_language_button')
                ]
            ],
            [
                [
                    'callback_data' => 'ru',
                    'text' => __('client.user_russian_language_button')
                ]
            ]
        ];
        if ($this->user == null) {
            $user = $this->userService->getByTelegramID($params['message']['from']['id']);
        } else {
            $user = $this->user;
        }

        app()->setLocale($user->language);

        if ($user !== null) {
            $text = __('client.welcome_message', ['place'  => env('TELEGRAM_BOT_NAME', 'Zumda')]);
            $this->sendMessage($text, 'HTML', $markup = $this->getInlineKeyboard($options));
            return false;
        }

        $res = $this->storeUser($request);


        if ($res['status'] == 200) {

            $this->user = $res['data']; // setting newly created user

            $text = __('client.welcome_message', ['place' => env('TELEGRAM_BOT_NAME', 'Zumda')]);

            $this->sendMessage($text, 'HTML', $markup = $this->getInlineKeyboard($options));

            return true;
        }

        $text = __('client.welcome_message_back_failed', [
            'place'  => env('TELEGRAM_BOT_NAME', 'Zumda'),
            'code' => $res['code']
        ]);

        $params = [
            'chat_id' => $params['message']['from']['id'],
            'text' => htmlspecialchars($text),
            'parse_mode' => 'HTML'
        ];

        return $this->postRequest('sendMessage', $params);
    }

    public function storeUser(Request $request)
    {

        $result = ['status' => 200];

        try {
            $result['data'] = $this->userService->saveUserData($request->all());
        } catch (Exception $e) {
            $result = [
                'status' => 500,
                'error' => $e->getMessage(),
                'code' =>  $e->getCode()
            ];
        }

        return $result;
    }

    public function checkIfClientSubscribed()
    {
        $values = [
            'last_step' => null,
            'last_value' => null
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __("client.user_menu", [
            'place'  => env('TELEGRAM_BOT_NAME', 'Zumda')
        ]);
        $menu = $this->getMainUserMenu();

        return $this->sendMessage($text, 'HTML', $menu);
    }

    public function sendRestaurantsMenuForClient($text = '', $is_edit = false)
    {
        $text = __('operator.restaurants', [
            'place' => env('TELEGRAM_BOT_NAME', 'Zumda')
        ]);
        $restaurants = $this->restaurantService->getActiveRestaurants();

        $markup = $this->getActiveRestaurantsKeyboard($restaurants);

        if ($is_edit){
            return $this->editMessageText(null, $text, 'HTML', $markup);
        }else{
            $this->sendMessage($text, 'HTML', $markup);
        }

    }

    public function sendCommonCategoriesMenuForClient($text = '', $is_edit = false)
    {
        DB::enableQueryLog();
        $categories = CommonCategory::with('translation')->paginate();
        $inline = [];
        if (count($categories) > 0) {
            $j = 0;
            $k = 0;
            $categories = $categories->toArray()['data'];
            for ($i = 0; $i < count($categories); $i++) {
                $item = $categories[$i];
                if (!$item['translation'] || !$item['translation']['name']) continue;
                if (isset($inline[$j]) && count($inline[$j]) == 2) {
                    $k = 0;
                    $j++;
                    $inline[$j][$k]['text'] = $item['translation']['name'];
                    $inline[$j][$k]['callback_data'] = 'common_category/' . $item['id'];
                } else {
                    $inline[$j][$k]['text'] = $item['translation']['name'];
                    $inline[$j][$k]['callback_data'] = 'common_category/' . $item['id'];
                }
                $k++;
            }

            if (!$text){
                $text = __('client.common_category_text');
            }

            $markup = $this->getInlineKeyboard($inline);


        } else {
            $text = __('client.no_common_category_found');
            $markup = $this->getInlineKeyboard([]);
        }

        if ($is_edit){
            return $this->editMessageText(null, $text, 'HTML', $markup);
        }else{
            $this->sendMessage($text, 'HTML', $markup);
        }

    }

    public function sendCommonCategoryRestaurantsMenuForClient($common_category_id, $is_new_message = false){
        $common_category = CommonCategory::find($common_category_id);
        $common_category->load('restaurants');
        $restaurants = $common_category->restaurants;
        foreach ($restaurants as $restaurant){
            $restaurant->category = $restaurant->categories()->with('translation')->where('common_category_id', $common_category->id)->first();
        }
        if (count($restaurants) > 0) {
            $j = 0;
            $k = 0;
            $restaurants = $restaurants->pick(['id', 'name', 'category.translation as category']);
            $inline = [];
            for ($i = 0; $i < count($restaurants); $i++) {
                $item = $restaurants[$i];
                if (!isset($item['name'])) continue;
                if (isset($inline[$j]) && count($inline[$j]) == 2) {
                    $k = 0;
                    $j++;
                    $inline[$j][$k]['text'] = $item['name'];
                    $inline[$j][$k]['callback_data'] = 'restaurant/'. $item['id'] . '/' . 'category/' . $item['category']->category_id;
                } else {
                    $inline[$j][$k]['text'] = $item['name'];
                    $inline[$j][$k]['callback_data'] = 'restaurant/'. $item['id'] . '/' . 'category/' . $item['category']->category_id;
                }
                $k++;
            }

            $inline[count($inline)][0] = [
                'text' => __('client.keyboard_restaurants_back_to_restaurants'),
                'callback_data' => 'back_restaurants_menu_from_restaurants_categories'
            ];

            $text = __('client.restaurants_restaurant_page_text', [
                'name' => $common_category->translation->name
            ]);
            $markup = $this->getInlineKeyboard($inline);

            if (!$is_new_message){
                return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
            }else{
                $this->sendMessage($text, 'HTML', $markup);
            }

        } else {
            $text = __('client.restaurants_restaurant_page_text', [
                'name' => $restaurant->translation->name
            ]);

//            $restaurant_id = 0; // to get res id
//            $inline[1][0] = [
//                'text' => __('client.keyboard_restaurants_back_to_restaurants'),
//                'callback_data' => 'categories_back_to_restaurant/' . $restaurant_id
//            ];

//            $markup = $this->getInlineKeyboard($inline);

            if (!$is_new_message){
                return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', );
            }else{
                $this->sendMessage($text, 'HTML', );
            }

        }
    }

    public function sendRestaurantCategoriesMenuForClient($restaurant_id, $is_new_message = false){
        $restaurant = $this->restaurantService->find($restaurant_id);
        $categories = $this->categoryService->getAll($restaurant_id);
        if ($restaurant instanceof Restaurant){

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
                        $inline[$j][$k]['callback_data'] = 'restaurant/'. $restaurant->id . '/' . 'category/' . $item['id'];
                    } else {
                        $inline[$j][$k]['text'] = $item['translation']['name'];
                        $inline[$j][$k]['callback_data'] = 'restaurant/'. $restaurant->id . '/' . 'category/' . $item['id'];
                    }
                    $k++;
                }

                $inline[count($inline)][0] = [
                    'text' => __('client.keyboard_restaurants_back_to_restaurants'),
                    'callback_data' => 'back_restaurants_menu'
                ];

                $text = __('client.restaurants_restaurant_page_text', [
                    'name' => $restaurant->name
                ]);
                $markup = $this->getInlineKeyboard($inline);

                if (!$is_new_message){
                    return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
                }else{
                    $this->sendMessage($text, 'HTML', $markup);
                }

            }
            else {
                $text = __('client.restaurants_restaurant_page_text', [
                    'name' => $restaurant->name
                ]);

                $inline[0][0] = [
                    'text' => __('client.keyboard_restaurants_back_to_restaurants'),
                    'callback_data' => 'back_to_restaurants_from_restaurants_categories'
                ];

                $markup = $this->getInlineKeyboard($inline);

                if (!$is_new_message){
                    return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
                }else{
                    $this->sendMessage($text, 'HTML', $markup);
                }
            }
        }else{
            $this->answerCallbackQuery(__("No restaurant found"));
        }
    }

    public function sendRestaurantCategoriesMenuForClientAsKeyboard($restaurant_id){
        $restaurant = $this->restaurantService->find($restaurant_id);
        $categories = $this->categoryService->getAll($restaurant_id);
        if ($restaurant instanceof Restaurant){
            $lastSteps = [
                'last_step' => 'restaurant_selected',
                'last_value' => $restaurant_id
            ];
            $this->userService->updateUserLastStep($this->user->telegram_id, $lastSteps);
            if (count($categories) > 0) {
                $j = 0;
                $k = 0;
                $categories = $categories->toArray();
                $keyboard = [];
                for ($i = 0; $i < count($categories); $i++) {
                    $item = $categories[$i];
                    if (!$item['translation']['name']) continue;
                    if (isset($keyboard[$j]) && count($keyboard[$j]) == 2) {
                        $k = 0;
                        $j++;
                        $keyboard[$j][$k]['text'] = $item['translation']['name'];
                    } else {
                        $keyboard[$j][$k]['text'] = $item['translation']['name'];
                    }
                    $k++;
                }

                $keyboard[count($keyboard)][0] = [
                    'text' => __('client.keyboard_restaurants_back_to_restaurants'),
                ];

                $text = __('client.restaurants_restaurant_page_text', [
                    'name' => $restaurant->name
                ]);
                $markup = $this->getKeyboard($keyboard, true);

                return $this->sendMessage($text, 'HTML', $markup);
            }else {
                $text = __('client.restaurants_restaurant_page_text', [
                    'name' => $restaurant->name
                ]);

                $keyboard = [];

                $keyboard[count($keyboard)][0] = [
                    'text' => __('client.keyboard_restaurants_back_to_restaurants'),
                ];

                $markup = $this->getKeyboard($keyboard, true);
                return $this->sendMessage($text, 'HTML', $markup);

            }
        }else{
            return $this->sendMessage(__("No restaurant found"));
        }
    }

    public function sendCategoryItemsMenuForClient($restaurant_id, $category_id, $is_new_message = false)
    {
        $restaurant = $this->restaurantService->find($restaurant_id);
        $category = $this->categoryService->find($category_id);
        $products = $this->productService->getAll($category->id);
        if (count($products) > 0) {
            $j = 0;
            $k = 0;
            $products = $products->toArray();
            $inline = [];
            for ($i = 0; $i < count($products); $i++) {
                $item = $products[$i];
                if (!$item['translation']['name']) continue;
                if (isset($inline[$j]) && count($inline[$j]) == 2) {
                    $k = 0;
                    $j++;
                    $inline[$j][$k]['text'] = $item['translation']['name'];
                    $inline[$j][$k]['callback_data'] = 'restaurant/'. $restaurant->id . '/' . 'category/' . $category->id . '/product/' . $item['id'] ;
                } else {
                    $inline[$j][$k]['text'] = $item['translation']['name'];
                    $inline[$j][$k]['callback_data'] = 'restaurant/'. $restaurant->id . '/' . 'category/' . $category->id . '/product/' . $item['id'];
                }
                $k++;
            }

            $inline[count($inline)][0] = [
                'text' => __('client.keyboard_restaurants_back_to_restaurants'),
                'callback_data' => 'back_restaurants_menu'
            ];

            $text = __('client.restaurants_restaurant_category_product_page', [
                'restaurant' => $restaurant->name,
                'category' => $category->translation->name,
            ]);

            $markup = $this->getInlineKeyboard($inline);

            if (!$is_new_message){
                return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
            }else{
                $this->sendMessage($text, 'HTML', $markup);
            }

            return response("", 200);

        } else {
            $text = __('client.restaurants_restaurant_category_product_page', [
                'restaurant' => $restaurant->translation->name,
                'category' => $category->translation->name,
            ]);

            $inline[1][0] = [
                'text' => __('client.keyboard_restaurants_back_to_restaurants'),
                'callback_data' => 'categories_back_to_restaurant/' . $restaurant_id
            ];

            $markup = $this->getInlineKeyboard($inline);

            if (!$is_new_message){
                return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
            }else{
                $this->sendMessage($text, 'HTML', $markup);
            }

        }
    }

    public function sendCategoryItemsMenuForClientAsKeyboard($restaurant_id, $category_name)
    {

        $restaurant = $this->restaurantService->find($restaurant_id);
        $restaurant->load('categories.translation');
        $category = $restaurant->categories->first(fn($item) => $item->translation->name === $category_name);
        if (!$category instanceof Category){
            return $this->sendMessage(__("client.no_category_found"));
        }
        $products = $this->productService->getActiveProducts($category->id);

        if (count($products) < 1) {

            $text = __('client.restaurants_restaurant_category_product_page', [
                'restaurant' => $restaurant->translation->name,
                'category' => $category->translation->name,
            ]);

            $keyboard[0][0] = [
                'text' => __('client.keyboard_restaurants_back_to_restaurants_categories'),
            ];
            $markup = $this->getInlineKeyboard($keyboard);
            return $this->sendMessage($text, 'HTML', $markup);
        } else {
            $lastSteps = [
                'last_step' => 'restaurant_category_selected',
                'last_value' => $category->id
            ];
            $this->userService->updateUserLastStep($this->user->telegram_id, $lastSteps);
            $products = $products->pick(['id', 'translation.name as name']);

            $j = 0;
            $k = 0;
            $products = $products->toArray();
            $keyboard = [];
            for ($i = 0; $i < count($products); $i++) {
                $item = $products[$i];
                if (!$item['name']) continue;
                if (isset($keyboard[$j]) && count($keyboard[$j]) == 2) {
                    $k = 0;
                    $j++;
                    $keyboard[$j][$k]['text'] = $item['name'];
                } else {
                    $keyboard[$j][$k]['text'] = $item['name'];
                }
                $k++;
            }

            $keyboard[count($keyboard)][0] = [
                'text' => __('client.keyboard_restaurants_back_to_restaurants_categories'),
            ];

            $text = __('client.restaurants_restaurant_category_product_page', [
                'restaurant' => $restaurant->name,
                'category' => $category->translation->name,
            ]);

            $markup = $this->getKeyboard($keyboard, true);
            return $this->sendMessage($text, 'HTML', $markup);
        }
    }

    public function processProductByClient($product_id)
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
            $caption .= __('client.product_price_in_caption', [
                'price' => number_format($product->price, 0, '.', ',')
            ]);

//            $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);

            $menu = $this->getProductQuantityMenu($product->id);

            $res = $this->sendPhoto($this->user->telegram_id, $product->photo_id, $caption, $menu);
//            dd($res);
            if (!$res) {
                $this->sendMessage($caption, 'HTML', $menu);
            }
            return $this->answerCallbackQuery(__('client.back_to_product_view'), false);

        } else {
            return $this->sendMessage(__("client.something_went_wrong"));
        }
    }
    public function viewProductByClient($product_id)
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
            $caption .= __('client.product_price_in_caption', [
                'price' => number_format($product->price, 0, '.', ',')
            ]);

            $menu = $this->getProductQuantityMenu($product->id);

            $res = $this->sendPhoto($this->user->telegram_id, $product->photo_id, $caption, $menu);
//            dd($res);
            if (!$res) {
                $this->sendMessage($caption, 'HTML', $menu);
            }
        } else {
            return $this->sendMessage(__("client.something_went_wrong"));
        }
    }

    public function addToCartByClient($tokens)
    {

        $product = $this->productService->find($tokens[1]);

        $restaurant = $product->category->restaurant;
        $restaurant_id = $product->category->restaurant_id;
        $quantity = $tokens[2];
        $cartItems = $this->user->cart->items()->get();
        if ($cartItems->count() > 0){
            $randomItem = $cartItems->random();
            if ($randomItem?->product?->restaurant?->id != $restaurant?->id){
                $text = __("You cannot order from multiple restaurants at a time");
                $this->answerCallbackQuery($text, $parse_mode = 'HTML');
                return false;
            }
        }
        $params = [
            'product_id' => $product->id,
            'cart_id' => $this->user->cart->id
        ];

        $values = [
            'price' => $product->price,
            'product_id' => $product->id,
            'quantity' => DB::raw('quantity + ' . $quantity)
        ];
        // dd($params, $values);
        $cartItem = CartItem::updateOrCreate(
            $params,
            $values
        );

        if ($cartItem instanceof CartItem) {

            $text = __('operator.cart_item_added_to_cart');

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
            $this->sendMessage($text, 'HTML', $markup);

            $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
        }
    }

    public function sendStoreContact($contact = null)
    {

        if ($contact) {
            $phone_number = $this->message['contact']['phone_number'];
            $res = $this->userService->updateContact($this->user->telegram_id, $phone_number);
            // dd($res);
            if ($res instanceof User) {
                // $this->clearUserSteps();

                $menu = $this->getMainMenu();
            } else {
                return $this->sendMessage(__("client.something_went_wrong"));
            }
        } else {

            $phone_number = str_ireplace(' ', '', $this->text);
            if (preg_match("/^([0-9]+){9}/", $phone_number)) {
                $res = $this->userService->updateContact($this->message['from']['id'], $phone_number);
                if ($res instanceof User) {
                    // $this->clearUserSteps();
                    $menu = $this->getMainMenu();
                } else {
                    // dd(1);
                    return $this->sendMessage(__("client.something_went_wrong"));
                }
            } else {
                return $this->sendMessage(__("client.invalid_phone_number"));
            }
        }


        $values = [
            'last_step' => 'signup_phone_number',
            'last_value' => null
        ];

        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __('client.signup_user_name');

        return $this->sendMessage($text, 'HTML');
    }

    public function sendStoreUserName($name)
    {

        $text = __("client.user_signed_up_successfully", [
            'place'  => env('TELEGRAM_BOT_NAME', 'Zumdan')
        ]);

        $res = $this->userService->updateName($this->message['from']['id'], $name);
        if ($res instanceof User) {
            // $this->clearUserSteps();
            $menu = $this->getMainUserMenu();
        } else {
            // dd(1);
            return $this->sendMessage(__("client.something_went_wrong"));
        }

        $values = [
            'last_step' => null,
            'last_value' => null
        ];

        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        return $this->sendMessage($text, 'HTML', $menu);
    }

    public function clearUserSteps()
    {
        $values = [
            'last_step' => null,
            'last_value' => null
        ];

        $this->userService->updateUserLastStep($this->user->telegram_id, $values);
    }

    public function getMainUserMenu()
    {
        $options = [
            [
                ['text' => __('client.keyboard_menu')],
            ],
            [
                ['text' => __('client.keyboard_orders')],
                ['text' => __('client.keyboard_settings')]
            ],
            [
                ['text' => __('client.keyboard_contact')],
                ['text' => __('client.keyboard_cashback')],
            ],
//            [
//                ['text' => __('client.keyboard_cart')],
//            ],
//            [
//                ['text' => __('client.keyboard_search')],
//            ]
        ];
        return $this->getKeyboard($options, $resize = true);
    }

    public function getMainUserMenuOptions()
    {
        $options = [
            [
                ['text' => __('client.keyboard_meals')],
                ['text' => __('client.keyboard_restaurants')],
            ],
            [
                ['text' => __('client.back_to_main_menu_as_back_button')],
            ]
        ];
        return $this->getKeyboard($options, $resize = true);
    }

    public function sendMainUserMenu()
    {
        $values = [
            'last_step' => null,
            'last_value' => null
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __("client.user_menu", [
            'place'  => env('TELEGRAM_BOT_NAME', 'Zumda')
        ]);
        $menu = $this->getMainUserMenu();

        return $this->sendMessage($text, 'HTML', $menu);
    }

    public function sendMainUserMenuOptions()
    {
        $values = [
            'last_step' => null,
            'last_value' => null
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __("client.user_menu", [
            'place'  => env('TELEGRAM_BOT_NAME', 'Zumda')
        ]);
        $menu = $this->getMainUserMenuOptions();

        return $this->sendMessage($text, 'HTML', $menu);
    }

    public function sendCartItemsByClient($is_inline = false, $restaurant_id = null)
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

    public function clearUserCartByClient()
    {
        $this->user->cart->items()->delete();
        $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
        $this->sendRestaurantsMenuForClient();
        $this->answerCallbackQuery(__('operator.cart_cleared'));
    }

    public function backToMainCategoryMenuByClient($restaurant_id)
    {

        $restaurant = $this->restaurantService->find($restaurant_id);
        if (!$restaurant instanceof Restaurant){
            $this->sendRestaurantsMenuForClient(is_edit:  true);
            $this->answerCallbackQuery();
            return false;
        }
        $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
        $categories = $this->categoryService->getActiveCategories($restaurant_id, $level = 1);
        // dd($categories);

        $menu = $this->getOrderMenu($level = 1, $categories);
        $text = __('client.selected_restaurant_main_categories', [
            'restaurant' => $restaurant->name,
            'menu_link' => env('BOT_MENU_LINK')
        ]);

        $this->sendMessage($text, 'HTML', $menu);
        $this->answerCallbackQuery();
    }

    public function storeAndSendOrderToAdmins()
    {
        $order = $this->user->orders()->find($this->user->last_value);

        $order->status = 'paid';

        $order->save();
        $order->refresh();

        $text = __('client.order_payment_payment_made_successfully', [
            'order_id'  => $order->id
        ]);
        $this->sendMessage($text, 'HTML', $markup = $this->getMainMenu());


        $admins = $this->userService->getAllEmployees();

        $order->items->load('product.translation');
        $items = $order->items->load('product.translation');

        $text = $this->getOrderText($order);

        $keyboard = $this->getOrderInlineMenu($order);

        $admins->each(function ($item) use ($text, $keyboard) {

            $data = [
                'chat_id' => $item->telegram_id,
                'text' => htmlspecialchars($text),
                'reply_markup' => $keyboard
            ];

            $res = $this->postRequest('sendMessage', $data);
            // dd($item);

        });

        dd($order);
    }

    public function sendOrderMenu()
    {

        $categories = $this->categoryService->getActiveCategories($is_first_level = true);
        // dd($categories);
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

            // dd($inline);
            $text = __('client.main_categories', [
                'menu_link' => env('BOT_MENU_LINK')
            ]);
            $markup = $this->getInlineKeyboard($inline);

            return $this->sendMessage($text, 'HTML', $markup);
        } else {
            $text = __('client.no_category_found');
            // $markup = $this->getInlineKeyboard($inline);

            return $this->sendMessage($text, 'HTML', []);
        }
    }

    public function getOrderMenu($level = 1, mixed $categories = [])
    {

        if ($level == 1) {
            $command = 'category/';
        } else {
            $command = 'subcategory/';
        }
        $row = intval(ceil(count($categories) / 2));

        $j = 0;
        $k = 0;

        if (!is_array($categories))
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
        if ($level != 1) {
            $last_index = count($inline);
            $inline[$last_index][0]['text'] = __('client.back_to_main_category_menu');
            $inline[$last_index][0]['callback_data'] = 'back_to_main_category_menu';
        }

        // dd($inline);
        $text = __('client.main_categories', [
            'menu_link' => env('BOT_MENU_LINK')
        ]);
        return $markup = $this->getInlineKeyboard($inline);
    }

    public function getProductsMenu($products)
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
        $inline[$last_index][0]['text'] = __('client.back_to_main_category_menu');
        $inline[$last_index][0]['callback_data'] = 'back_restaurants_menu';

        // dd($inline);
        $text = __('client.main_categories', [
            'menu_link' => env('BOT_MENU_LINK')
        ]);
        return $markup = $this->getInlineKeyboard($inline);
    }

    public function sendUserOrders()
    {

        $orders = $this->user->orders()->with(['items.product.translation'])->where('status', 'completed')->get();

        // dd($orders);

        $orderRow = [];
        $text = '';

        if ($orders->count() == 0) {
            $text = __('client.no_orders_found');
            $markup = $this->getInlineKeyboard($orderRow);
            $this->sendMessage($text, 'HTML', $markup);
            return false;
        }
        foreach ($orders as $key => $item) {
            // dd($key);
            $orderRow[0][0]['text'] = 'Takrorlash';
            $orderRow[0][0]['callback_data'] = 'repeat/' . $item->id;
            $orderRow[0][1]['text'] = 'Yopish';
            $orderRow[0][1]['callback_data'] = 'deleteOrder/' . $item->id;

            $itemsText = '';
            $items = $item->items;
            foreach ($items as $subkey => $subitem) {
                $itemsText .=  $subitem->quantity . ' x ' . $subitem->product->translation->name . "\n";
            }
            $orderDetails = '';
            $orderDetails .= __('client.order_items_list', [
                'items' => $itemsText,
                'created_at' => $item->created_at
            ]);
            $markup = $this->getInlineKeyboard($orderRow);
            $this->sendMessage($orderDetails, 'HTML', $markup);
        }
    }

    public function sendUserSettings()
    {
        $inline = [];

        $inline[0][0]['text'] = __('client.user_language_button');
        $inline[0][0]['callback_data'] = 'lang';
        $inline[1][0]['text'] = __('client.user_phone_number_button');
        $inline[1][0]['callback_data'] = 'phone';
        $inline[2][0]['text'] = __('client.user_name_button');
        $inline[2][0]['callback_data'] = 'name';
        $inline[3][0]['text'] = __('client.main_menu_button');
        $inline[3][0]['callback_data'] = 'main';

        if ($this->user->language == 'uz') {
            $language = __('client.user_uzbek_language');
        } else {
            $language = __('client.user_russian_language');
        }
        $text = __('client.user_settings_text', [
            'name' => $this->user->name,
            'phone' => $this->user->phone_number,
            'language' => $language
        ]);

        return $this->sendMessage($text, 'HTML', $this->getInlineKeyboard($inline));
    }

    public function sendCustomerReview()
    {


        $values = [
            'last_step' => 'customer_review',
            'last_value' => null
        ];

        $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);


        $text = __('client.customer_review');

        $options = [
            [
                [
                    'text' => __('client.back_to_main_menu'),
                ]
            ]
        ];

        return $this->sendMessage($text, 'HTML', $this->getKeyboard($options, $resize = true));
    }

    public function storeCustomerReview($review)
    {
        $text = __('client.customer_review_accepted');

        // dd($this->user->reviews, $review);
        $entities = [];
        if (isset($this->message['entities'])) {
            $entities = $this->message['entities'];
        }
        $review = $this->user->reviews()->create([
            'text' => $review,
            'entities' => json_encode($entities)
        ]);

        // dd($review);

        // $this->user->first_name = $name;
        if ($review instanceof Review) {

            $admins = $this->userService->getAdministrators();

            $admins = $admins->reverse();

            $admins->each(function ($item) use ($review) {

                // dd($this->user->phone_number);

                $text = __('client.user_review', [
                    'user' => $item->first_name,
                    'phone' => $this->user->phone_number,
                    'text' => $review->text,
                    'tg_id' => $this->user->telegram_id
                ]);

                $this->sendMessage2($item->telegram_id, $text, 'HTML', );

            });

            // dd($admins);

            $values = [
                'last_step' => null,
                'last_value' => null
            ];
            $this->userService->updateUserLastStep($this->user->telegram_id, $values);
            $menu = $this->getMainUserMenu();
            return $this->sendMessage($text, 'HTML', $menu);
        } else {
            return $this->sendMessage(__("client.something_went_wrong"));
        }
    }

    public function sendUserContacts()
    {
        $admins = $this->userService->getAdministrators();
        $admins_text = env('TELEGRAM_ADMIN_USERNAME', '@Zumda_admin');
        if ($admins->count() > 0){
//            $admins_text .=

            $admin_arr = [];
            $admins_text = '';
            foreach ($admins as $item){
                $data = $this->getRequest('getChatMember', [
                    'chat_id' => $item->telegram_id,
                    'user_id' => $item->telegram_id
                ]);
                $admin_arr[] = $data;
                if (isset($data['user']) && isset($data['user']['username'])){

                    $admins_text .= "\n@" . $data['user']['username'] ?? 'no username' . "\n";
                    $item->username = $data['user']['username'] ?? '';
                    $item->save();
                }
            }
        }
        $text = __('client.contacts_text', [
            'bot_name' => env('TELEGRAM_BOT_NAME'),
            'office_address' => env('TELEGRAM_OFFICE_ADDRESS'),
            'phone' => env('TELEGRAM_PHONE'),
            'admin_username' => $admins_text,
        ]);

        $menu = $this->getMainUserMenu();
        return $this->sendMessage($text, 'HTML', $menu);
    }

    public function storeOrderLocationByClient()
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

    public function storeOrderAddressByClient($address)
    {

        // dd($address);

        $gmaps = new \yidas\googleMaps\Client(['key' => env('GOOGLE_API_KEY', 'AIzaSyCC1KHaC4OoUjXubpLSjvnI4ve9nE_YIiI')]);
        $lang = App::getLocale();
        $lang = $lang . '-' . strtoupper($lang);
        // dd($lang);
        $gmaps->setLanguage($lang);

        $distanceMatrixResult = $gmaps->distanceMatrix(env('ORIGIN_POINT_LOCATION', '41.25511,69.31867'),  $address);
        // dd($distanceMatrixResult);


        if ($distanceMatrixResult['status'] !== 'OK' || $distanceMatrixResult['rows'][0]['elements'][0]['status'] !== 'OK') {

            $text = __('client.location_not_determined_by_google_api');

            // dd($text);

            return $this->sendMessage($text, $parse_mode = 'HTML');
            // dd($distanceMatrixResult['rows'][0]['elements'][0]['distance']['value']);
        }

        $order = $this->user->orders()->find($this->user->last_value);

        if ($order == null) {
            $text = __('client.no_last_order_found');
            return $this->sendMessage($text, $parse_mode = 'HTML');
        }

        $values = [
            'last_step' => 'order_location_or_address',
            'last_value' => $order->id
        ];
        $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        // dd($res);


        $address = $distanceMatrixResult['destination_addresses'][0];
        $distance = round($distanceMatrixResult['rows'][0]['elements'][0]['distance']['value']);
        // dd($meters);
        $fixed_delivery_km = env('FIXED_DELIVERY_DISTANCE', 5);


        $delivery_price = env('FIXED_DELIVERY_PRICE', 1000);
        if ($distance > $fixed_delivery_km) {
            $delivery_price = (env('PRICE_DELIVERY_PER_KM', 1000) * ($distance - $fixed_delivery_km) + env('FIXED_DELIVERY_PRICE', 1000));
        }
        $order->shipping_price =  $delivery_price;
        $order->per_km_price =  env('PRICE_DELIVERY_PER_KM', 1000);
        $order->distance =  $distance;
        $order->address =  $address;
        $order->save();
        $order->refresh();

        $text = __('client.received_address_title_vs_distance_and_delivery_price', [
            'destination' => $address,
            'distance' => $distance,
            'delivery_price' => $delivery_price
        ]);

        // dd($order);
        // dd($text);

        $options = [
            [
                [
                    'text' => __('client.confirm_the_received_address'),
                    'callback_data' => 'confirm_the_received_address'
                ],
                [
                    'text' => __('client.disconfirm_the_received_address'),
                    'callback_data' => 'disconfirm_the_received_address'
                ]
            ]
        ];
        $markup = $this->getInlineKeyboard($options);
        // dd($markup);
        $res = $this->sendMessage($text, 'HTML', $markup);


        die;
    }

    public function backToOrderOrLocation()
    {
        $this->confirmOrder($is_inline = false);
    }

    public function backToOrderPhoneNumber()
    {

        $order = $this->user->orders()->find($this->user->last_value);

        // dd($order);

        if ($order == null) {
            $text = __('client.no_last_order_found');
            return $this->sendMessage($text, $parse_mode = 'HTML');
        }

        $values = [
            'last_step' => 'confirm_order_location_or_address',
            'last_value' => $order->id
        ];
        $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);

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
        // dd($summary);
    }

    public function backToOrderCustomerNote()
    {

        $order = $this->user->orders()->find($this->user->last_value);

        // dd($order);

        if ($order == null) {
            $text = __('client.no_last_order_found');
            return $this->sendMessage($text, $parse_mode = 'HTML');
        }

        $values = [
            'last_step' => 'order_phone_number',
            'last_value' => $order->id
        ];
        $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __("client.order_customer_note_text");
        $options = [
            [
                [
                    'text' => __('client.order_customer_note_button')
                ]
            ],
            [
                [
                    'text' => __('client.order_back_to_previous_step')
                ]
            ]
        ];


        $this->sendMessage($text, 'HTML', $markup = $this->getKeyboard($options, $resize = true));
        // dd($summary);
    }

    public function storeOrderContact($contact = null)
    {

        $order = $this->user->orders()->find($this->user->last_value);

        // dd($order);

        if ($order == null) {
            $text = __('client.no_last_order_found');
            return $this->sendMessage($text, $parse_mode = 'HTML');
        }

        $values = [
            'last_step' => 'order_phone_number',
            'last_value' => $order->id
        ];
        $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);


        $text = __("client.order_customer_note_text");
        $options = [
            [
                [
                    'text' => __('client.order_customer_note_button')
                ]
            ],
            [
                [
                    'text' => __('client.order_back_to_previous_step')
                ]
            ]
        ];

        if ($contact) {
            $phone_number = $this->message['contact']['phone_number'];
            $order->phone_number = $phone_number;

            // dd($res);
            if ($order->save()) {
                $menu = $this->getKeyboard($options, $resize = true);
                return $this->sendMessage($text, 'HTML', $menu);
            } else {
                return $this->sendMessage(__("client.something_went_wrong"));
            }
            die;
        }


        $phone_number = str_ireplace(' ', '', $this->text);

        // dd($phone_number);

        if (preg_match("/^([0-9]+){9}/", $phone_number)) {
            // $res = $this->userService->updateContact($this->message['from']['id'], $phone_number);
            $order->phone_number = $phone_number;
            if ($order->save()) {
                $menu = $this->getKeyboard($options, $resize = true);
                $this->sendMessage($text, 'HTML', $menu);
            } else {
                // dd(1);
                return $this->sendMessage(__("client.something_went_wrong"));
            }
        } else {
            return $this->sendMessage(__("client.invalid_phone_number"));
        }
    }

    public function acceptCustomerNote()
    {

        // dd($this->text, $this->message);
        if ($this->text == __('client.order_customer_note_button')) {
            $customer_note = '-';
        }

        $customer_note = $this->text;

        $order = $this->user->orders()->find($this->user->last_value);

        if ($order == null) {
            $text = __('client.no_last_order_found');
            return $this->sendMessage($text, $parse_mode = 'HTML');
        }

        $values = [
            'last_step' => 'order_customer_note',
            'last_value' => $order->id
        ];

        $res = $this->userService->updateUserLastStep($this->user->telegram_id, $values);


        $text = '';

        $options = [
            [
                [
                    'text' => __('client.order_payment_method_payme'),
                    'callback_data' => 'order_payment_method_payme'
                ],
                [
                    'text' => __('client.order_payment_method_click'),
                    'callback_data' => 'order_payment_method_click'
                ]
            ],
            [
                [
                    'text' => __('client.order_payment_method_cash'),
                    'callback_data' => 'order_payment_method_cash'
                ]
            ]
        ];


        $order->items->load('product.translation');
        $items = $order->items->load('product.translation');
        // dd($order->items);
        $summary = 0;
        $itemsText = '';

        $prices = [];
        foreach ($items as $key => $item) {
            $itemSummary = ($item->quantity * $item->price);
            $summary += $itemSummary;
            if ($item->product) {
                $itemsText .= $item->quantity . ' x ' . $item->product->translation->name . ' = ' . $itemSummary . __('client.item_currency_lang');
                $prices[$key]['amount'] = $itemSummary;
                $prices[$key]['label'] = $item->product->translation->name;
            } else {
                continue;
            }
        }

        $shipping_price = $order->shipping_price;
        $text .= $itemsText;
        $text .= __('client.order_shipping_price', [
            'shipping_price' => number_format($shipping_price, 0, '.', ','),
            'shipping_address' => $order->address
        ]);
        $text .= __('client.order_customer_note_text_in_message', [
            'message' => $customer_note
        ]);
        $text .= __('client.order_summary', [
            'summary' => number_format(($summary + $shipping_price), 0, '.', ',')
        ]);
        // $text .= __('client.order_information');
        $textInvoice = __('client.order_payments');

        // dd($text);

        $last_index = count($prices);
        $prices[$last_index]['label'] = __('client.order_delivery_text');
        $prices[$last_index]['amount'] = $shipping_price;
        // dd($text);
        // dd($order->items);

        $order->customer_note = $customer_note;
        $order->summary = $summary;


        // dd($prices);
        // dd($customer_note);
        if ($order->save()) {

            $menu = $this->getInlineKeyboard($options);
            // dd($menu);
            $res = $this->sendMessage($text, 'HTML', $menu);

            if (isset($res['message_id'])) {


                $text = __('client.choose_one_of_the_payment_or_back_to_customer_note');

                $options = [
                    [
                        [
                            'text' => __('client.order_back_to_previous_step')
                        ]
                    ]
                ];

                $res = $this->sendMessage($text, 'HTML',  $menu = $this->getKeyboard($options, true));



                // dd($res);
            }
            // dd($res);

            // dd($res);
        } else {
            // dd(1);
            return $this->sendMessage(__("client.something_went_wrong"));
        }
    }

    public function cancelEditingUserSettings()
    {
        $values = [
            'last_step' => null,
            'last_value' => null
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);
        return $this->deleteMessage($this->message['from']['id'], $this->message['message_id'] - 1);
    }
}
