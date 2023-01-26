<?php
namespace App\Http\Controllers\Bot\Core\SharedMethods;

use App\Models\Category;
use App\Models\Product;

trait ProductMethods {


    public function sendProductViewMessage($action, $is_from_product = true){

        $product = $this->productService->find($action);

        if (!$product instanceof Product){
            $text = __('admin.products_error_add_category');
            return $this->answerCallbackQuery($text, true);
        }


        $text = $this->getEditProductText($product);

        $markup = $this->getProductViewKeyboard($product);

        $this->answerCallbackQuery();

        if ($is_from_product){
            $res = $this->editMessageCaption($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
            if ($res === false){
                return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
            }
        }else{
            $res = $this->sendPhoto($this->callback_query['from']['id'], $product->photo_id, $text, $markup);
            if ($res === false){
                return $this->sendMessage($text, 'HTML', $markup);
            }
        }
    }

    public function sendAddProductMessage($action){

        $data = [
            'category_id' => $action,
            'user_id' => $this->user->id,
            'lang' => 'uz',
        ];

        $product = $this->productService->addProduct($data);

        if (!$product instanceof Product){
            $text = __('admin.products_error_add_category');
            return $this->answerCallbackQuery($text, true);
        }

        $values = [
            'last_step' => 'add_new_product_name',
            'last_value' => $product->id // product_id
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);
//
        $keyboard[0][0]['text'] = __('admin.products_cancel_creating');
        $markup = $this->getKeyboard($keyboard, true);

        $text = __('admin.products_enter_a_name_for_new_product');
        $this->sendMessage($text, 'HTML', $markup);
    }

    public function cancelCreatingNewProduct(){

        $this->productService->removeProduct($this->user->last_value);

        $values = [
            'last_step' => null,
            'last_value' => null,
            'last_message_id' => null,
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __('admin.products_cancelled_creating_new_product');

        $this->sendMessage($text, 'HTML', $this->getMainAdminMenuKeyboard());

    }

    public function getEditProductKeyboard($product, $is_from_product = true){

        $product_callback_command =  'product/' . $product->id;

        if ($is_from_product){
            $product_callback_command = 'product_from_edit/' . $product->id;
        }
        $options = [
            [
                [
                    'text' => __('admin.products_edit_product_name_button'),
                    'callback_data' => 'edit_product_name/' . $product->id
                ],
                [
                    'text' => __('admin.products_edit_product_price_button'),
                    'callback_data' => 'edit_product_price/' . $product->id
                ]
            ],
            [
                [
                    'text' => __('admin.products_edit_product_photo_button'),
                    'callback_data' => 'edit_product_photo/' . $product->id
                ],
                [
                    'text' => __('admin.products_edit_product_description_button'),
                    'callback_data' => 'edit_product_description/' . $product->id
                ]
            ],
            [
                [
                    'text' => __('admin.products_edit_product_profit_button'),
                    'callback_data' => 'edit_product_percentage/' . $product->id
                ],
                [
                    'text' => __('admin.products_edit_product_dishes_button'),
                    'callback_data' => 'edit_product_dishes/' . $product->id
                ]
            ],
            [
                [
                    'text' => __('admin.products_delete_product_button'),
                    'callback_data' => 'delete_product/' . $product->id
                ]
            ],
            [
                [
                    'text' => __('admin.products_back_to_product_view'),
                    'callback_data' => $product_callback_command
                ]
            ]
        ];

        return $this->getInlineKeyboard($options);
    }

    public function getProductViewKeyboard($product){

        if ($product->status == 'active'){
            $statusButton = [
                'text' => __('admin.product_on'),
                'callback_data' => 'product_off/' . $product->id
            ];
        }else{
            $statusButton = [
                'text' => __('admin.product_off'),
                'callback_data' => 'product_on/' . $product->id
            ];
        }
        $options = [
            [
                [
                    'text' => __('admin.products_edit_product'),
                    'callback_data' => 'edit_product/' . $product->id
                ]
            ],
            [
                $statusButton
            ],
            [
                [
                    'text' => __('admin.products_back_to_restaurant_categories'),
                    'callback_data' => 'categories_go_to_products_from_product/' . $product->category_id
                ]
            ]
        ];

        return $this->getInlineKeyboard($options);
    }

    public function getEditProductText($product){

        $text = __('admin.products_product_view', [
            'restaurant' => $product->category->restaurant->name,
            'category' => $product->category->translation->name,
            'name' => $product->translation->name,
            'description' => $product->translation->description,
            'price' => number_format($product->price , 0, '.', ' '),
            'profit' => $product->profit_in_percentage . '%',
        ]);
        return $text;
    }


    public function toggleProductStatus($action){

        $product = $this->productService->find($action);
        if ($product->status == 'active'){
            $product->status = 'inactive';
            $product->save();
            $product->refresh();
        }else{
            $product->status = 'active';
            $product->save();
            $product->refresh();
        }

        $markup = $this->getProductViewKeyboard($product);
        $text = $this->getEditProductText($product);

        $this->editMessageCaption($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
    }


    public function sendDeleteProductByAdmin($action){
        $values = [
            'last_step' => null,
            'last_value' => null,
            'last_message_id' => null
        ];
        $product = $this->productService->find($action);
        $this->productService->removeProduct($action);

        $this->deleteMessage( $this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
        $this->sendCategoryProductsFromProductViewMenuForAdmin($product->category_id, true);;
//        $this->sendRestaurantCategoriesMenuForAdmin($product->restaurant_id, true);
    }


    public function sendProductsMenuForAdmin($action, $is_new_message = false){
        $product = $this->productService->find($action);
        $products = $this->productService->getAll($action);

        if (count($products) > 0) {
            $row = intval(ceil(count($products) / 2));

            $j = 0;
            $k = 0;
            $categories = $products->toArray();
            $inline = [];
            for ($i = 0; $i < count($categories); $i++) {
                $item = $categories[$i];
                if (!$item['translation']['name']) continue;
                if (isset($inline[$j]) && count($inline[$j]) == 2) {
                    $k = 0;
                    $j++;
                    $inline[$j][$k]['text'] = $item['translation']['name'];
                    $inline[$j][$k]['callback_data'] = 'product/' . $item['id'];
                } else {
                    $inline[$j][$k]['text'] = $item['translation']['name'];
                    $inline[$j][$k]['callback_data'] = 'product/' . $item['id'];
                }
                $k++;
            }

            $inline[count($inline)][0] = [
                'text' => __('admin.products_add_new_product'),
                'callback_data' => 'add_new_product/' . $action
            ];

            $inline[count($inline)][0] = [
                'text' => __('admin.products_back_to_category'),
                'callback_data' => 'category/' . $product->id
            ];

            $text = __('admin.product_page', [
                'restaurant' => $product->restaurant->translation->name,
                'category' => $product->translation->name,
            ]);
            $markup = $this->getInlineKeyboard($inline);

            if (!$is_new_message){
                return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
            }else{
                $this->sendMessage($text, 'HTML', $markup);
            }

        } else {
            $text = __('admin.restaurant_page', [
                'name' => $product->translation->name
            ]);


            $inline[0][0] = [
                'text' => __('admin.products_add_new_product'),
                'callback_data' => 'add_new_product/' . $action
            ];

            $inline[1][0] = [
                'text' => __('admin.products_back_to_category'),
                'callback_data' => 'category/' . $action
            ];

            $markup = $this->getInlineKeyboard($inline);

            if (!$is_new_message){
                return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
            }else{
                $this->sendMessage($text, 'HTML', $markup);
            }

        }
    }


    public function sendEditProductMenuForAdmin($action){

        $product = $this->productService->find($action);

        $markup = $this->getEditProductKeyboard($product);
        $text = $this->getEditProductText($product);

        $res = $this->editMessageCaption($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
        if ($res === false){
            return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
        }
    }


    public function cancelEditingProduct(){

        $values = [
            'last_step' => null,
            'last_value' => null,
            'last_message_id' => null,
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __('admin.restaurants_cancelled_editing_product');

        $this->sendMessage($text, 'HTML', $this->getMainAdminMenuKeyboard());

    }

    public function sendEditProductNameMessageForAdmin($action){
        $values = [
            'last_step' => 'edit_product_name',
            'last_value' => $action,
            'last_message_id' => $this->callback_query['message']['message_id']
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __('admin.products_enter_a_name_for_edit_product');

        $keyboard[0][0]['text'] = __('admin.products_cancel_editing');
        $markup = $this->getKeyboard($keyboard, true);
        $this->sendMessage($text, 'HTML', $markup);
    }


    public function sendEditProductPriceMessageForAdmin($action){
        $values = [
            'last_step' => 'edit_product_price',
            'last_value' => $action,
            'last_message_id' => $this->callback_query['message']['message_id']
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __('admin.products_enter_price_for_edit_product');

        $keyboard[0][0]['text'] = __('admin.products_cancel_editing');
        $markup = $this->getKeyboard($keyboard, true);
        $this->sendMessage($text, 'HTML', $markup);
    }

    public function sendEditProductPercentageMessageForAdmin($action){
        $values = [
            'last_step' => 'edit_product_percentage',
            'last_value' => $action,
            'last_message_id' => $this->callback_query['message']['message_id']
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $this->answerCallbackQuery();

        $text = __('admin.products_enter_percentage_for_edit_product');

        $keyboard[0][0]['text'] = __('admin.products_cancel_editing');
        $markup = $this->getKeyboard($keyboard, true);

        return $this->sendMessage($text, 'HTML', $markup);

    }

    public function sendEditProductPhotoMessageForAdmin($action){
        $values = [
            'last_step' => 'edit_product_photo',
            'last_value' => $action,
            'last_message_id' => $this->callback_query['message']['message_id']
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __('admin.products_enter_photo_for_edit_product');

        $keyboard[0][0]['text'] = __('admin.products_cancel_editing');
        $markup = $this->getKeyboard($keyboard, true);
        $this->sendMessage($text, 'HTML', $markup);
    }


    public function sendEditProductDescriptionMessageForAdmin($action){
        $values = [
            'last_step' => 'edit_product_description',
            'last_value' => $action,
            'last_message_id' => $this->callback_query['message']['message_id']
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __('admin.products_enter_description_for_edit_product');

        $keyboard[0][0]['text'] = __('admin.products_cancel_editing');
        $markup = $this->getKeyboard($keyboard, true);
        $this->sendMessage($text, 'HTML', $markup);
    }


    public function sendStoreEditProductNameMessage($action){
        if ($action == __('admin.products_cancel_editing')){
            $this->cancelEditingProduct();
        }else{
            $data = [
                'name' => $action,
            ];
//            dd($data);
            $product = $this->productService->EditProductName($this->user->last_value, $data);
            if(!$product instanceof Product){
                $text = __('admin.products_edit_product_name_error');
                return $this->sendMessage($text, 'HTML');
            }
            $markup = $this->getEditProductKeyboard($product);
            $text = $this->getEditProductText($product);

            $this->editMessageCaption($this->user->last_message_id, $text, 'HTML', $markup);
            $values = [
                'last_step' => null,
                'last_value' => null,
                'last_message_id' => null
            ];


            $this->userService->updateUserLastStep($this->user->telegram_id, $values);

            $this->deleteMessage($this->message['from']['id'], $this->message['message_id']);
            $this->deleteMessage($this->message['from']['id'], $this->message['message_id'] - 1);

            $text = __('admin.products_edit_product_updated_successfully');

            $markup = $this->getMainAdminMenuKeyboard();
            $this->sendMessage($text, 'HTML', $markup, $this->user->last_message_id);
        }

    }
    public function sendStoreEditProductPriceMessage($action){
        if ($action == __('admin.products_cancel_editing')){
            $this->cancelEditingProduct();
        }else{
            $data = [
                'price' => intval($action),
            ];
//            dd($data);
            $product = $this->productService->editProductPrice($this->user->last_value, $data);
            if(!$product instanceof Product){
                $text = __('admin.products_edit_product_name_error');
                return $this->sendMessage($text, 'HTML');
            }

            $markup = $this->getEditProductKeyboard($product);
            $text = $this->getEditProductText($product);
            $this->editMessageCaption($this->user->last_message_id, $text, 'HTML', $markup);

            $this->userService->resetSteps($this->user->telegram_id);

            $this->deleteMessage($this->message['from']['id'], $this->message['message_id']);
            $this->deleteMessage($this->message['from']['id'], $this->message['message_id'] - 1);

            $text = __('admin.products_edit_product_updated_successfully');
            $markup = $this->getMainAdminMenuKeyboard();
            $this->sendMessage($text, 'HTML', $markup, $this->user->last_message_id);
        }

    }
    public function sendStoreEditProductPercentageMessage($action){
        if ($action == __('admin.products_cancel_editing')){
            $this->cancelEditingProduct();
        }else{
            $data = [
                'profit_in_percentage' => intval($action),
            ];
            $product = $this->productService->editProductPrice($this->user->last_value, $data);
            if(!$product instanceof Product){
                $text = __('admin.products_edit_product_name_error');
                return $this->sendMessage($text, 'HTML');
            }

            $markup = $this->getEditProductKeyboard($product);
            $text = $this->getEditProductText($product);
            $this->editMessageCaption($this->user->last_message_id, $text, 'HTML', $markup);

            $this->userService->resetSteps($this->user->telegram_id);

            $this->deleteMessage($this->message['from']['id'], $this->message['message_id']);
            $this->deleteMessage($this->message['from']['id'], $this->message['message_id'] - 1);

            $text = __('admin.products_edit_product_updated_successfully');
            $markup = $this->getMainAdminMenuKeyboard();
            $this->sendMessage($text, 'HTML', $markup, $this->user->last_message_id);
        }

    }
    public function sendStoreEditProductPhotoMessage($action){
        if ($action == __('admin.products_cancel_editing')){
            $this->cancelEditingProduct();
        }else{
            if(!isset($this->message['photo'])){
                $text = __('admin.products_edit_product_content_error');
                return $this->sendMessage($text, 'HTML');
            }
            $last = end($this->message['photo']);
            $data = [
                'photo_id' => $last['file_id'],
            ];
//            dd($data);
            $product = $this->productService->editProductPhoto($this->user->last_value, $data);
            if(!$product instanceof Product){
                $text = __('admin.products_edit_product_name_error');
                return $this->sendMessage($text, 'HTML');
            }

            $markup = $this->getEditProductKeyboard($product);
            $text = $this->getEditProductText($product);
            $this->sendPhoto($this->user->telegram_id,  $last['file_id'], $text, $markup);

            $this->userService->resetSteps($this->user->telegram_id);

        }

    }
    public function sendStoreEditProductDescriptionMessage($action){
        if ($action == __('admin.products_cancel_editing')){
            $this->cancelEditingProduct();
        }else{

            $data = [
                'description' => $action,
            ];
//            dd($data);
            $product = $this->productService->editProductDescription($this->user->last_value, $data);

            if(!$product instanceof Product){
                $text = __('admin.products_edit_product_name_error');
                return $this->sendMessage($text, 'HTML');
            }

            $this->deleteMessage($this->message['from']['id'], $this->message['message_id']);
            $this->deleteMessage($this->message['from']['id'], $this->message['message_id'] - 1);
            $this->deleteMessage($this->message['from']['id'], $this->message['message_id'] - 2);

            $markup = $this->getEditProductKeyboard($product);
            $text = $this->getEditProductText($product);
            $this->sendPhoto($this->user->telegram_id,  $product->photo_id, $text, $markup);

            $this->userService->resetSteps($this->user->telegram_id);

        }

    }

    public function sendCategoryProductsMessage($category_id, $is_new_message = false){
        $category = $this->categoryService->find($category_id);
        $products = $this->productService->getAll($category_id);

        if (count($products) > 0) {
            $j = 0;
            $k = 0;
            $products = $products->toArray();
            $inline = [];
            for ($i = 0; $i < count($products); $i++) {
                $item = $products[$i];

                if (!$item['translation']['name']) continue;

                if ($item['status'] === 'active'){
                    $status_button = [
                        'text' => __("partner_operator.restaurants_product_button_on", [
                            'product' => $item['translation']['name'],
                        ]),
                        'callback_data' => 'product_off/' . $item['id'],
                    ];
                }else{
                    $status_button = [
                        'text' => __("partner_operator.restaurants_product_button_off", [
                            'product' => $item['translation']['name'],
                        ]),
                        'callback_data' => 'product_on/' . $item['id'],
                    ];
                }

                if (isset($inline[$j]) && count($inline[$j]) == 2) {
                    $k = 0;
                    $j++;
                    $inline[$j][$k] = $status_button;
                } else {
                    $inline[$j][$k] = $status_button;
                }
                $k++;
            }
            if ($category->status == 'active'){
                $statusButton = [
                    'text' => __('admin.category_on'),
                    'callback_data' => 'category_off/' . $category->id
                ];
            }else{
                $statusButton = [
                    'text' => __('admin.category_off'),
                    'callback_data' => 'category_on/' . $category->id
                ];
            }

            $inline[count($inline)][0] = $statusButton;

            $inline[count($inline)][0] = [
                'text' => __('admin.products_back_to_category'),
                'callback_data' => 'back_to_categories/' .  $category->restaurant_id
            ];

            $text = __('admin.category_page', [
                'restaurant' => $category->restaurant->name,
                'category' => $category->translation->name,
            ]);
            $markup = $this->getInlineKeyboard($inline);

            if (!$is_new_message){
                return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
            }else{
                $this->sendMessage($text, 'HTML', $markup);
            }

        } else {
            $text = __('admin.category_page', [
                'restaurant' => $category->restaurant?->name,
                'category' => $category->translation?->name,
            ]);


            $inline[0][0] = [
                'text' => __('admin.products_add_new_product'),
                'callback_data' => 'add_new_product/' . $category_id
            ];

            $inline[1][0] = [
                'text' => __('admin.products_back_to_category'),
                'callback_data' => 'category/' . $category_id
            ];

            $markup = $this->getInlineKeyboard($inline);

            if (!$is_new_message){
                return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
            }else{
                return $this->sendMessage($text, 'HTML', $markup);
            }

        }
    }
//
//
//    public function sendEditRestaurantNameMessageForAdmin($action){
//        $values = [
//            'last_step' => 'edit_restaurant_name',
//            'last_value' => $action,
//            'last_message_id' => $this->callback_query['message']['message_id']
//        ];
//        $this->userService->updateUserLastStep($this->user->telegram_id, $values);
//
//        $text = __('admin.products_enter_a_name_for_edit_restaurant');
//
//        $keyboard[0][0]['text'] = __('admin.products_cancel_editing');
//        $markup = $this->getKeyboard($keyboard, true);
//        $this->sendMessage($text, 'HTML', $markup);
//    }
//    public function sendDeleteRestaurantByAdmin($action){
//        $values = [
//            'last_step' => null,
//            'last_value' => null,
//            'last_message_id' => null
//        ];
//        $this->restaurantService->removeRestaurant($action);
//
//
//        $this->deleteMessage( $this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
//        $this->sendRestaurantsMenuForAdmin();
////        $this->sendMessage($text, $markup);
//    }
//    public function sendEditRestaurantAddressMessageForAdmin($action){
//        $values = [
//            'last_step' => 'edit_restaurant_address',
//            'last_value' => $action,
//            'last_message_id' => $this->callback_query['message']['message_id']
//        ];
//        $this->userService->updateUserLastStep($this->user->telegram_id, $values);
//
//        $text = __('admin.products_enter_a_address_for_edit_restaurant');
//
//        $keyboard[0][0]['text'] = __('admin.products_cancel_editing');
//        $markup = $this->getKeyboard($keyboard, true);
//        $this->sendMessage($text, 'HTML', $markup);
//    }
//    public function sendEditRestaurantLocationMessageForAdmin($action){
//        $values = [
//            'last_step' => 'edit_restaurant_location',
//            'last_value' => $action,
//            'last_message_id' => $this->callback_query['message']['message_id']
//        ];
//        $this->userService->updateUserLastStep($this->user->telegram_id, $values);
//
//        $text = __('admin.products_enter_a_location_for_edit_restaurant');
//
//        $keyboard[0][0]['text'] = __('admin.products_cancel_editing');
//        $markup = $this->getKeyboard($keyboard, true);
//
//        $this->sendMessage($text, 'HTML', $markup);
//    }
//    public function sendEditRestaurantPartnerAccountMessageForAdmin($action){
//        $values = [
//            'last_step' => 'edit_restaurant_partner_account',
//            'last_value' => $action,
//            'last_message_id' => $this->callback_query['message']['message_id']
//        ];
//        $this->userService->updateUserLastStep($this->user->telegram_id, $values);
//
//        $partnerUsers = $this->userService->getPartners();
//
//        $inline = [];
//        if (count($partnerUsers) > 0){
//            foreach($partnerUsers as $index => $item){
//                $inline[$index][0]['text'] = $item['first_name'] . ' ' . $item['last_name'];
//                $inline[$index][0]['callback_data'] = 'set_restaurant_partner_account/' . $item['id'];
//            }
//        }
//        $inline[count($inline)][0] = [
//            'text' => __('admin.products_set_edit_restaurant_partner_account_null'),
//            'callback_data' => 'set_restaurant_partner_account/0'
//        ];
//        $markup = $this->getInlineKeyboard($inline);
//        $text = __('admin.products_enter_a_partner_account_for_edit_restaurant');
//        $this->sendMessage($text, 'HTML', $markup);
//    }
}
