<?php

namespace App\Http\Controllers\Bot\Core\Client;

use App\Http\Controllers\Bot\Core\Client\Methods\ClientCallbacks;
use App\Http\Controllers\Bot\Core\Client\Methods\ClientMessages;
use App\Http\Controllers\Bot\Core\Driver\Methods\DriverMessages;
use App\Http\Controllers\Bot\Core\MakeComponents;
use App\Http\Controllers\Bot\Core\Operator\Methods\OperatorMessages;
use App\Http\Controllers\Bot\Core\Partner\Methods\PartnerMessages;
use App\Http\Controllers\Bot\Core\PartnerOperator\Methods\PartnerOperatorMessages;
use App\Http\Controllers\Bot\Core\PartnerOperator\Methods\PartnerOperatorMethods;
use App\Http\Controllers\Bot\Core\RequestTrait;
use App\Http\Controllers\Bot\Services\CategoryService;
use App\Http\Controllers\Bot\Services\ProductService;
use App\Http\Controllers\Bot\Services\RestaurantService;
use App\Http\Controllers\Bot\Services\UserService;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Partner;
use App\Models\PartnerOperator;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\Request;

class ClientRouter extends Controller
{

    use RequestTrait;
    use MakeComponents;
    use ClientMessages;
    use ClientCallbacks;
    use OperatorMessages;
    use DriverMessages;
    use PartnerMessages;
    use PartnerOperatorMessages;

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

    protected function messages($text){

        // step01
        $this->text = $text;
        if ($text == '/start') {
            $this->greetUser($this->request);
            return false;
        }
        if ($this->user->last_step != null) {
            // storing customer_review
            if ($this->user->last_step === 'customer_review') {
                if ($text == __('client.user_settings_cancel_button')){
                    $this->cancelEditingUserSettings();
                    $this->sendMainUserMenu();
                }else{
                    $this->storeCustomerReview($text);
                }
                return false;
            }


            if ($this->user->last_step === 'channel_subscription_confirmed') {
                return $this->sendStoreUserContactAndFindInDatabase($this->message['contact'] ?? null);
            }

            if ($this->user->last_step === 'phone_number_saved') {
                return $this->sendStoreUserName($text);
            }

            // updating user name
            if ($this->user->last_step === 'user_name') {
                if ($text == __('client.user_settings_cancel_button')){
                    $this->cancelEditingUserSettings();
                    return $this->sendUserSettings();
                }else{
                    return $this->updateUserName($text);
                }
            }

            // updating user phone
            if ($this->user->last_step === 'user_phone') {
                if ($text == __('client.user_settings_cancel_button')){
                    $this->cancelEditingUserSettings();
                    return $this->sendUserSettings();
                }else{
                    return $this->updateUserContact($this->message['contact'] ?? null);
                }
            }


            // start of ordering
            if ($this->user->last_step === 'order_confirmation'){
                if ($text == __('client.order_cancel_button')) {
                    return $this->cancelOrderByClient();
                }else{
                    if (isset($this->message['location'])){
                        return $this->storeOrderLocationByClient();
                    }else{
                        return $this->storeOrderAddressByClient($text);
                    }
                }
            }

            // storing phone number of contact object
            if ($this->user->last_step === 'confirm_order_location_or_address'){
                if ($text == __('client.order_cancel_button')) {
                    return $this->cancelOrderByClient();
                }else{
                    if (isset($this->message['contact'])){
                        return $this->storeOrderContact($this->message['contact']);
                    }else{
                        return $this->storeOrderContact();
                    }
                }
            }

            // storing order note
            if ($this->user->last_step === 'order_phone_number'){
                if ($text == __('client.order_cancel_button')) {
                    return $this->cancelOrderByClient();
                }else{
                    return $this->acceptCustomerNote();
                }
            }


            if ($this->user->last_step === 'restaurant_selected'){
                if ($text == __('client.keyboard_restaurants_back_to_restaurants')) {
                    return $this->sendRestaurantsMenu();
                }else{
                    return $this->sendCategoryItemsMenuForClientAsKeyboard($this->user->last_value, $text);
                }
            }

            if ($this->user->last_step === 'restaurant_category_selected'){
                if ($text == __('client.keyboard_restaurants_back_to_restaurants_categories')) {
                    $category = Category::find($this->user->last_value);
                    $category->load('restaurant');
                    return $this->sendRestaurantCategoriesMenuForClientAsKeyboard($category->restaurant->id, true);
                }else{
                    $category = Category::find($this->user->last_value);
                    $category->load('products.translation');
                    $product = $category->products->first(fn($item) => $item->translation?->name === $text);
                    if (!$product instanceof Product){
                        return $this->sendMessage(__("client.no_product_found"));
                    }
                    return $this->viewProductByClient($product->id);
                }
            }


        }

        if ($text == __("client.user_settings_cancel_button")) {
            return $this->sendMainUserMenu();
        }

        if ($text == '/menu') {
            return $this->sendMainUserMenu();
        }

        if ($text == __('client.keyboard_menu')) {
            return $this->sendMainUserMenuOptions();
        }

        if ($text == __('client.back_to_main_menu_as_back_button')) {
            return $this->sendMainUserMenu();
        }

        if ($text == __('client.keyboard_restaurants')) {
            return $this->sendRestaurantsMenuForClient();
        }

        if ($text == __('client.keyboard_meals')) {
            return $this->sendCommonCategoriesMenuForClient();
        }

        if ($text == __('client.keyboard_contact')) {
            return $this->sendUserContacts();
        }

        if ($text == __('client.keyboard_review')) {
            return $this->sendCustomerReview();
        }

        if ($text == __('client.keyboard_settings')) {
            return $this->sendUserSettings();
        }


        if ($text == __('client.keyboard_cart')) {
            return $this->sendCartItemsByClient();
        }


        $restaurant = Restaurant::whereName($text)->first();

        if ($restaurant instanceof  Restaurant){
            return $this->sendRestaurantCategoriesMenuForClientAsKeyboard($restaurant->id, true);
        }

        $restored_user = User::query()->where('telegram_id', $this->message['from']['id'])->where('deleted_at', '!=', null)->first();
        if ($restored_user instanceof User){
            $user = $restored_user;
            $user->restore();
            $user->refresh();
        }else{
            $user_by_TG_ID = User::query()->where('telegram_id', $this->message['from']['id'])->first();
            $user = $user_by_TG_ID;
        }

        $name = $this->message['from']['first_name'] ?? '-';
        $tg_id = $this->message['from']['id'] ?? null;
        $operator = $this->userService->getOperatorByActivationCode($text);
        if ($operator instanceof Operator){

            $text = __('operator.welcome_to_the_team', [
                'name' => htmlspecialchars($name),
                'role' => __("Operator")
            ]);

            $operator->username = $this->message['from']['username'] ?? null;
            $operator->telegram_id = $tg_id;
            $operator->activation_code_used = true;
            $operator->self_status = true;
            $operator->save();
            $operator->refresh();
            $user->operator_id = $operator->id;
            $user->role = 'operator'; // hard coded role

            $user->save();

            $markup = $this->getMainOperatorMenu();
            return $this->sendMessage($text, 'HTML', $markup);
        }

        $driver = $this->userService->getDriverByActivationCode($text);
        if ($driver instanceof Driver){
            $text = __("driver.welcome_to_the_team", [
                'name' => htmlspecialchars($name),
                'role' => __("Driver")
            ]);
            $driver->telegram_id = $tg_id;
            $driver->username = $this->message['from']['username'] ?? null;
            $driver->activation_code_used = true;
            $driver->self_status = true;
            $driver->save();
            $driver->refresh();
            $user->driver_id = $driver->id;
            $user->role = 'driver'; // hard coded role
            $user->save();

            $markup = $this->getMainDriverMenu($driver);
            return $this->sendMessage($text, 'HTML', $markup);
        }

        $partner_operator = $this->userService->getPartnerOperatorByActivationCode($text);
        if ($partner_operator instanceof PartnerOperator){

            $text = __('partner_operator.welcome_to_the_team', [
                'name' => htmlspecialchars($name),
                'role' => __("Partner Operator")
            ]);
            $partner_operator->username = $this->message['from']['username'] ?? null;
            $partner_operator->telegram_id = $tg_id;
            $partner_operator->activation_code_used = true;
            $partner_operator->self_status = true;
            $partner_operator->save();
            $partner_operator->refresh();
            $user->partner_operator_id = $partner_operator->id;
            $user->role = 'partner_operator'; // hard coded role
            $user->save();

            $markup = $this->getMainPartnerOperatorMenu($partner_operator);
            return $this->sendMessage($text, 'HTML', $markup);

        }

        $partner = $this->userService->getPartnerByActivationCode($text);
        if ($partner instanceof Partner){

            $text = __('partner.welcome_to_the_team', [
                'name' => htmlspecialchars($name),
                'role' => __("Partner")
            ]);
            $partner->username = $this->message['from']['username'] ?? null;
            $partner->telegram_id = $tg_id;
            $partner->activation_code_used = true;
            $partner->self_status = true;
            $partner->save();
            $partner->refresh();
            $user->partner_id = $partner->id;
            $user->role = 'partner'; // hard coded role
            $user->save();

            $markup = $this->getMainPartnerMenu($user);
            return $this->sendMessage($text, 'HTML', $markup);

        }


        // if ($action){
        // $text = __("Noma'lum buyruq berildi!");
        // $this->sendMessage($text);
        //}
    }

    public function callbacks($action)
    {
        $res = null;

        // step 02
        if (in_array($action, ['uz', 'en', 'ru'])) {
            $res = $this->updateUserLanguage($action);
            if ($res === true){
                $text = __("client.force_client_to_subscribe_to_channel");
                $message_id = $this->callback_query['message']['message_id'];
                $options = [
                    [
                        [
                            'text' => __("client.go_to_channel"),
                            'url' => 'https://t.me/' . config('bot.TELEGRAM_BOT_CHANNEL'),
                        ]
                    ],
                    [
                        [
                            'text' => __("client.check_if_client_subscribed_to_channel_button"),
                            'callback_data' => __("check_subscription"),
                        ]
                    ]
                ];
                $reply_markup = $this->getInlineKeyboard($options);
                $res = $this->editMessageText($message_id, $text, 'HTML', $reply_markup);
            }
            return $res;
        }

        // step 03
        if($action === 'check_subscription') {
            $res = $this->checkIfUserSubscribedToChannel();
            if ($res){
                $text = __("client.enter_your_phone_number_or_send_as_contact");

                $options = [
                    [
                        [
                            'text' => __('client.enter_your_phone_number_or_send_as_contact_button'),
                            'request_contact' => true
                        ]
                    ],
                ];
                $reply_markup = $this->getKeyboard($options, true);
                $message_id = $this->callback_query['message']['message_id'];
                $chat_id = $this->callback_query['from']['id'];
                $this->deleteMessage($chat_id, $message_id);
                return $this->sendMessage($text, 'HTML', $reply_markup);
            }else{
                $text = __("client.not_subscribed");
                return $this->answerCallbackQuery($text, true);
            }
        }

        if($action === 'back_restaurants_menu') {
            $res = $this->sendRestaurantsMenuForClient(is_edit: true);
        }
        if ($action === 'back_to_restaurants_from_restaurants_categories') {
            return $this->sendRestaurantsMenu(is_edit: true);
        }
//        if ($action === 'back_restaurants_menu_from_restaurants') {
//            $res = $this->sendCommonCategoriesMenuForClient(is_edit: true);
//        }// back_restaurants_menu_from_restaurants

        // main
        if ($action === 'main') {
            $this->answerCallbackQuery();
            $res = $this->sendMainUserMenu();
        }

        /* settings of users */
        // back to settings
        if ($action === 'back_to_user_settings') {
            $res = $this->backToUserSettings();
        }

        // changing name
        if ($action === 'name') {
            $res = $this->editUserName();
        }


        // changing phone
        if ($action === 'phone') {
            $res = $this->editUserPhone();
        }
        // changing lang
        if ($action === 'lang') {
            $res = $this->editUserLanguages();
        }

        // common_category\/([0-9]+)
        preg_match("/^common_category\/([0-9]+)$/", $action, $tokens);
        if (isset($tokens[1])) {
            $res = $this->sendCommonCategoryRestaurantsMenuForClient($tokens[1]);
        }
//        // back_restaurants_menu_from_common_category\/([0-9]+)
//        preg_match("/^back_restaurants_menu_from_common_category", $action, $tokens);
//        if (isset($tokens[1])) {
//            $this->sendCommonCategoriesMenuForClient(is_edit: true);
//        }
//        // back_restaurants_menu_from_common_category\/([0-9]+)
//        preg_match("/^back_restaurants_menu_from_common_category\/([0-9]+)$/", $action, $tokens);
//        if (isset($tokens[1])) {
//            $this->sendCommonCategoriesMenuForClient($tokens[1]);
//        }
        // restaurant\/([0-9]+)
        preg_match("/^restaurant\/([0-9]+)$/", $action, $tokens);
        if (isset($tokens[1])) {
            $res = $this->sendRestaurantCategoriesMenuForClient($tokens[1]);
        }
        // restaurant\/([0-9]+)\/category\/([0-9]+)
        preg_match("/^restaurant\/([0-9]+)\/category\/([0-9]+)$/", $action, $tokens);
        if (isset($tokens[1])) {
            $res = $this->sendCategoryItemsMenuForClient($tokens[1], $tokens[2]);
        }
        // restaurant\/([0-9]+)\/category\/([0-9]+)\/product\/([0-9]+)
        preg_match("/^restaurant\/([0-9]+)\/category\/([0-9]+)\/product\/([0-9]+)$/", $action, $tokens);
        if (isset($tokens[1])) {
            $res = $this->processProductByClient($product_id  = $tokens[3]);
        }


        // back_to_product_list\/([0-9]+)
        preg_match("/^back_to_product_list\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $res = $this->backToProductList($tokens[1]);
        }

        // ^add_to_cart\/([0-9]+)\/([0-9]+)
        preg_match("/^add_to_cart\/([0-9]+)\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $res = $this->addToCartByClient($tokens);
        }

        if ($action == 'go_to_cart') {
            $res = $this->sendCartItemsByClient($is_inline = true);
        }

        if ($action == 'clear_the_cart') {
            $res = $this->clearUserCartByClient();
        }

        preg_match("/^decrement_cart_item\/([0-9]+)\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $this->decrementCartItemByOperator($tokens);
        }
        preg_match("/^increment_cart_item\/([0-9]+)\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $res = $this->incrementCartItemByOperator($tokens);
        }
        preg_match("/^decrement_cart_item_by_10x\/([0-9]+)\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $this->decrementCartItemBy10XByOperator($tokens);
        }
        preg_match("/^increment_cart_item_by_10x\/([0-9]+)\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $res = $this->incrementCartItemBy10XByOperator($tokens);
        }
        if ($action == 'confirm_the_order') {
            $res = $this->confirmOrderByClient();
        }

        if ($action == 'confirm_the_received_address') {
            if ($this->user->last_step != null) {
//                $this->confirmOrderLandmarkByOperator();
                $res = $this->confirmOrderAddressOrLocation();
            }
        }

        if ($action == 'disconfirm_the_received_address') {
            if ($this->user->last_step != null) {
                $res = $this->disconfirmOrderAddressOrLocation();
            }
        }

        // back_restaurant\/([0-9]+)
        preg_match("/^back_restaurant\/([0-9]+)$/", $action, $tokens);
        if (isset($tokens[1])) {
            $res = $this->sendRestaurantViewMenuForAdmin($tokens[1]);
        }
        // back_to_main_category_menu_from_product\/([0-9]+)
        preg_match("/^back_to_main_category_menu_from_product\/(?:[0-9]+)?/", $action, $tokens);
        if (isset($tokens[0])) {
            $restaurant_id = null;
            if (isset($tokens[1]))
                $restaurant_id = $tokens[1];
            $res = $this->backToMainCategoryMenuByClient($restaurant_id);
        }
        return $res;
//        dd($action);
        // if ($action){
        // $text = __("Noma'lum buyruq berildi!");
        // $this->sendMessage($text);
        //}
    }
}
