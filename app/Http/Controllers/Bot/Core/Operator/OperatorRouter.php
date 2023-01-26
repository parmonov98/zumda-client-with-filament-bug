<?php

namespace App\Http\Controllers\Bot\Core\Operator;

use App\Http\Controllers\Bot\Core\Administrator\Methods\AdministratorMessages;
use App\Http\Controllers\Bot\Core\MakeComponents;
use App\Http\Controllers\Bot\Core\Operator\Methods\OperatorCallbacks;
use App\Http\Controllers\Bot\Core\Operator\Methods\OperatorMessages;
use App\Http\Controllers\Bot\Core\RequestTrait;
use App\Http\Controllers\Bot\Core\SharedMethods\RestaurantMethods;
use App\Http\Controllers\Bot\Services\CategoryService;
use App\Http\Controllers\Bot\Services\ProductService;
use App\Http\Controllers\Bot\Services\RestaurantService;
use App\Http\Controllers\Bot\Services\UserService;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class OperatorRouter extends Controller
{
    use RequestTrait;
    use RestaurantMethods;
    use OperatorMessages;
    use OperatorCallbacks;
    use AdministratorMessages;
    use MakeComponents;

    protected mixed $type;
    protected mixed $message;
    protected mixed $text;
    protected mixed $data;
    protected mixed $callback_query;
    protected mixed $action;
    protected mixed $user;

    /**
     * Operator request
     *
     * @param  Request  $request
     * @return void
     */

    public function __construct(
        protected Request $request,
        protected UserService $userService,
        protected RestaurantService $restaurantService,
        protected CategoryService $categoryService,
        protected ProductService $productService,
    )
    {

    }
    public function routes(User $user, string $type, mixed $action)
    {
        $this->user = $user;
        $this->type = $type;
        $this->action = $action;
        $request = $this->request;
        $this->callback_query = $request->get('callback_query');
        $this->message = $request->get('message');

        if ($this->type == 'message'){
            $this->messages($action);
        }
        if ($this->type == 'callback') {
            $this->callbacks($action);
        }
    }

    public function messages($action){
        $this->text = $action;
//        dd($this->user);
//        if ($this->user->status !== 'active'){
//            $this->greetUserDisabled();
//            return 'OK_OPERATOR_DISABLED';
//        }

        if (isset($this->message['contact'])){
            $this->user->phone_number = $this->message['contact']['phone_number'];
            $this->user->save();
            $text = __('driver.phone_saved');
            return $this->sendMessage($text);
        }


        if ($action == '/start') {
            return $this->greetOperator($this->user->telegram_id);
        }
        if ($action == __('operator.keyboard_order')) {
            return $this->sendRestaurantsMenu();
        }
        if ($action == __('operator.keyboard_statistics')) {
            return $this->sendOperatorStaistics();
        }
        if ($action == __('operator.go_to_home')) {
            return $this->sendMainOperatorMenu();
        }

        if ($this->user->last_step != null) {
            switch ($this->user->last_step) {
                case 'order_client_name':
                    if (isset($this->message['text']) && $this->message['text'] == __('operator.order_cancel_button')) {
                        return $this->cancelOrderByOperator();
                    }else{
                        return $this->storeOrderClientNameByOperator($this->message['text']);
                    }
                    break;

                case 'order_confirmation':
//                    dd('order_confirmation');
                    if (isset($this->message['text']) && $this->message['text'] == __('operator.order_cancel_button')) {
                        return $this->cancelOrderByOperator();
                    }else{
                        if (isset($this->message['location'])){
                            return $this->storeOrderLocationByOperator();
                        }else{
                            return $this->storeOrderAddressByOperator($action);
                        }
                    }
                    break;
                case 'order_location_or_address':
                    if (isset($this->message['text']) && $this->message['text'] == __('operator.order_cancel_button')) {
//                         dd(__('client.order_cancel_button'));
                        return $this->cancelOrderByOperator();
                    }else{
                        return $this->sendMessage(__('operator.invalid_order_confirm_location_or_address_button'));
                    }
                    break;
                case 'confirm_order_location_or_address':
                    if (isset($this->message['text']) && $this->message['text'] == __('operator.order_back_to_previous_step')) {
                        return $this->backToOrderOrLocationByOperator();
                    }else{
                        return $this->storeOrderLandmarkByOperator($this->text);
                    }
                    break;
                case 'order_landmark':
                    if (isset($this->message['text']) && $this->message['text'] == __('operator.order_back_to_previous_step')) {
                        return $this->backToOrderLandmarkByOperator();
//                        $this->backToOrderOrLocationByOperator();
                    }else{
                        if (isset($this->message['contact'])) {
                            return $this->storeOrderContactByOperator($this->message['contact']);
                        }else{
                            return $this->storeOrderContactByOperator();
                        }
                    }
                    break;
                case 'order_phone_number':

                    if (isset($this->message['text']) && $this->message['text'] == __('operator.order_back_to_previous_step')) {
                        return $this->backToOrderPhoneNumberByOperator();
                    }else{
                        return $this->acceptCustomerNoteByOperator();
                    }
                    break;
                case 'order_customer_note':
                    if (isset($this->message['text']) && $this->message['text'] == __('operator.order_back_to_previous_step')) {
                        return $this->backToOrderCustomerNoteByOperator();
                    }else{
                        return $this->acceptCustomerNoteByOperator();
                    }

                    break;
                default:

                    break;
            }
        }


        if ($this->text == '/menu') {
            return $this->sendMainOperatorMenu();

        }
        if ($this->text == __('operator.keyboard_cart')) {
            return $this->sendCartItemsByOperator();
        }
        $restaurants = $this->restaurantService->getActiveRestaurants($as_array = true);
        if (array_key_exists($action, $restaurants)) {
            return $this->sendRestaurantMenu($restaurants[$action]);
        }

        if ($action){

            if (!$this->user->last_value){
                if (str_contains($action, 'ID') || str_contains($action, '#') || str_contains($action, '#ID')){
                    $str = str_replace(['#', 'ID', '#ID'], ['', '', ''], $action);
                    $ID = intval($str);

                    $orders = Order::where('id', 'LIKE', "%$ID%")->get();
                    if ($orders->count() > 0){
                        if (!$orders->count() < 5){
                            foreach ($orders as $order){
                                $text = $this->getOrderViewText($order, $this->user);
                                $keyboard = $this->getOrderViewKeyboard($order, $this->user);
                                return $this->sendMessage($text, 'HTML', $keyboard);
                            }
                        }else{
                            $order = $orders->first();
                            $text = $this->getOrderViewText($order, $this->user);
                            $keyboard = $this->getOrderViewKeyboard($order, $this->user);
                            return $this->sendMessage($text, 'HTML', $keyboard);
                        }
                    }
                    exit('OK');
                }

                $phone_number = intval($action);
                if (strlen($phone_number) >= 4){
                    $users = $this->userService->searchClientsByPhone($action);

                    if ($users->count() > 0){
                        $users->each(function($item){
                            $text = $this->getClientViewText($item);
                            $markup = $this->getClientViewKeyboard($item);
                            $this->sendMessage($text, 'HTML', $markup);
                        });

                    }else{
                        $text = __("Mijoz topilmadi!");
                        return $this->sendMessage($text, 'HTML');
                    }
                }else{
//                    $text = __("Mijoz telefon raqami xonalari soni 4 ta bo'lishi kerak. ltimos, mijoz raqamini yanayam aniq kiriting.");
//                    $this->sendMessage($text, 'HTML');
                }

            }
        }
        if ($action){
            $text = __("Noma'lum buyruq berildi!");
            $res = $this->sendMessage($text);
        }
        return $res;
    }

    public function callbacks($action){
        if (!$this->user->operator->status){
            return $this->greetUserDisabled();
        }

        if ($action == 'go_to_cart') {
            return  $this->sendCartItemsByOperator($is_inline = true);
        }

        if ($action == 'clear_the_cart') {
            return  $this->clearUserCartByOperator();
        }

        // order_back_to_last_step\/([0-9]+)
        preg_match("/^order_back_to_last_step\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->backToOrderLastStep($tokens[1]);
        }

        // order_preset_client_id\/([0-9]+)
        preg_match("/^order_preset_client_id\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $client_id = $tokens[1];
            $this->user->operator->temp_client_id = $client_id;
            $this->user->save();
            $this->user->refresh();
            $text = __('operator.order_client_id_set_message');
            return $this->answerCallbackQuery($text, false);
        }
        // order_preset_new_client
        preg_match("/^order_preset_new_client/", $action, $tokens);
        if ($action == 'order_preset_new_client'){
            $this->user->operator->temp_client_id = null;
            $this->user->save();
            $this->user->refresh();
            $text = __('operator.order_client_id_unset_message');
            return $this->answerCallbackQuery($text, false);
        }
        // order_select_shipping_address\/([0-9]+)
        preg_match("/^order_select_shipping_location\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $order_id = $tokens[1];
//            dd($order_id);
            return $this->sendOrderChooseLocationFromOrders($order_id);
        }
        // confirm_order_and_send_to_restaurants_vs_drivers\/([0-9]+)
        preg_match("/^confirm_order_and_send_to_restaurants_vs_drivers\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendOrderToPartnerAndDrivers($tokens[1]);
        }

        // confirm_order_and_send_to_restaurants_only\/([0-9]+)
        preg_match("/^confirm_order_and_send_to_restaurants_only\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendOrderToPartnerOnly($tokens[1]);
        }

        // order_resend_order_to_drivers\/([0-9]+)
        preg_match("/^order_resend_order_to_drivers\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->resendOrderToDrivers($tokens[1]);
        }


        preg_match("/^order_move_to_driver_list\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendDriversListForOperator($tokens[1]);
        }

        preg_match("/^order_set_driver_by_operator\/([0-9]+)\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->setOrderDriverByOperator(order_id: $tokens[1], driver_id: $tokens[2]);
        }

        preg_match("/^back_to_order_view\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendOrderViewByOperator($tokens[1]);
        }

        preg_match("/^order_move_to_cancelled\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->deleteOrderByOperator($tokens[1]);
        }

        preg_match("/^order_confirm_delete\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->confirmDeleteOrderByOperator($tokens[1]);
        }

        preg_match("/^order_disconfirm_delete\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->disconfirmDeleteOrderByOperator($tokens[1]);
        }


        preg_match("/^order_resend_order_to_drivers_with_prepared_status\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->resendOrderWithPreparedToDrivers($tokens[1]);
        }


        preg_match("/^order_update\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->updateOrder($tokens[1]);
        }


        preg_match("/^back_to_main_category_menu\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $restaurant_id = $tokens[1];
            $categories = $this->categoryService->getActiveCategories($restaurant_id);

            $menu = $this->getOrderMenuByOperator($level = 1, $categories);
            $text = __('operator.main_categories', [
                'menu_link' => env('BOT_MENU_LINK')
            ]);
            $this->editMessageText($message_id = null, $text, 'HTML', $menu, disable_web_page_preview: true);
            return $this->answerCallbackQuery();
        }


        if ($action == "back_to_main_category_menu_from_product/") {
            $this->sendRestaurantsMenu();
            return $this->deleteMessage($this->user->telegram_id, $this->callback_query['message']['message_id']);
        }

        preg_match("/^back_to_main_category_menu_from_product\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $restaurant_id = $tokens[1];
            return $this->backToMainCategoryMenuByOperator($restaurant_id);
        }

        preg_match("/^category\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->processCategoryForProductsByOperator($category_id  = $tokens[1]);
        }
        preg_match("/^product\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->processProductByOperator($product_id  = $tokens[1]);
        }
//        preg_match("/^repeat\/([0-9]+)/", $action, $tokens);
//        if (isset($tokens[1])) {
//            $res = $this->repeatOrderByOperator($order_id  = $tokens[1]);
//        }
//        preg_match("/^deleteOrder\/([0-9]+)/", $action, $tokens);
//        if (isset($tokens[1])) {
//            $res = $this->deleteOrderByOperator($order_id  = $tokens[1]);
//        }

        // preg_match("/^subcategory\/([0-9])+/", $action, $tokens);
        // if (isset($tokens[1])) {
        //     $res = $this->processSubcategory($category_id  = $tokens[1]);
        //     dd($res);
        //     die;
        // }

        preg_match("/^back_to_product_view\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->processProductByOperator($product_id  = $tokens[1]);
        }

        preg_match("/^back_to_product_list\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->backToProductListByOperator($tokens[1]);
        }

        preg_match("/^add_to_cart\/([0-9]+)\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->addToCartByOperator($tokens);
        }

        preg_match("/^decrement_cart_item\/([0-9]+)\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->decrementCartItemByOperator($tokens);
        }
        preg_match("/^increment_cart_item\/([0-9]+)\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->incrementCartItemByOperator($tokens);
        }

        preg_match("/^decrement_cart_item_by_10x\/([0-9]+)\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->decrementCartItemBy10XByOperator($tokens);
        }
        preg_match("/^increment_cart_item_by_10x\/([0-9]+)\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->incrementCartItemBy10XByOperator($tokens);
        }

        if ($action == 'confirm_the_order') {
            return $this->confirmOrderByOperator();
        }

        if ($action == 'confirm_the_received_address') {
            if ($this->user->last_step != null) {
//                $this->confirmOrderLandmarkByOperator();
                return $this->confirmOrderAddressOrLocationByOperator();
            }
        }

        if ($action == 'disconfirm_the_received_address') {
            if ($this->user->last_step != null) {
                return $this->disconfirmOrderAddressOrLocationByOperator();
            }
        }
        if (!$action){
            $action = '-';
        }
        $res = $this->answerCallbackQuery($action, false);

        // if ($action){
        // $text = __("Noma'lum buyruq berildi!");
        // $this->sendMessage($text);
        //}
        return $res;
    }
}
