<?php

namespace App\Http\Controllers\Bot;

use App\Facades\ConsoleOutput;
use App\Http\Controllers\Bot\Core\Administrator\AdministratorRouter;
use App\Http\Controllers\Bot\Core\Client\ClientRouter;
use App\Http\Controllers\Bot\Core\Driver\DriverRouter;
use App\Http\Controllers\Bot\Core\Operator\OperatorRouter;
use App\Http\Controllers\Bot\Core\Partner\PartnerRouter;
use App\Http\Controllers\Bot\Core\PartnerOperator\PartnerOperatorRouter;
use App\Http\Controllers\Bot\Services\CategoryService;
use App\Http\Controllers\Bot\Services\ProductService;
use App\Http\Controllers\Bot\Services\RestaurantService;
use App\Http\Controllers\Bot\Services\UserService;
use App\Http\Controllers\Bot\Core\MakeComponents;
use App\Http\Controllers\Bot\Core\RequestTrait;
use App\Models\Order;
use App\Models\PartnerOperator;
use Exception;

use Illuminate\Http\Request;

class TelegramController
{
    use RequestTrait;
    use MakeComponents;

    protected mixed $user = null;
    protected mixed $message = null;
    protected mixed $callback_query = null;

    protected UserService $userService;
    protected CategoryService $categoryService;
    protected ProductService $productService;
    protected RestaurantService $restaurantService;

    protected array $operators = [];
    protected array $administrators = [];
    protected array $drivers = [];
    protected array $developers = [];
    protected mixed $allowed_ids = [];
    protected mixed $data = null;
    protected mixed $text = null;
    protected mixed $request = null;
    protected array $restaurants = [];

    function __construct(
        Request $request,
        UserService $userService,
        RestaurantService $restaurantService,
        CategoryService $categoryService,
        ProductService $productService,
    )
    {
        $this->userService = $userService;
        $this->restaurantService = $restaurantService;
        $this->categoryService = $categoryService;
        $this->productService = $productService;
        $this->request = $request;
        $this->allowed_ids = $this->userService->getAllEmployeeIDs();
    }

    public function setWebHook()
    {
        $url = '/bot/';
        $token = config('bot.token');
        $url .= $token;
        $params = [
            'url' => url($url),
            'drop_pending_updates' => true,
        ];

        return $this->getRequest('setWebHook', $params);

        if (config('bot.debug')){
            return $this->getRequest('setWebHook', $params);
        }else{
            return 1;
        }
    }
    public function setClearWebHook()
    {
        $url = '/bot/';
        $token = config('bot.token');
        $url .= $token;
        $params = [
            'url' => url($url),
            'drop_pending_updates' => true,
        ];

        return $this->getRequest('setWebHook', $params);

        if (env('APP_ENV') !== 'production'){
            return $this->getRequest('setWebHook', $params);
        }else{
            return $this->getRequest('setWebHook', $params);
        }

    }
    public function clearMessages(Request $request)
    {
        $chat_id = config('bot.developer_id');
        $text = 'pending messages cleared!';
        if ($request->message){
            $chat_id = $request->message['from']['id'];
            $text = $request->message['text'];
        }
        if ($request->callback_query){
            $chat_id = $request->callback_query['from']['id'];
            $text = $request->callback_query['data'];
        }
        $data = [
            'chat_id' => $chat_id,
            'text' => htmlspecialchars($text),
        ];
        $this->postRequest('sendMessage', $data);
        $this->answerCallbackQueryTest($chat_id, $text);
        return response('OK_TEST');
    }
    public function getWebHook()
    {
        $params = [];

        return $this->getRequest('getWebHookInfo', $params);
        if (config('bot.debug')){
            return $this->getRequest('getWebHookInfo', $params);
        }else{
            return 1;
        }
    }

    public function index(Request $request)
    {
        $result = $request->all();
        $this->request = $request;

        file_put_contents(storage_path('bot_test.json'), json_encode($result));

//        dd($result);

        if (
            isset($result['message']) && (
                isset($result['message']['text'])
                || isset($result['message']['contact'])
                || isset($result['message']['photo'])
                || isset($result['message']['location'])
            )
        ) {

            $this->message = $result;
            $this->text = isset($result['message']['text']) ? $result['message']['text'] : null;
            $userID = $result['message']['from']['id'];

            $res = $this->setUser($userID);

            if ($res === true) {
                if ($this->text == '/error') {
                    abort(200, "505 error occured!");
                }
                $this->user->load(['operator', 'driver', 'partner', 'partner_operator']);
                if ($this->user->role == 'user') {
                    return $this->userMode($this->text);
                }

                if ($this->user->role == 'administrator') {
                    return $this->adminMode($this->text);
                }
                if ($this->user->role == 'operator') {
                    return $this->operatorMode($this->text);
                }
                if ($this->user->role == 'driver') {
                    return $this->driverMode($this->text);
                }
                if ($this->user->role == 'partner') {
                    return $this->partnerMode($this->text);
                }
                if ($this->user->role == 'partner_operator') {
                    return $this->partnerOperatorMode($this->text);
                }
                if ($this->user->role == 'developer') {
                    return $this->developerModeMessages($this->text);
                }
            } else {
                if ($this->text == '/start') {
                    return $this->sendMessage(env('TELEGRAM_BOT_DEVELOPER_ID'), $request->json());
                }
                if ($this->text != "/start") {
                    $this->setLang('uz');
                    return $this->sendMessage(__("client.no_user_found_in_database"), $parse_mode = 'HTML');
                    die;
                }
            }
        }

        if (isset($result['callback_query']) && isset($result['callback_query']['data'])) {
            $this->callback_query = $result;
            $this->data = $result['callback_query']['data'];
            $userID = $result['callback_query']['from']['id'];
            $this->setUser($userID);

            if ($this->user) {
                if ($this->user->role == 'user') {
                    return $this->userMode($this->data);
                }
                if ($this->user->role == 'administrator') {
                    return $this->adminMode($this->data);
                }
                if ($this->user->role == 'operator') {
                    return $this->operatorMode($this->data);
                }
                if ($this->user->role == 'partner') {
                    return $this->partnerMode($this->data);
                }
                if ($this->user->role == 'partner_operator') {
                    return $this->partnerOperatorMode($this->data);
                }
                if ($this->user->role == 'driver') {
                    return $this->driverMode($this->data);
                }
                if ($this->user->role == 'developer') {
                    return $this->answerCallbackQuery('developer', false);
                }
            } else {
                return $this->answerCallbackQuery(__("client.no_user_found_in_database"));
            }
        }

        return 'OK ==> admin';
    }

    public function administratorModeEditedMessages($action)
    {
        // dd($action);

        if ($this->user->last_step != null) {
            switch ($this->user->last_step) {

                case 'mailing':

                    // dd(0);
                    $this->storeMailingMessage();

                    break;
                default:
                    # code...
                    break;
            }
        }

        echo( "OK_ADMIN_MESSAGE");

        if (isset($this->message['photo'])) {

            $text = $this->printArray($this->message['photo']);

            $res = $this->sendMessage($text, $parse_mode = 'HTML');
            die;
        }

    }

    public function userMode($action)
    {
        if ($this->message){
            return app(ClientRouter::class)->routes($this->user, 'message', $action);
        }
        if($this->callback_query){
            return app(ClientRouter::class)->routes($this->user, 'callback', $action);
        }
        return response("exit_client");
    }
    public function partnerMode($action)
    {
        if ($this->message){
            return app(PartnerRouter::class)->routes($this->user, 'message', $action);
        }
        if($this->callback_query){
            return app(PartnerRouter::class)->routes($this->user, 'callback', $action);
        }
        return response("exit_partner");
    }
    public function partnerOperatorMode($action)
    {
        if ($this->message){
            return app(PartnerOperatorRouter::class)->routes($this->user, 'message', $action);
        }
        if($this->callback_query){
            return app(PartnerOperatorRouter::class)->routes($this->user, 'callback', $action);
        }
        return response("exit_partner_operator");
    }
    public function operatorMode($action)
    {
        if ($this->message){
            $res = app(OperatorRouter::class)->routes($this->user, 'message', $action);
        }
        if($this->callback_query){
            $res = app(OperatorRouter::class)->routes($this->user, 'callback', $action);
        }
        return $res;
    }
    public function driverMode($action)
    {
        if ($this->message){
            return app(DriverRouter::class)->routes($this->user, 'message', $action);
        }
        if($this->callback_query){
            return app(DriverRouter::class)->routes($this->user, 'callback', $action);
        }
    }
    public function adminMode($action)
    {
        if ($this->message){
            $res = app(AdministratorRouter::class)->routes($this->user, 'message', $action);
        }
        if($this->callback_query){
            $res = app(AdministratorRouter::class)->routes($this->user, 'callback', $action);
        }
        return $res;
    }
    function printArray($arr, $pad = 0, $padStr = "\t")
    {
        $outerPad = $pad;
        $innerPad = $pad + 1;
        $out = '[' . PHP_EOL;
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $out .= str_repeat($padStr, $innerPad) . $k . ' => ' . $this->printArray($v, $innerPad) . PHP_EOL;
            } else {
                $out .= str_repeat($padStr, $innerPad) . $k . ' => ' . $v;
                $out .= PHP_EOL;
            }
        }
        $out .= str_repeat($padStr, $outerPad) . ']';
        return $out;
    }
}
