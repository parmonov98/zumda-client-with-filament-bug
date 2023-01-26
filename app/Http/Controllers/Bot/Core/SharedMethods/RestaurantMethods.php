<?php
namespace App\Http\Controllers\Bot\Core\SharedMethods;

use App\Models\Category;
use App\Models\PartnerOperator;
use App\Models\Restaurant;
use App\Models\User;

trait RestaurantMethods {


    public function sendRestaurantsMenuForAdmin($text = '')
    {
        $restaurants = $this->restaurantService->getRestaurants();
        $inline = [];
        if (count($restaurants) > 0) {

            $j = 0;
            $k = 0;
            $restaurants = $restaurants->toArray();
            for ($i = 0; $i < count($restaurants); $i++) {
                $item = $restaurants[$i];
                if (!$item['name']) continue;
                if (isset($inline[$j]) && count($inline[$j]) == 2) {
                    $k = 0;
                    $j++;
                    $inline[$j][$k]['text'] = $item['name'];
                    $inline[$j][$k]['callback_data'] = 'restaurant/' . $item['id'];
                } else {
                    $inline[$j][$k]['text'] = $item['name'];
                    $inline[$j][$k]['callback_data'] = 'restaurant/' . $item['id'];
                }
                $k++;
            }

            $inline[count($inline)][0] = [
                'text' => __('admin.restaurants_add_new_restaurant'),
                'callback_data' => 'add_new_restaurant'
            ];

            if (!$text){
                $text = __('admin.restaurants_main_page');
            }

            $markup = $this->getInlineKeyboard($inline);

            $res = $this->sendMessage($text, 'HTML', $markup);

        } else {
            $text = __('admin.no_restaurant_found');

            $inline[0][0] = [
                'text' => __('admin.restaurants_add_new_restaurant'),
                'callback_data' => 'add_new_restaurant'
            ];

            $markup = $this->getInlineKeyboard($inline);

            $res = $this->sendMessage($text, 'HTML', $markup);

        }

        return $res;
    }

    public function sendCreateNewRestaurantMessage(){
        $values = [
            'last_step' => 'add_new_restaurant_name',
            'last_value' => null
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __('admin.restaurants_enter_a_name_for_new_restaurant');

        $keyboard[0][0]['text'] = __('admin.restaurants_cancel_creating');
        $markup = $this->getKeyboard($keyboard, true);
        return $this->sendMessage($text, 'HTML', $markup);
    }

    public function cancelCreatingNewRestaurant(){

        $this->restaurantService->removeRestaurant($this->user->last_value);

        $values = [
            'last_step' => null,
            'last_value' => null,
            'last_message_id' => null,
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __('admin.restaurants_cancelled_creating_new_restaurant');

        $this->sendMessage($text, 'HTML', $this->getMainAdminMenuKeyboard());

    }

    public function cancelEditingRestaurant(){

        $values = [
            'last_step' => null,
            'last_value' => null,
            'last_message_id' => null,
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __('admin.restaurants_cancelled_editing_restaurant');

        $this->sendMessage($text, 'HTML', $this->getMainAdminMenuKeyboard());

    }

    public function cancelEditingCategory(){

        $values = [
            'last_step' => null,
            'last_value' => null,
            'last_message_id' => null,
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __('admin.categories_cancelled_editing_category');

        $this->sendMessage($text, 'HTML', $this->getMainAdminMenuKeyboard());

    }

    public function getEditRestaurantKeyboard($restaurant){

        $options = [
            [
                [
                    'text' => __('admin.restaurants_edit_restaurant_name_button'),
                    'callback_data' => 'edit_restaurant_name/' . $restaurant->id
                ],
                [
                    'text' => __('admin.restaurants_edit_restaurant_address_button'),
                    'callback_data' => 'edit_restaurant_address/' . $restaurant->id
                ]
            ],
            [
                [
                    'text' => __('admin.restaurants_edit_restaurant_location_button'),
                    'callback_data' => 'edit_restaurant_location/' . $restaurant->id
                ],
                [
                    'text' => __('admin.restaurants_edit_restaurant_employees_button'),
                    'callback_data' => 'edit_restaurant_employees/' . $restaurant->id
                ]
            ],
//            [
//                [
//                    'text' => __('admin.restaurants_update_restaurant_owner'),
//                    'callback_data' => 'update_restaurant_owner/' . $restaurant->id
//                ]
//            ],
            [
                [
                    'text' => __('admin.restaurants_edit_dishes'),
                    'callback_data' => 'edit_restaurant_dishes/' . $restaurant->id
                ]
            ],
            [
                [
                    'text' => __('admin.restaurants_delete_restaurant'),
                    'callback_data' => 'delete_restaurant/' . $restaurant->id
                ]
            ],
            [
                [
                    'text' => __('admin.restaurants_back_to_restaurant_view'),
                    'callback_data' => 'restaurant/' . $restaurant->id
                ]
            ]
        ];

        return $this->getInlineKeyboard($options);
    }

    public function getEditRestaurantDishesKeyboard($restaurant){

        $options = [];

        if (!$restaurant instanceof Restaurant){
            return $this->answerCallbackQuery(__("admin.no_restaurant_found"));
        }

        $dishes = $restaurant->dishes;

        $inline = [];
        if (count($dishes) > 0){
            foreach($dishes as $index => $item){
                $inline[$index][0]['text'] = $item['name'];
                $inline[$index][0]['callback_data'] = 'view_restaurant_dish/' . $item['id'];
            }
        }
        $inline[count($inline)][0] = [
            'text' => __('admin.restaurants_add_edit_restaurant_dish'),
            'callback_data' => 'add_restaurant_dish/' . $restaurant->id
        ];
        $inline[count($inline)][0] = [
            'text' => __('admin.restaurants_edit_restaurant_back_to_restaurant_button'),
            'callback_data' => 'back_to_edit_restaurant/' . $restaurant->id
        ];

        $markup = $this->getInlineKeyboard($inline);

        $text = __('admin.restaurants_edit_restaurant_employees_text', [
            'restaurant' => $restaurant->name
        ]);

//        return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);

        return $this->getInlineKeyboard($options);
    }


    public function getEditRestaurantEmployeeKeyboard($restaurant){

        $options = [
            [
                [
                    'text' => __('admin.restaurants_edit_restaurant_name_button'),
                    'callback_data' => 'edit_restaurant_name/' . $restaurant->id
                ],
                [
                    'text' => __('admin.restaurants_edit_restaurant_address_button'),
                    'callback_data' => 'edit_restaurant_address/' . $restaurant->id
                ]
            ],
            [
                [
                    'text' => __('admin.restaurants_back_to_restaurant_view'),
                    'callback_data' => 'restaurant/' . $restaurant->id
                ]
            ]
        ];

        return $this->getInlineKeyboard($options);
    }

    public function getDeleteRestaurantConfirmationKeyboard($restaurant){

        $options = [
            [
                [
                    'text' => __('admin.restaurants_delete_restaurant_confirm_button'),
                    'callback_data' => 'delete_restaurant_confirm_button/' . $restaurant->id
                ],
                [
                    'text' => __('admin.restaurants_delete_restaurant_back_button'),
                    'callback_data' => 'delete_restaurant_back_button/' . $restaurant->id
                ]
            ]
        ];

        return $this->getInlineKeyboard($options);
    }

    public function getEditRestaurantViewKeyboard($restaurant){

        if ($restaurant->status){
            $statusButton = [
                'text' => __('admin.restaurant_on'),
                'callback_data' => 'restaurant_off/' . $restaurant->id
            ];
        }else{
            $statusButton = [
                'text' => __('admin.restaurant_off'),
                'callback_data' => 'restaurant_on/' . $restaurant->id
            ];
        }
        $options = [
            [
                [
                    'text' => __('admin.restaurants_go_to_categories'),
                    'callback_data' => 'restaurants_go_to_categories/' . $restaurant->id
                ]
            ],
            [
                [
                    'text' => __('admin.restaurants_edit_restaurant'),
                    'callback_data' => 'edit_restaurant/' . $restaurant->id
                ]
            ],
            [
                $statusButton
            ],
            [
                [
                    'text' => __('admin.restaurants_back_to_restaurants'),
                    'callback_data' => 'back_to_restaurants'
                ]
            ]
        ];

        return $this->getInlineKeyboard($options);
    }


    public function getEditRestaurantText($restaurant){
        $employeesText = '-';
        $employees = $restaurant->operators;

        if ($employees->count() > 0){
            $employeesText = '';
            foreach ($employees as $item){
                $employeesText .= $item?->name . "\n";
            }
        }

        $restaurant->load('owner');

        $text = __('admin.restaurants_add_new_restaurant_template', [
            'name' => htmlspecialchars($restaurant->name, ENT_QUOTES),
            'owner' => htmlspecialchars($restaurant->owner?->name, ENT_QUOTES),
            'address' => htmlspecialchars($restaurant->address, ENT_QUOTES),
            'link' => 'https://www.google.com/maps/search/?api=1&query=' . $restaurant->latitude . ',' . $restaurant->longitude,
            'employees' => htmlspecialchars($employeesText),
        ]);

        return $text;
    }


    public function toggleRestaurantStatus($action){

        $restaurant = $this->restaurantService->find($action);
        if ($restaurant->status){
            $restaurant->status = false;
            $restaurant->save();
            $restaurant->refresh();
        }else{
            $restaurant->status = true;
            $restaurant->save();
            $restaurant->refresh();
        }


        $markup = $this->getEditRestaurantViewKeyboard($restaurant);
        $text = $this->getEditRestaurantText($restaurant);

        return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
    }

    public function sendEditRestaurantNameMessageForAdmin($action){
        $values = [
            'last_step' => 'edit_restaurant_name',
            'last_value' => $action,
            'last_message_id' => $this->callback_query['message']['message_id']
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __('admin.restaurants_enter_a_name_for_edit_restaurant');

        $keyboard[0][0]['text'] = __('admin.restaurants_cancel_editing');
        $markup = $this->getKeyboard($keyboard, true);
        $this->sendMessage($text, 'HTML', $markup);
    }
    public function sendDeleteRestaurantByAdmin($action){
        $restaurant = $this->restaurantService->find($action);
        $markup = $this->getDeleteRestaurantConfirmationKeyboard($restaurant);

        $text = __('admin.restaurants_delete_restaurant_confirm_text');
        return $this->editMessageText(null, $text, 'HTML', $markup);
    }
    public function sendDeleteRestaurantConfirmationByAdmin($action){
        $this->restaurantService->removeRestaurant($action);
        $this->deleteMessage( $this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
        $this->sendRestaurantsMenuForAdmin();
    }
    public function sendEditRestaurantAddressMessageForAdmin($action){
        $values = [
            'last_step' => 'edit_restaurant_address',
            'last_value' => $action,
            'last_message_id' => $this->callback_query['message']['message_id']
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __('admin.restaurants_enter_a_address_for_edit_restaurant');

        $keyboard[0][0]['text'] = __('admin.restaurants_cancel_editing');
        $markup = $this->getKeyboard($keyboard, true);
        $this->sendMessage($text, 'HTML', $markup);
    }
    public function sendEditRestaurantLocationMessageForAdmin($action){
        $values = [
            'last_step' => 'edit_restaurant_location',
            'last_value' => $action,
            'last_message_id' => $this->callback_query['message']['message_id']
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $text = __('admin.restaurants_enter_a_location_for_edit_restaurant');

        $keyboard[0][0]['text'] = __('admin.restaurants_cancel_editing');
        $markup = $this->getKeyboard($keyboard, true);

        $this->sendMessage($text, 'HTML', $markup);
    }

    public function sendRestaurantEmployeesMessageForAdmin($action){

        $restaurant = $this->restaurantService->find($action);

        if (!$restaurant instanceof Restaurant){
            $this->answerCallbackQuery(__("admin.no_restaurant_found"));
            return false;
        }

        $partnerUsers = $restaurant->operators;

        $inline = [];
        if (count($partnerUsers) > 0){
            foreach($partnerUsers as $index => $item){
                $inline[$index][0]['text'] = $item['name'];
                $inline[$index][0]['callback_data'] = 'view_restaurant_employee_account/' . $item['id'];
            }
        }
        $inline[count($inline)][0] = [
            'text' => __('admin.restaurants_add_edit_restaurant_partner_account'),
            'callback_data' => 'add_restaurant_employee_account/' . $restaurant->id
        ];
        $inline[count($inline)][0] = [
            'text' => __('admin.restaurants_edit_restaurant_back_to_restaurant_button'),
            'callback_data' => 'back_to_edit_restaurant/' . $restaurant->id
        ];

        $markup = $this->getInlineKeyboard($inline);

        $text = __('admin.restaurants_edit_restaurant_employees_text', [
            'restaurant' => $restaurant->name
        ]);

        return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
//        $this->sendMessage($text, 'HTML', $markup);
    }
    public function sendRestaurantOwnerMessageForAdmin($action){

        $restaurant = Restaurant::find($action);
        if (!$restaurant instanceof Restaurant){
            $this->answerCallbackQuery(__("admin.no_restaurant_found"));
            return false;
        }

        $values = [
            'last_step' => 'edit_restaurant_owner',
            'last_value' => $action,
            'last_message_id' => $this->callback_query['message']['message_id']
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $partnerUsers = $this->userService->getActivePartners();
        $inline = [];
        if (count($partnerUsers) > 0){
            foreach($partnerUsers as $index => $item){
                $inline[$index][0]['text'] = $item['name'];
                $inline[$index][0]['callback_data'] = 'set_new_restaurant_owner/' . $item['id'];
            }
        }

        $inline[count($inline)][0] = [
            'text' => __('admin.restaurants_edit_restaurant_back_to_restaurant_button'),
            'callback_data' => 'back_to_edit_restaurant/' . $restaurant->id
        ];

        $markup = $this->getInlineKeyboard($inline);

        $text = __('admin.restaurants_edit_restaurant_employees_text', [
            'restaurant' => $restaurant->name
        ]);

        return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
//        $this->sendMessage($text, 'HTML', $markup);
    }
    public function sendViewRestaurantEmployeeMessageForAdmin($action){

        $user = $this->userService->find($action);
        $user->load('partner_operator');

        $restaurantOperator = $user->partner_operator;
        $restaurantOperator->load('restaurant');

        if (!$restaurantOperator instanceof PartnerOperator){
            $this->answerCallbackQuery(__("admin.no_restaurant_employee_found"));
            return false;
        }

        $self_status_text =  "✅";
        if ($restaurantOperator->self_status == 'inactive'){
            $self_status_text =  "⭕️";
        }

        $status_button = "✅";
        $status_text =  "✅";
        if ($restaurantOperator->status == 'inactive'){
            $status_button = "⭕️";
            $status_text =  "⭕️";
        }

        $inline = [];
        $inline[0] = [
            [
                'text' => $status_button,
                'callback_data' => 'toggle_restaurant_employee/' . $user->id
            ],
            [
                'text' => "❌",
                'callback_data' => 'delete_restaurant_employee/' . $user->id
            ]
        ];

        $inline[1][0] = [
            'text' => __('admin.restaurants_edit_restaurant_employee_back_to_restaurant_employees_button'),
            'callback_data' => 'back_to_edit_restaurant_employees/' . $restaurantOperator->restaurant->id
        ];

        $markup = $this->getInlineKeyboard($inline);

        $text = __('admin.restaurants_edit_restaurant_employee_single_text', [
            'name' => $restaurantOperator->name,
            'phone' => $user->phone_number,
            'status' => $status_text,
            'self_status' => $self_status_text,
        ]);

        return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);

    }
    public function sendToggleRestaurantEmployeeMessageForAdmin($action){

        $user = $this->userService->find($action);
        if (!$user) return false;
        $user->load('partner_operator');
        $partnerOperator = $user->partner_operator;

        if (!$partnerOperator instanceof PartnerOperator){
            $this->answerCallbackQuery(__("admin.no_restaurant_employee_found"));
            return false;
        }

        if ($partnerOperator->status == 'inactive'){
            $partnerOperator->status = 'active';
        }else{
            $partnerOperator->status = 'inactive';
        }
        $partnerOperator->save();
        $partnerOperator->refresh();
        $this->sendViewRestaurantEmployeeMessageForAdmin($action);

    }
    public function sendEditRestaurantEmployeesAccountMessageForAdmin($action){
        $restaurant_id = $action;
        $values = [
            'last_step' => 'edit_restaurant_employees',
            'last_value' => $restaurant_id,
            'last_message_id' => $this->callback_query['message']['message_id']
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $restaurant = Restaurant::find($restaurant_id);

        if (!$restaurant instanceof Restaurant){
            $this->answerCallbackQuery(__("admin.no_restaurant_found"));
            return false;
        }


        $partnerOperatorsUsers = $this->userService->getActivePartnerOperators();

        $partnerOperatorsIDs = $restaurant->partner_operators()->pluck('id');
        $partnerOperatorsUsers = $partnerOperatorsUsers->filter(fn($item) => !in_array($item['id'], $partnerOperatorsIDs->toArray()))->values();

        $inline = [];
        $j = 0;
        $k = 0;
        $users = $partnerOperatorsUsers->toArray();
        for ($i = 0; $i < count($users); $i++) {
            $item = $users[$i];

            if (isset($inline[$j]) && count($inline[$j]) == 2) {
                $k = 0;
                $j++;
                $inline[$j][$k]['text'] = $item['name'];
                $inline[$j][$k]['callback_data'] = 'confirm_add_restaurant_employee_account/' . $item['id'] . "/" . $restaurant_id;
            } else {
                $inline[$j][$k]['text'] = $item['name'];
                $inline[$j][$k]['callback_data'] = 'confirm_add_restaurant_employee_account/' . $item['id'] . "/" . $restaurant_id;
            }
            $k++;
        }

        $inline[count($inline)][0] = [
            'text' => __('admin.restaurants_edit_restaurant_back_to_restaurant_employees_button'),
            'callback_data' => 'edit_restaurant_employees/' . $restaurant->id
        ];
        $markup = $this->getInlineKeyboard($inline);
        $text = __('admin.restaurants_enter_a_partner_account_for_edit_restaurant');

        return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
    }
}
