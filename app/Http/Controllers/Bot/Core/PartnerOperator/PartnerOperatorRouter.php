<?php

namespace App\Http\Controllers\Bot\Core\PartnerOperator;

use App\Http\Controllers\Bot\Core\MakeComponents;
use App\Http\Controllers\Bot\Core\PartnerOperator\Methods\PartnerOperatorMessages;
use App\Http\Controllers\Bot\Core\PartnerOperator\Methods\PartnerOperatorMethods;
use App\Http\Controllers\Bot\Core\RequestTrait;
use App\Http\Controllers\Bot\Services\CategoryService;
use App\Http\Controllers\Bot\Services\ProductService;
use App\Http\Controllers\Bot\Services\RestaurantService;
use App\Http\Controllers\Bot\Services\UserService;
use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\Request;

class PartnerOperatorRouter extends Controller
{
    use PartnerOperatorMessages;
    use PartnerOperatorMethods;
    use RequestTrait;
    use MakeComponents;

    protected $type;
    protected $message;
    protected $text;
    protected $data;
    protected $callback_query;
    protected $action;
    protected $user;

    /**
     * Client request
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

    public function routes(User $user, string $type, string $action)
    {
        $this->user = $user;
        $this->type = $type;
        $this->action = $action;
        $request = $this->request;
        $this->callback_query = $request->get('callback_query');
        $this->message = $request->get('message');

        if ($this->type == 'message'){
            return $this->messages($action);
        }
        if ($this->type == 'callback') {
            return $this->callbacks($action);
        }
    }

    public function messages($action){
        $this->text = $action;

        if ($this->user->trashed()){
            return $this->greetUserDisabled();
        }
        if ($this->user->partner_operator->status == 'inactive'){
            return $this->greetOffRestrauntEmployee();

        }

        if (isset($this->message['contact'])){
            $this->user->phone_number = $this->message['contact']['phone_number'];
            $this->user->save();
            $text = __('partner_operator.phone_saved');
            return $this->sendMessage($text);
        }

        if ($this->text == '/start') {
            return $this->sendMainPartnerOperatorMenu();
        }
        if ($this->text == '/menu') {
            return $this->sendMainPartnerOperatorMenu();
        }

        if ($this->text == __('partner_operator.keyboard_menu')) {
            return $this->sendRestaurantCategoriesMenu();
        }

//        if ($this->text == __('partner_operator.keyboard_report')) {
//            return $this->sendRestaurantReports();
//        }

        if ($this->text == __('partner_operator.keyboard_statistics')) {
            return $this->sendPartnerOperatorStatistics();
        }

        if ($this->text == __('partner_operator.keyboard_on_activated')) {
            $this->user->self_status = 'inactive';
            $this->user->save();
            $this->user->refresh();
            return $this->sendEnableRestaurantEmployeeAvailibilityMessage();
        }
        if ($this->text == __('partner_operator.keyboard_off_activated')) {
            $this->user->self_status = 'active';
            $this->user->save();
            $this->user->refresh();
            return $this->sendDisableRestaurantEmployeeAvailibilityMessage();
        }

//        if ($this->text == __('operator.keyboard_orders')) {
//            $this->sendUserOrdersByOperator();operatorMode
//        }
//        if ($this->text == __('operator.keyboard_settings')) {
//            $this->sendUserSettingsByOperator();
//        }

        // if ($action){
        // $text = __("Noma'lum buyruq berildi!");
        // $this->sendMessage($text);
        //}

    }

    public function callbacks($action) {
        //ConsoleOutput::writeln($action);


        preg_match("/^category\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendCategoryProductsMessage($tokens[1]);
        }
        preg_match("/^category_off\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->toggleCategoryStatus($tokens[1]);
        }

        preg_match("/^category_on\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->toggleCategoryStatus($tokens[1]);
        }
        preg_match("/^product_off\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->toggleProductStatus($tokens[1]);
        }
        preg_match("/^product_on\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->toggleProductStatus($tokens[1]);
        }

        if ($action === "back_to_categories") {
            $partner_operator = $this->user->partner_operator;
            $partner_operator->load('restaurant');
            $restaurant_id = $this->user->partner_operator->restaurant->id;
            return $this->sendRestaurantCategoriesMenu($restaurant_id, false);
        }

        preg_match("/^order_accept_and_move_to_cook\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->moveOrderToCookAndSendDrivers($tokens[1]);

        }

        preg_match("/^order_cancel_and_call_to_operator\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->promptMoveOrderToCanceledByRestaurantEmployeeMessage($tokens[1]);

        }
        preg_match("/^confirm_order_cancel_and_call_to_operator\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->moveOrderToCanceledByRestaurantEmployee($tokens[1]);

        }
        preg_match("/^disconfirm_order_cancel_and_call_to_operator\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->backtToMoveOrderToCanceledByRestaurantEmployee($tokens[1]);

        }

//        // order_move_to_prepared_button\/([0-9]+)
//        preg_match("/^order_move_to_prepared_button\/([0-9]+)/", $action, $tokens);
//
//        if (isset($tokens[1])) {
//            $this->moveOrderToReady($tokens);
//        }


        preg_match("/^order_update\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->updateOrder($tokens[1]);
        }

        preg_match("/^back_to_order_details\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->backToOrderDetails(order_id: $tokens[1]);
        }

        if (!$action){
            $action = '-';
        }
        return $this->answerCallbackQuery($action, false);
    }
}
