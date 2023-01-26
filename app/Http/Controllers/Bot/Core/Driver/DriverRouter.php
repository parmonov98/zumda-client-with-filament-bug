<?php

namespace App\Http\Controllers\Bot\Core\Driver;

use App\Http\Controllers\Bot\Core\Driver\Methods\DriverCallbacks;
use App\Http\Controllers\Bot\Core\Driver\Methods\DriverMessages;
use App\Http\Controllers\Bot\Core\Driver\Methods\DriverMethods;
use App\Http\Controllers\Bot\Core\MakeComponents;
use App\Http\Controllers\Bot\Core\RequestTrait;
use App\Http\Controllers\Bot\Services\CategoryService;
use App\Http\Controllers\Bot\Services\ProductService;
use App\Http\Controllers\Bot\Services\RestaurantService;
use App\Http\Controllers\Bot\Services\UserService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class DriverRouter extends Controller
{
    use DriverCallbacks;
    use DriverMessages;
    use RequestTrait;
    use MakeComponents;
    use DriverMethods;

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

    public function routes(User $user, string $type, mixed $action)
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


        if ($this->text == '/menu') {
            return $this->sendMainDriverMenu();
        }
        if ($this->text == '/start') {
            return $this->sendMainDriverMenu();
        }

        if ($this->user->trashed()){
            return $this->greetUserDisabled();
        }
        if ($this->user->status == 'inactive'){
            return $this->greetOffDriver();
        }
        if (isset($this->message['contact'])){
            $this->user->phone_number = $this->message['contact']['phone_number'];
            $this->user->save();
            $this->user->refresh();
            $text = __('driver.phone_saved');
            return $this->sendMessage($text);
        }


        if ($this->user->last_step == 'car_plate'){
            $this->user->driver->plate = $this->text;
            $this->user->driver->save();
            $this->user->driver->refresh();
            $values = [
                'last_step' => null,
            ];
            $this->userService->updateUserLastStep($this->user->telegram_id, $values);

            $text = __('driver.plate_saved');
            return $this->sendMessage($text);
        }

        if (!$this->user->driver->plate){
            $values = [
                'last_step' => 'car_plate',
            ];
            $this->userService->updateUserLastStep($this->user->telegram_id, $values);
            return $this->greetDriverPlateRequired();
        }

        if (!$this->user->phone_number){
            return $this->greetDriverPhoneRequired();
        }

        if ($this->text == __('driver.keyboard_on_activated')) {
            $this->user->driver->self_status = false;
            $this->user->driver->save();
            $this->user->driver->refresh();
            return $this->sendDisableDriverAvailibilityMessage();
        }
        if ($this->text == __('driver.keyboard_off_activated')) {
            $this->user->driver->self_status = true;
            $this->user->driver->save();
            $this->user->driver->refresh();
            return $this->sendEnableDriverAvailibilityMessage();
        }

        if ($this->text == __('driver.keyboard_statistics')) {
            return $this->sendDriverStatistics();
        }

        if ($this->text == __('driver.keyboard_on_off')) {
            return $this->sendDriverAvailibilityMessage();
        }


        // if ($action){
        // $text = __("Noma'lum buyruq berildi!");
        // $this->sendMessage($text);
        //}
    }
    public function callbacks($action) {

//         dd($action);
        //ConsoleOutput::writeln($action);

        // keyboard_switch_on\/([0-9]+)
        if ($action == 'keyboard_switch_on') {
            $this->activateDriverSelfStatus($this->user);
        }
        // keyboard_switch_on\/([0-9]+)
        if ($action == 'keyboard_switch_off') {
            $this->diactivateDriverSelfStatus($this->user);
        }
        // order_propose_and_go_to_point\/([0-9]+)
        preg_match("/^order_propose_and_go_to_point\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $this->moveOrderToDriver($tokens[1]);
        }
        // driver_i_confirm_to_accept\/([0-9]+)
        preg_match("/^driver_i_confirm_to_accept\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $this->sendDriverConfirmTheReceivedOrder(order_id: $tokens[1]);
        }
        // driver_i_disconfirm_to_accept\/([0-9]+)
        preg_match("/^driver_i_disconfirm_to_accept\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $this->sendDriverDisconfirmTheReceivedOrder(order_id: $tokens[1]);
        }
        // order_picked_and_got_on_the_way\/([0-9]+)
        preg_match("/^order_picked_and_got_on_the_way\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $this->moveOrderToPicked($tokens[1]);
        }

        // order_delivered_and_got_payment\/([0-9]+)
        preg_match("/^order_delivered_and_got_payment\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $this->moveOrderToCompleted($tokens[1]);
        }


        // order_update\/([0-9]+)
        preg_match("/^order_update\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $this->updateOrder($tokens[1]);
        }

        preg_match("/^back_to_order_details\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $this->backToOrderDetails(order_id: $tokens[1]);
        }

        if (!$action){
            $action = '-';
        }
        $this->answerCallbackQuery($action, false);
        // if ($action){
        // $text = __("Noma'lum buyruq berildi!");
        // $this->sendMessage($text);
        //}
        echo( "OK_DRIVER_CALLBACK");
    }
}
