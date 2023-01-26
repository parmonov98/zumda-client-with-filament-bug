<?php
namespace App\Http\Controllers\Bot\Core\SharedMethods;

use App\Models\Category;

trait CategoryMethods {


    public function sendCategoryViewMessage($action){

        $category = $this->categoryService->find($action);

        if (!$category instanceof Category){
            $text = __('admin.categories_error_add_category');
            return $this->answerCallbackQuery($text, true);
        }

        $text = $this->getEditCategoryText($category);
        $markup = $this->getCategoryViewKeyboard($category);
        return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
    }

    public function sendAddCategoryMessage($action){

        $data = [
            'restaurant_id' => $action,
            'user_id' => $this->user->id,
            'lang' => 'uz',
        ];
        $category = $this->categoryService->addCategory($data);

        if (!$category instanceof Category){
            $text = __('admin.categories_error_add_category');
            return $this->answerCallbackQuery($text, true);
        }

        $values = [
            'last_step' => 'add_new_category_name',
            'last_value' => $category->id // category_id
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $keyboard[0][0]['text'] = __('admin.categories_cancel_creating');
        $markup = $this->getKeyboard($keyboard, true);

        $text = __('admin.categories_enter_a_name_for_new_category');
        $this->sendMessage($text, 'HTML', $markup);
    }

    public function cancelCreatingNewCategory(){

        $this->categoryService->removeCategory($this->user->last_value);

        $this->userService->resetSteps($this->user->telegram_id);

        $text = __('admin.categories_cancelled_creating_new_category');

        $this->sendMessage($text, 'HTML', $this->getMainAdminMenuKeyboard());

    }

    public function getEditCategoryKeyboard($category){

        $options = [
            [
                [
                    'text' => __('admin.categories_edit_category_name_button'),
                    'callback_data' => 'edit_category_name/' . $category->id
                ],
                [
                    'text' => __('admin.categories_edit_category_description_button'),
                    'callback_data' => 'edit_category_description/' . $category->id
                ]
            ],
            [
                [
                    'text' => __('admin.categories_delete_category'),
                    'callback_data' => 'delete_category/' . $category->id
                ]
            ],
            [
                [
                    'text' => __('admin.categories_back_to_category_view'),
                    'callback_data' => 'category/' . $category->id
                ]
            ]
        ];

        return $this->getInlineKeyboard($options);
    }

    public function getCategoryViewKeyboard($category){

        if ($category->status){
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
        $options = [
            [
                [
                    'text' => __('admin.categories_go_to_products'),
                    'callback_data' => 'categories_go_to_products/' . $category->id
                ]
            ],
            [
                [
                    'text' => __('admin.categories_edit_category'),
                    'callback_data' => 'edit_category/' . $category->id
                ]
            ],
            [
                $statusButton
            ],
            [
                [
                    'text' => __('admin.categories_back_to_restaurant_categories'),
                    'callback_data' => 'restaurants_go_to_categories/' . $category->restaurant_id
                ]
            ]
        ];

        return $this->getInlineKeyboard($options);
    }

    public function getEditCategoryText($category){

        $text = __('admin.categories_restaurant_category_view', [
            'name' => $category->translation->name,
            'description' => $category->translation->description,
        ]);

        return $text;
    }


    public function toggleCategoryStatus($action){

        $category = $this->categoryService->find($action);
        if ($category->status){
            $category->status = false;
        }else{
            $category->status = true;
        }
        $category->save();
        $category->refresh();

        return $this->sendCategoryViewMessage($category->id);
    }

    public function sendDeleteCategoryByAdmin($action){
        $values = [
            'last_step' => null,
            'last_value' => null,
            'last_message_id' => null
        ];
        $category = $this->categoryService->find($action);
        $this->categoryService->removeCategory($action);

        $this->deleteMessage( $this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
        $this->sendRestaurantCategoriesMenuForAdmin($category->restaurant_id, true);
    }


    public function sendCategoryProductsMenuForAdmin($category_id, $is_new_message = false){
        $category = $this->categoryService->find($category_id);

        $products = $this->productService->getAll($category_id);

        if (count($products) > 0) {
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
                'callback_data' => 'add_new_product/' . $category_id
            ];

            $inline[count($inline)][0] = [
                'text' => __('admin.products_back_to_category'),
                'callback_data' => 'category/' . $category->id
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
                $this->sendMessage($text, 'HTML', $markup);
            }

        }
    }


    public function sendCategoryProductsFromProductViewMenuForAdmin($action, $is_new_message = false){
        $category = $this->categoryService->find($action);
        $products = $this->productService->getAll($action);

        if (count($products) > 0) {
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
                'callback_data' => 'category/' . $category->id
            ];

            $text = __('admin.category_page', [
                'restaurant' => $category->restaurant->name,
                'category' => $category->translation->name,
            ]);
            $markup = $this->getInlineKeyboard($inline);

            if (!$is_new_message){
                $res = $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
            }else{
                $res =$this->sendMessage($text, 'HTML', $markup);
            }

        } else {
            $text = __('admin.restaurant_page', [
                'name' => $category->translation->name
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
                $res = $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
            }else{
                $res =$this->sendMessage($text, 'HTML', $markup);
            }

        }

        $this->answerCallbackQuery();
        return $res;
    }


    public function sendEditCategoryMenuForAdmin($action){

        $category = $this->categoryService->find($action);

        $markup = $this->getEditCategoryKeyboard($category);
        $text = $this->getEditCategoryText($category);

        return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
    }


    public function sendEditCategoryNameMessageForAdmin($action){
        $values = [
            'last_step' => 'edit_category_name',
            'last_value' => $action,
            'last_message_id' => $this->callback_query['message']['message_id']
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __('admin.categories_enter_a_name_for_edit_category');

        $keyboard[0][0]['text'] = __('admin.categories_cancel_editing');
        $markup = $this->getKeyboard($keyboard, true);
        $this->sendMessage($text, 'HTML', $markup);
    }

    public function sendEditCategoryDescriptionMessageForAdmin($action){
        $values = [
            'last_step' => 'edit_category_description',
            'last_value' => $action,
            'last_message_id' => $this->callback_query['message']['message_id']
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __('admin.categories_enter_a_description_for_for_edit_category');

        $keyboard[0][0]['text'] = __('admin.categories_cancel_editing');
        $markup = $this->getKeyboard($keyboard, true);
        $this->sendMessage($text, 'HTML', $markup);
    }

    public function sendStoreEditCategoryNameMessage($action){
        if ($action == __('admin.categories_cancel_editing')){
            $this->cancelEditingRestaurant();
        }else{
            $data = [
                'name' => $action,
            ];
//            dd($data);
            $category = $this->categoryService->editCategoryName($this->user->last_value, $data);
            if(!$category instanceof Category){
                $text = __('admin.categories_edit_category_name_error');
                return $this->sendMessage($text, 'HTML');
            }
            $markup = $this->getEditCategoryKeyboard($category);
            $text = $this->getEditCategoryText($category);

            return $this->editMessageText($this->user->last_message_id, $text, 'HTML', $markup);
            $values = [
                'last_step' => null,
                'last_value' => null,
                'last_message_id' => null
            ];


            $this->userService->updateUserLastStep($this->user->telegram_id, $values);

            $this->deleteMessage($this->message['from']['id'], $this->message['message_id']);
            $this->deleteMessage($this->message['from']['id'], $this->message['message_id'] - 1);

            $text = __('admin.restaurants_edit_category_updated_successfully');

            $markup = $this->getMainAdminMenuKeyboard();
            $this->sendMessage($text, 'HTML', $markup, $this->user->last_message_id);
        }

    }

    public function sendStoreEditCategoryDescriptionMessage($action){
        if ($action == __('admin.categories_cancel_editing')){
            $this->cancelEditingRestaurant();
        }else{
            $data = [
                'description' => $action,
            ];
//            dd($data);
            $category = $this->categoryService->editCategoryDescription($this->user->last_value, $data);
            if(!$category instanceof Category){
                $text = __('admin.categories_edit_category_name_error');
                return $this->sendMessage($text, 'HTML');
            }
            $markup = $this->getEditCategoryKeyboard($category);
            $text = $this->getEditCategoryText($category);

            return $this->editMessageText($this->user->last_message_id, $text, 'HTML', $markup);
            $values = [
                'last_step' => null,
                'last_value' => null,
                'last_message_id' => null
            ];


            $this->userService->updateUserLastStep($this->user->telegram_id, $values);

            $this->deleteMessage($this->message['from']['id'], $this->message['message_id']);
            $this->deleteMessage($this->message['from']['id'], $this->message['message_id'] - 1);

            $text = __('admin.restaurants_edit_category_updated_successfully');

            $markup = $this->getMainAdminMenuKeyboard();
            $this->sendMessage($text, 'HTML', $markup, $this->user->last_message_id);
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
//        $text = __('admin.categories_enter_a_name_for_edit_restaurant');
//
//        $keyboard[0][0]['text'] = __('admin.categories_cancel_editing');
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
//        $text = __('admin.categories_enter_a_address_for_edit_restaurant');
//
//        $keyboard[0][0]['text'] = __('admin.categories_cancel_editing');
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
//        $text = __('admin.categories_enter_a_location_for_edit_restaurant');
//
//        $keyboard[0][0]['text'] = __('admin.categories_cancel_editing');
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
//            'text' => __('admin.categories_set_edit_restaurant_partner_account_null'),
//            'callback_data' => 'set_restaurant_partner_account/0'
//        ];
//        $markup = $this->getInlineKeyboard($inline);
//        $text = __('admin.categories_enter_a_partner_account_for_edit_restaurant');
//        $this->sendMessage($text, 'HTML', $markup);
//    }
}
