<?php

namespace App\Http\Controllers\Bot\Core\Administrator\Methods;

use App\Http\Controllers\Bot\Core\Partner\Methods\PartnerMethods;
use App\Http\Controllers\Bot\Core\PartnerOperator\Methods\PartnerOperatorMessages;
use App\Models\Driver;
use App\Models\Messages;
use App\Models\Operator;
use App\Models\PartnerOperator;
use App\Models\Restaurant;
use App\Models\User;

trait AdministratorCallbacks
{
    use PartnerOperatorMessages;

    public function mailToUsers($tokens)
    {

        $message = Messages::find($tokens[1]);

        $customers = User::where('status', 'active')
            ->where('role', 'user')
            ->get();

        // dd($customers->toArray());

        $text = $this->generateHTMLText($message->text, json_decode($message->entities, 1));

        // dd($text);
        // dd($items);

        $cnt = 0;

        foreach ($customers as $item) {

            // dd($item);

            $data = [
                'chat_id' => $item['telegram_id'],
                'caption' => htmlspecialchars($text),
                'parse_mode' => 'HTML',
                'photo' => $message->content
            ];

            $res = $this->postRequest('sendPhoto', $data);

            if (isset($res['ok']) && $res['ok'] !== true) {
                $item->status = 'inactive';
                $item->save();
            }

            if ($cnt % 25 == 0) {
                sleep(1);
                $cnt = 0;
            }
            $cnt++;
        }

        // $this->sendMessage(__('admin.mailing_completed'));
        return $this->answerCallbackQuery(__('admin.mailing_completed'));
    }

    public function sendToggleRestaurantStatusRequest($action){
        return $this->toggleRestaurantStatus($action);
    }

    public function sendToggleOperatorStatusRequest($action){
        return $this->toggleOperatorStatus($action);
    }

    public function toggleOperatorStatus($operator_id){
        $operator = $this->userService->findOperator($operator_id);
        if (!$operator instanceof Operator){
            $text = __('admin.users_no_user_found');
            $res = $this->answerCallbackQuery(htmlspecialchars($text), true);
            if (!$res) return $this->sendMessage($text);
            return $res;
        }
        if ($operator->status == 'active'){
            $operator->status = 'inactive';
        }else{
            $operator->status = 'active';
        }

        $operator->save();
        $operator->refresh();

        $markup = $this->getEditOperatorViewKeyboard($operator);
        $text = $this->getEditOperatorText($operator);

        return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
    }

    public function sendDeleteOperatorMessage($user_id){

        $operator = $this->userService->findOperator($user_id);
        $user = $operator->user;

        if (!$operator instanceof Operator){
            $text = __('admin.users_no_user_found');
            $res = $this->answerCallbackQuery(htmlspecialchars($text), true);
            if (!$res) return $this->sendMessage($text);
            return $res;
        }

        $markup = $this->getDeleteOperatorConfirmationKeyboard($operator);
        $text = $this->getEditOperatorText($operator);

        return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
    }


    public function sendBackToRestaurantsForAdmin(){
        $restaurants = $this->restaurantService->getRestaurants();
        $inline = [];
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


        $text = __('admin.main_restaurants', [
            'menu_link' => env('BOT_MENU_LINK')
        ]);
        $markup = $this->getInlineKeyboard($inline);

        return $this->editMessageText( $this->callback_query['message']['message_id'], $text, 'HTML', $markup);
    }

    public function sendOperatorsMenuForAdmin(){
        $operators = $this->userService->getOperators();
        $inline = [];
        $j = 0;
        $k = 0;
//        $operators = $operators->toArray();
//        dd($operators);
        for ($i = 0; $i < count($operators); $i++) {
            $item = $operators[$i];
            $buttonData = 'operator/' . $item['id'];
//            dd($buttonData);
            if (isset($inline[$j]) && count($inline[$j]) == 2) {
                $k = 0;
                $j++;
                $inline[$j][$k]['text'] = $item['name'];
                $inline[$j][$k]['callback_data'] = $buttonData;
            } else {
                $inline[$j][$k]['text'] = $item['name'];
                $inline[$j][$k]['callback_data'] = $buttonData;
            }
            $k++;
        }

        $inline[count($inline)][0] = [
            'text' => __('admin.users_back_to_users'),
            'callback_data' => 'back_to_users'
        ];
        $text = __('admin.users_operators', [
            'menu_link' => env('BOT_MENU_LINK')
        ]);
        $markup = $this->getInlineKeyboard($inline);

        return $this->editMessageText( $this->callback_query['message']['message_id'], $text, 'HTML', $markup);
    }

    public function sendDriversMenuForAdmin(){
        $drivers = $this->userService->getDrivers();
        $inline = [];
        $j = 0;
        $k = 0;
        $drivers = $drivers->toArray();
        $all = count($drivers);
        $disabled = 0;
        $self_disabled = 0;
        $active = 0;
        for ($i = 0; $i < count($drivers); $i++) {
            $item = $drivers[$i];
            $buttonData = 'driver/' . $item['id'];
            if (isset($inline[$j]) && count($inline[$j]) == 2) {
                $k = 0;
                $j++;
                $inline[$j][$k]['text'] = $item['name'];

                if ($item['self_status'] == 'active' && $item['status'] == 'active'){
                    $inline[$j][$k]['text'] = $item['name'] . "✅";
                    $active++;
                }else{
                    if ($item['status'] == 'inactive'){
                        $disabled++;
                        $inline[$j][$k]['text'] = $item['name'] . "❌";
                    }elseif ($item['self_status'] == 'inactive'){
                        $self_disabled++;
                        $inline[$j][$k]['text'] = $item['name'] . "⭕";
                    }else{
                        $inline[$j][$k]['text'] = $item['name'] ;
                    }
                }
                $inline[$j][$k]['callback_data'] = $buttonData;
            } else {
                $inline[$j][$k]['text'] = $item['name'] ;
                if ($item['self_status'] == 'active' && $item['status'] == 'active'){
                    $inline[$j][$k]['text'] = $item['name'];
                    $active++;
                }elseif ($item['self_status'] == 'inactive') {

                    if ($item['status'] == 'inactive'){
                        $disabled++;
                        $inline[$j][$k]['text'] = $item['name'] . "❌";
                    }elseif ($item['self_status'] == 'inactive'){
                        $self_disabled++;
                        $inline[$j][$k]['text'] = $item['name'] . "⭕";
                    }else{
                        $inline[$j][$k]['text'] = $item['name'] ;
                    }
                }
                $inline[$j][$k]['callback_data'] = $buttonData;
            }
            $k++;
        }

        $inline[count($inline)][0] = [
            'text' => __('admin.users_back_to_users'),
            'callback_data' => 'back_to_users'
        ];
        $text = __('admin.users_drivers', [
            'menu_link' => env('BOT_MENU_LINK'),
            'all' => $all,
            'disabled' => $disabled,
            'self_disabled' => $self_disabled,
            'active' => $active,
        ]);
        $markup = $this->getInlineKeyboard($inline);

        return $this->editMessageText( $this->callback_query['message']['message_id'], $text, 'HTML', $markup);
    }

    public function sendPartnerOperatorsMenuForAdmin(){
        $partner_operators = $this->userService->getPartnerOperators();
        $inline = [];
        $j = 0;
        $k = 0;
        $partner_operators = $partner_operators->toArray();
        for ($i = 0; $i < count($partner_operators); $i++) {
            $item = $partner_operators[$i];
            $buttonData = 'partner_operator/' . $item['id'];
            if (isset($inline[$j]) && count($inline[$j]) == 2) {
                $k = 0;
                $j++;
                $inline[$j][$k]['text'] = $item['name'];
                $inline[$j][$k]['callback_data'] = $buttonData;
            } else {
                $inline[$j][$k]['text'] = $item['name'];
                $inline[$j][$k]['callback_data'] = $buttonData;
            }
            $k++;
        }

        $inline[count($inline)][0] = [
            'text' => __('admin.users_back_to_users'),
            'callback_data' => 'back_to_users'
        ];
        $text = __('admin.users_partner_operators', [
            'menu_link' => env('BOT_MENU_LINK')
        ]);
        $markup = $this->getInlineKeyboard($inline);

        return $this->editMessageText( $this->callback_query['message']['message_id'], $text, 'HTML', $markup);
    }

    public function sendPartnersMenuForAdmin(){
        $partners = $this->userService->getPartners();
        $inline = [];
        $j = 0;
        $k = 0;
        $partners = $partners->toArray();
        for ($i = 0; $i < count($partners); $i++) {
            $item = $partners[$i];

            if (isset($inline[$j]) && count($inline[$j]) == 2) {
                $k = 0;
                $j++;
                $inline[$j][$k]['text'] = $item['name'];
                $inline[$j][$k]['callback_data'] = 'partner/' . $item['id'];
            } else {
                $inline[$j][$k]['text'] = $item['name'];
                $inline[$j][$k]['callback_data'] = 'partner/' . $item['id'];
            }
            $k++;
        }

        $inline[count($inline)][0] = [
            'text' => __('admin.users_back_to_users'),
            'callback_data' => 'back_to_users'
        ];
        $text = __('admin.users_partners', [
            'menu_link' => env('BOT_MENU_LINK')
        ]);
        $markup = $this->getInlineKeyboard($inline);

        return $this->editMessageText( $this->callback_query['message']['message_id'], $text, 'HTML', $markup);
    }

    public function sendRestaurantViewMenuForAdmin($action){

        $restaurant = $this->restaurantService->find($action);

        if (!$restaurant){
            $text = __('admin.restaurants_no_restaurant_found');
            $this->answerCallbackQuery(htmlspecialchars($text), true);
        }

        $markup = $this->getEditRestaurantViewKeyboard($restaurant);
        $text = $this->getEditRestaurantText($restaurant);


        return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
    }

    public function sendOperatorViewMenuForAdmin($operator_id){

        $operator = $this->userService->findOperator($operator_id);

        $user = $operator->user;

        if (!$operator instanceof Operator){
            $text = __('admin.users_no_user_found');
            $res = $this->answerCallbackQuery(htmlspecialchars($text), true);
            if (!$res) return $this->sendMessage($text);
            return $res;
        }


        $markup = $this->getEditOperatorViewKeyboard($operator);
        $text = $this->getEditOperatorText($operator);

        return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
    }

    public function sendDriverViewMenuForAdmin($driver_id){
        $driver = $this->userService->findDriver($driver_id);
        $user = $driver->user;
        if (!$driver instanceof Driver){
            $text = __('admin.users_no_user_found');
            $res = $this->answerCallbackQuery(htmlspecialchars($text), true);
            if (!$res) return $this->sendMessage($text);
            return $res;
        }

        $markup = $this->getEditDriverViewKeyboard($driver);
        $text = $this->getEditDriverText($driver);

        return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
    }

    public function updateDriverViewForAdmin($driver_id){

        $driver = $this->userService->findDriver($driver_id);
        $user = $driver->user;
        if (!$user){
            $text = __('admin.users_no_user_found');
            $this->answerCallbackQuery(htmlspecialchars($text), true);
        }
        $text = $this->getEditDriverText($driver);

        $chat_info = $this->getRequest('getChat', ['chat_id' => $user->telegram_id]);

        if ($chat_info !== false){
            $driver->name = $chat_info['first_name'];
            $driver->save();
            $driver->refresh();
        }else{
            $text = __('admin.drivers_user_stopped_the_bot_or_the_moved_another_username');
        }

        $markup = $this->getEditDriverViewKeyboard($driver);

        return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
    }

    public function deleteDriverByAdminConfirmation($action){

        $driver = $this->userService->findDriver($action);
        $user = $driver->user;

        $driver->delete();
        if (!$driver instanceof Driver){
            $text = __('admin.users_no_user_found');
            $this->answerCallbackQuery(htmlspecialchars($text), true);
        }

        if ($user instanceof User)
        {
            $user->role = 'user';
            $user->save();
        }
//        $user->driver_orders()->whereIn('status', ['delivering', 'paid'])->update([
//            'status' => 'canceled'
//        ]);
//        $user->delete();

        return $this->sendDriversMenuForAdmin();
    }

    public function deletePartnerOperatorByAdminConfirmation($partner_operator_id){

        $partner_operator = $this->userService->findPartnerOperator($partner_operator_id);
        $user = $partner_operator->user;

        $partner_operator->delete();
        if (!$partner_operator instanceof PartnerOperator){
            $text = __('admin.users_no_user_found');
            $this->answerCallbackQuery(htmlspecialchars($text), true);
        }

        if ($user instanceof User)
        {
            $user->role = 'user';
            $user->save();
        }

        // TODO doing some clean up or freezing data related to user

        return $this->sendPartnerOperatorsMenuForAdmin();
    }

    public function deleteOperatorByAdminConfirmation($action){

        $operator = $this->userService->findOperator($action);

        $user = $operator->user;

        if (!$operator instanceof Operator)
        {
            $text = __('admin.users_no_user_found');
            $res = $this->answerCallbackQuery(htmlspecialchars($text), true);
            if ($res) return $this->sendMessage($text);
            return $res;
        }

        if ($operator instanceof Operator){
            $operator->delete();
        }

        if ($user instanceof User)
        {
            $user->role = 'user';
            $user->save();
        }
        return $this->sendOperatorsMenuForAdmin();
    }

    public function sendPartnerViewMenuForAdmin($action){

        $partner = $this->userService->findPartner($action);

        if (!$partner){
            $text = __('admin.users_no_user_found');
            $this->answerCallbackQuery(htmlspecialchars($text), true);
        }

        $markup = $this->getEditPartnerViewKeyboard($partner);
        $text = $this->getEditPartnerText($partner);


        return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
    }

    public function sendPartnerOperatorViewMenuForAdmin($action){

        $partner_operator = $this->userService->findPartnerOperator($action);

        if (!$partner_operator instanceof PartnerOperator){
            $text = __('admin.users_no_user_found');
            return $this->answerCallbackQuery(htmlspecialchars($text), true);
        }

        $markup = $this->getEditPartnerOperatorViewKeyboard($partner_operator);
        $text = $this->getEditPartnerOperatorText($partner_operator);


        return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
    }

    public function updateRestaurantViewForAdmin($action){

        $user = $this->userService->findPartnerOperator($action);

        if (!$user){
            $text = __('admin.users_no_user_found');
            $this->answerCallbackQuery(htmlspecialchars($text), true);
        }

        $text = $this->getEditPartnerText($user);

        $chat_info = $this->getRequest('getChat', ['chat_id' => $user->telegram_id]);

        if ($chat_info !== false){
            $user->name = $chat_info['first_name'];
            $user->save();
            $user->refresh();
        }else{
            $text = __('admin.restaurant_employee_user_stopped_the_bot_or_the_moved_another_username');
        }

        $markup = $this->getEditPartnerViewKeyboard($user);

        return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
    }

    public function sendEditRestaurantMenuForAdmin($action){

        $restaurant = $this->restaurantService->find($action);

        $markup = $this->getEditRestaurantKeyboard($restaurant);
        $text = $this->getEditRestaurantText($restaurant);

        return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
    }

    public function sendEditRestaurantDishesMenuForAdmin($action){

        $restaurant = $this->restaurantService->find($action);

        $markup = $this->getEditRestaurantDishesKeyboard($restaurant);
        $text = $this->getEditRestaurantDishesText($restaurant);

        return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
    }

    public function sendRestaurantCategoriesMenuForAdmin($restaruant_id, $is_new_message = false){
        $restaurant = $this->restaurantService->find($restaruant_id);

        $categories = $this->categoryService->getAll($restaruant_id);

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

            $inline[count($inline)][0] = [
                'text' => __('admin.categories_add_new_category'),
                'callback_data' => 'add_new_category/' . $restaruant_id
            ];

            $inline[count($inline)][0] = [
                'text' => __('admin.categories_back_to_restaurant'),
                'callback_data' => 'restaurant/' . $restaurant->id
            ];

            $text = __('admin.restaurant_page', [
                'name' => $restaurant->name
            ]);
            $markup = $this->getInlineKeyboard($inline);

            if (!$is_new_message){
                return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
            }else{
                return $this->sendMessage($text, 'HTML', $markup);
            }

        } else {
            $text = __('admin.restaurant_page', [
                'name' => $restaurant->name
            ]);

            $inline[0][0] = [
                'text' => __('admin.categories_add_new_category'),
                'callback_data' => 'add_new_category/' . $restaruant_id
            ];

            $inline[1][0] = [
                'text' => __('admin.categories_back_to_restaurant'),
                'callback_data' => 'categories_back_to_restaurant/' . $restaruant_id
            ];

            $markup = $this->getInlineKeyboard($inline);

            if (!$is_new_message){
                return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
            }else{
                return $this->sendMessage($text, 'HTML', $markup);
            }

        }
    }

    public function sendConfirmAddRestaurantEmployeeMessage($user_id, $restaurant_id){

        $restaurant = $this->restaurantService->find($restaurant_id);

        if(!$restaurant instanceof Restaurant){
            $text = __('admin.restaurants_edit_restaurant_duplicate_error');
            return $this->sendMessage($text, 'HTML');
        }

        $partner_operator = $this->userService->findPartnerOperator($user_id);
        $partner_operator->restaurant_id = $restaurant_id;
        $partner_operator->save();
        $partner_operator->refresh();

        return $this->sendEditRestaurantEmployeesAccountMessageForAdmin($restaurant_id);
    }

}

