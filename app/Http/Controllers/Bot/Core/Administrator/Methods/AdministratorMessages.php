<?php

namespace App\Http\Controllers\Bot\Core\Administrator\Methods;

use App\Exports\DriversReportExport;
use App\Exports\OrdersExport;
use App\Models\Category;
use App\Models\Order;
use App\Models\PartnerOperator;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

trait AdministratorMessages
{
    public function getMainAdminMenuKeyboard()
    {
        $options = [
             [
                 ['text' => __('admin.admin_keyboard_restaurants')],
                 ['text' => __('admin.admin_keyboard_users')],
             ],
             [
                 ['text' => __('admin.admin_keyboard_reports')],
             ],
            [
                ['text' => __('admin.admin_keyboard_clients_as_json')],
                ['text' => __('admin.admin_keyboard_statistics')]
            ]
        ];
        return $this->getKeyboard($options, $resize = true);
    }

    public function getAdminReportsMenuKeyboard()
    {
        $options = [
             [
                 ['text' => __('admin.admin_keyboard_order_reports')],
                 ['text' => __('admin.admin_keyboard_driver_reports')],
             ],
            [
                ['text' => __('admin.admin_keyboard_back_to_home')]
            ]
        ];
        return $this->getKeyboard($options, $resize = true);
    }


    public function sendUsersMenuForAdmin($text = '', $is_edit = false)
    {


        $keyboard = [
            [
                [
                    'text' => __('admin.users_keyboard_operators'),
                    'callback_data' => 'operators'
                ]
            ],
            [
                [
                    'text' => __('admin.users_keyboard_drivers'),
                    'callback_data' => 'drivers'
                ],
            ],
//            [
//                [
//                    'text' => __('admin.users_keyboard_partners'),
//                    'callback_data' => 'partners'
//                ]
//            ],
            [
                [
                    'text' => __('admin.users_keyboard_partner_operators'),
                    'callback_data' => 'partner_operators'
                ]
            ],
            [
                [
                    'text' => __('admin.users_add_new_user'),
                    'callback_data' => 'add_new_user'
                ],
            ]
        ];

        if ($text == ''){
            $text = __('admin.users_in_the_system');
        }
        $markup = $this->getInlineKeyboard($keyboard);

        if ($is_edit){
            return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
        }else{
            return $this->sendMessage($text, 'HTML', $markup);

        }
    }

    public function sendClientsAsJsonCalendarMenuForAdmin($text = '', $is_edit = false)
    {

        $data = [
            'last_step' => 'get_client_as_json'
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $data);
        if ($text == ''){
            $text = __('admin.select_a_year_for_from_date');
        }
        $options = [
          [[
              'text' => __("admin.cancel_clients_report_button")
          ]]
        ];

        if ($is_edit){
            $markup = $this->getInlineKeyboard($options);

            return $this->editMessageText($this->callback_query['message']['message_id'], $text, 'HTML', $markup);
        }else{
            $markup = $this->getKeyboard($options, true);
            return  $this->sendMessage($text, 'HTML', $markup);
        }
    }

    public function sendAllUsersMenuForAdmin($text = '')
    {

        $users = $this->userService->getUsers(['user', 'administrator']);

        $inline = [];
        if (count($users) > 0) {
            $row = intval(ceil(count($users) / 2));

            $j = 0;
            $k = 0;
            $users = $users->toArray();
            for ($i = 0; $i < count($users); $i++) {
                $item = $users[$i];
                if (!$item['first_name']) continue;
                if (isset($inline[$j]) && count($inline[$j]) == 2) {
                    $k = 0;
                    $j++;
                    $inline[$j][$k]['text'] = $item['first_name'] . '('. $item['role'] .')';
                    $inline[$j][$k]['callback_data'] = 'user/' . $item['id'];
                } else {
                    $inline[$j][$k]['text'] = $item['first_name'] . '('. $item['role'] .')';
                    $inline[$j][$k]['callback_data'] = 'user/' . $item['id'];
                }
                $k++;
            }

            $inline[count($inline)][0] = [
                'text' => __('admin.users_add_new_user'),
                'callback_data' => 'add_new_user'
            ];

            if (!$text){
                $text = __('admin.users_main_page');
//                $text = __('admin.main_restaurants', [
//                    'menu_link' => env('BOT_MENU_LINK')
//                ]);
            }

            $markup = $this->getInlineKeyboard($inline);

            $res = $this->sendMessage($text, 'HTML', $markup);

        } else {
            $text = __('admin.no_user_found');

            $inline[0][0] = [
                'text' => __('admin.users_add_new_user'),
                'callback_data' => 'add_new_user'
            ];

            $markup = $this->getInlineKeyboard($inline);

            $res = $this->sendMessage($text, 'HTML', $markup);

        }
        return $res;
    }

    public function sendMainMenuForAdmin($text = '')
    {
        $values = [
            'last_step' => null,
            'last_value' => null,
            'last_message_id' => null,
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);

        $menu = $this->getMainAdminMenuKeyboard();

        if ($text == '')
            $text = __("admin.welcome");

        return $this->sendMessage($text, 'HTML', $menu);
    }
    public function sendStatistics()
    {

        $orders = Order::whereDate('created_at', Carbon::today())
            ->withTrashed()
            ->where('status', 'completed')
            ->get();

        // dd($orders);

        $orders_sum = $orders->pluck('summary')->sum();
        $deliveries_sum = $orders->pluck('shipping_price')->sum();
        // $orders_sum = array_sum($orders_sum);

        $text = '';
        $text .= __('admin.daily_report', [
            'orders_qty' => $orders->count(),
            'orders_sum' => number_format($orders_sum, 0, '.', ','),
            'delivery_sum' => number_format($deliveries_sum, 0, '.', ','),
        ]);

        $orders = Order::whereMonth('created_at', date('m'))
            ->withTrashed()
            ->whereYear('created_at', date('Y'))
            ->where('status', 'completed')
            ->get();

        // dd($orders);

        $orders_sum = $orders->pluck('summary')->sum();
        $deliveries_sum = $orders->pluck('shipping_price')->sum();


        $text .= __('admin.monthly_report_with_objects', [
            'orders_qty' => $orders->count(),
            'orders_sum' => number_format($orders_sum, 0, '.', ','),
            'delivery_sum' => number_format($deliveries_sum, 0, '.', ','),
        ]);
        $text .= __('admin.restaurant_objects_in_numbers', [
            'operators_qty' => User::where('role', 'operator')->count(),
            'drivers_qty' => User::where('role', 'driver')->count(),
            'restaurants_qty' => Restaurant::count(),
        ]);

        return $this->sendMessage($text, $parse_mode = 'HTML');
    }
    public function sendOperatorStaistics()
    {

        $orders = Order::whereDate('created_at', Carbon::today())
            ->withTrashed()
            ->where('status', 'completed')
            ->where('user_id', $this->user->id)
            ->get();

        $orders_sum = $orders->pluck('summary')->sum();
        $deliveries_sum = $orders->pluck('shipping_price')->sum();
        // $orders_sum = array_sum($orders_sum);

        $text = '';
        $text .= __('operator.daily_report', [
            'orders_qty' => $orders->count(),
            'orders_sum' => number_format($orders_sum, 0, '.', ','),
            'delivery_sum' => number_format($deliveries_sum, 0, '.', ','),
        ]);

        $orders = Order::whereMonth('created_at', date('m'))
            ->withTrashed()
            ->where('user_id', $this->user->id)
            ->whereYear('created_at', date('Y'))
            ->where('status', 'completed')
            ->get();

        // dd($orders);

        $orders_sum = $orders->pluck('summary')->sum();
        $deliveries_sum = $orders->pluck('shipping_price')->sum();


        $text .= __('operator.annual_report', [
            'orders_qty' => $orders->count(),
            'orders_sum' => number_format($orders_sum, 0, '.', ','),
            'delivery_sum' => number_format($deliveries_sum, 0, '.', ','),
        ]);

        return $this->sendMessage($text, $parse_mode = 'HTML');
    }

    public function enableDriverAvailibility()
    {
        $text = __('driver.availability_text_keyboard_smile');
        $markup = $this->getMainDriverMenu($this->user);
        return $this->sendMessage($text, 'HTML', $markup);
    }

    public function sendReportsMenu()
    {
        $menu = $this->getAdminReportsMenuKeyboard();
        return $this->sendMessage(__("Hisobotni yuklash uchun quyidagi tugamalardan foydalaning!"), 'HTML', $menu);
    }
    public function sendOrderReports()
    {
        $file_name = 'public/order-' . Carbon::now()->format('Y-m-d-h-m-s') . '.xlsx';
        Excel::store(new OrdersExport(),  $file_name);
        $path = Storage::disk('local')->path($file_name);
        $text = $file_name;
        return $this->sendDocument($this->message['from']['id'], $path, $text);
    }
    public function sendDriverReports()
    {
        $file_name = 'public/drivers-' . Carbon::now()->format('Y-m-d-h-m-s') . '.xlsx';
        Excel::store(new DriversReportExport(),  $file_name);
        $path = Storage::disk('local')->path($file_name);
        $text = $file_name;
        return $this->sendDocument($this->message['from']['id'], $path, $text);
    }


    public function storeMailingMessage()
    {

        $message = null;
        if (isset($this->message) && isset($this->message['photo'])) {
            $message = $this->message;
        }
        if (isset($this->message['edited_message']) && isset($this->message['edited_message']['photo'])) {
            $message = $this->message['edited_message'];
        }

        if ($message == null || !isset($message['photo'])) {
            $text = __('admin.invalid_mailing_content');
            return $this->sendMessage($text, $parse_mode = 'HTML');
        }
         // dd($this->message['photo']);
            // ['video', 'photo', 'text', 'audio', 'voice', 'animation', 'document'];
        $file_id = end($message['photo'])['file_id'];
        $entities = [];
        $caption = null;
        if (isset($message['caption'])) {
            $caption = $message['caption'];
        }
        if (isset($message['caption_entities'])) {
            $entities = $message['caption_entities'];
        }
        $messageData = [
            'type' => 'photo',
            'content' => $file_id,
            'text' => $caption,
            'entities' => json_encode($entities)
        ];
        // dd($message);

        $mailing = $this->user->messages()->create($messageData);
        // dd($mailing);

        $data = [];

        // dd($message);
        $chat_id = $message['from']['id'];

        $options = [
            [
                [
                    'text' => __('admin.start_mailing'),
                    'callback_data' => 'start_mailing/' . $mailing->id
                ]
            ]
        ];

        $text = $this->generateHTMLText($caption, $entities);

        // dd($text);

        $markup = $this->getInlineKeyboard($options);
        $res = $this->sendPhoto($chat_id, $file_id, htmlspecialchars($text), $markup);

        $values = [
            'last_step' => null,
            'last_value' => null
        ];
        $this->userService->updateUserLastStep($this->user->telegram_id, $values);
//        dd($res);

        $text = __('admin.invalid_mailing_content');
        return $this->sendMessage($text, $parse_mode = 'HTML');
    }

    public function sendStoreNewRestaurantNameMessage($action){
        if ($action == __('admin.restaurants_cancel_creating') || $action == '/cancel'){
            $res = $this->cancelCreatingNewRestaurant();
        }else{
            $data = [
                'name' => $action,
                'lang' => 'uz'
            ];
            $restaurant = $this->restaurantService->addRestaurantName($data);
            if(!$restaurant instanceof Restaurant){
                $text = __('admin.restaurants_new_restaurant_duplicate_error');
                return $this->sendMessage($text, 'HTML');
            }

            $values = [
                'last_step' => 'add_new_restaurant_address',
                'last_value' => $restaurant->id
            ];

            $this->userService->updateUserLastStep($this->user->telegram_id, $values);

            $text = __('admin.restaurants_add_new_restaurant_address');

            $res = $this->sendMessage($text, 'HTML');
        }
        return $res;
    }


    public function sendStoreNewCategoryNameMessage($action){
        if ($action == __('admin.categories_cancel_creating') || $action == '/cancel'){
            $res = $this->cancelCreatingNewCategory();
        }else{
            $data = [
                'name' => $action,
                'lang' => 'uz'
            ];
            $category = $this->categoryService->addCategoryName($this->user->last_value, $data);
//            addslashes
            if(!$category instanceof Category){
                $text = __('admin.categories_new_category_duplicate_error');
                return $this->sendMessage($text, 'HTML');
            }

            $values = [
                'last_step' => 'add_new_category_description',
                'last_value' => $category->id
            ];

            $this->userService->updateUserLastStep($this->user->telegram_id, $values);

            $text = __('admin.categories_enter_a_description_for_new_category');

            $res = $this->sendMessage($text, 'HTML');
        }
        return $res;
    }

    public function sendStoreNewProductNameMessage($action){
        if ($action == __('admin.products_cancel_creating') || $action == '/cancel'){
            $res = $this->cancelCreatingNewProduct();
        }else{
            $data = [
                'name' => $action,
                'lang' => 'uz'
            ];
//            dd($data);
            $product = $this->productService->addProductName($this->user->last_value, $data);
//            addslashes
            if(!$product instanceof Product){
                $text = __('admin.products_new_product_duplicate_error');
                return $this->sendMessage($text, 'HTML');
            }

            $values = [
                'last_step' => 'add_new_product_photo',
                'last_value' => $product->id
            ];

            $this->userService->updateUserLastStep($this->user->telegram_id, $values);

            $text = __('admin.products_enter_a_photo_for_new_product');

            $res = $this->sendMessage($text, 'HTML');
        }
        return $res;
    }

    public function sendStoreNewProductPhotoMessage($action){
        if ($action == __('admin.products_cancel_creating') || $action == '/cancel'){
            $res = $this->cancelCreatingNewProduct();
        }else{

            if(!isset($this->message['photo'])){
                $text = __('admin.products_new_product_content_error');
                return $this->sendMessage($text, 'HTML');
            }
            $last = end($this->message['photo']);
            $data = [
                'photo_id' => $last['file_id'],
            ];
            $product = $this->productService->addProductPhoto($this->user->last_value, $data);
//            dd($product);
            if(!$product instanceof Product){
                $text = __('admin.products_new_product_duplicate_error');
                return $this->sendMessage($text, 'HTML');
            }


            $values = [
                'last_step' => 'add_new_product_price',
                'last_value' => $product->id
            ];

            $this->userService->updateUserLastStep($this->user->telegram_id, $values);

            $text = __('admin.products_enter_price_for_new_product');

            $res = $this->sendMessage($text);
        }
        return $res;
    }

    public function sendStoreNewProductPriceMessage($action){
        if ($action == __('admin.products_cancel_creating') || $action == '/cancel'){
            $res = $this->cancelCreatingNewProduct();
        }else{
            if (!is_numeric($action)) {
                $text = __('admin.products_new_product_price_error');
                return $this->sendMessage($text, 'HTML');
            }
            $data = [
                'price' => $action,
            ];
            $product = $this->productService->addProductPrice($this->user->last_value, $data);
            if(!$product instanceof Product){
                $text = __('admin.products_new_product_duplicate_error');
                return $this->sendMessage($text, 'HTML');
            }

            $values = [
                'last_step' => 'add_new_product_description',
                'last_value' => $product->id
            ];

            $this->userService->updateUserLastStep($this->user->telegram_id, $values);


//            $text = $this->getEditProductText($product);
//            $markup = $this->getEditProductKeyboard($product);
//            $this->sendPhoto($this->user->telegram_id, $product->photo_id, $text, $markup);

//            $text = __('admin.products_creating_product_done_successfully');
            $text = __('admin.products_enter_description_for_new_product');
            $res = $this->sendMessage($text);
        }
        return $res;
    }
    public function sendStoreNewDescriptionMessage($action){
        if ($action == __('admin.products_cancel_creating') || $action == '/cancel'){
            $res = $this->cancelCreatingNewProduct();
        }else{
            $data = [
                'description' => $action,
            ];
            $product = $this->productService->addProductDescription($this->user->last_value, $data);
            if(!$product instanceof Product){
                $text = __('admin.products_new_product_duplicate_error');
                return $this->sendMessage($text, 'HTML');
            }

            $this->userService->resetSteps($this->user->telegram_id);

            $text = $this->getEditProductText($product);
            $markup = $this->getEditProductKeyboard($product);
            $this->sendPhoto($this->user->telegram_id, $product->photo_id, $text, $markup);

            $text = __('admin.products_creating_product_done_successfully');
            $res = $this->sendMainMenuForAdmin($text);
        }
        return $res;
    }

    public function sendStoreNewCategoryDescriptionMessage($action){

        if ($action == __('admin.categories_cancel_creating') || $action == '/cancel'){
            $res = $this->cancelCreatingNewCategory();
        }else{
            $data = [
                'description' => $action,
            ];
            $category = $this->categoryService->addCategoryDescription($this->user->last_value, $data);

            if(!$category instanceof Category){
                $text = __('admin.categories_new_category_duplicate_error');
                return $this->sendMessage($text, 'HTML');
            }

            $this->userService->resetSteps($this->user->telegram_id);

            $text = $this->getEditCategoryText($category);
            $markup = $this->getEditCategoryKeyboard($category);

            $this->sendMessage($text, 'HTML', $markup);
            $text = __('admin.categories_add_new_category_done');
            $res = $this->sendMainMenuForAdmin($text);
        }
        return $res;
    }

    public function sendStoreNewRestaurantAddressMessage($action){
        if ($action == __('admin.restaurants_cancel_creating') || $action == '/cancel'){
            $res = $this->cancelCreatingNewRestaurant();
        }else{

            $data = [
                'address' => $action,
            ];
            $restaurant_id = $this->user->last_value;
            $restaurant = $this->restaurantService->addRestaurantAddress($restaurant_id, $data);
            if(!$restaurant instanceof Restaurant){
                $text = __('admin.restaurants_new_restaurant_duplicate_error');
                return $this->sendMessage($text, 'HTML');
            }

            $values = [
                'last_step' => 'add_new_restaurant_location',
                'last_value' => $restaurant->id
            ];

            $this->userService->updateUserLastStep($this->user->telegram_id, $values);

            $text = __('admin.restaurants_add_new_restaurant_location');

            $res = $this->sendMessage($text, 'HTML');
        }
        return $res;
    }

    public function sendStoreNewRestaurantLocationMessage($action){
        if ($action == __('admin.restaurants_cancel_creating') || $action == '/cancel'){
            $res = $this->cancelCreatingNewRestaurant();
        }else{

            if (!isset($this->message['location']) && !str_contains($action, ',')){
                $text = __('admin.restaurants_new_restaurant_location_not_found');
                return $this->sendMessage($text, 'HTML');
            }

            if (str_contains($action, ',')){
                $pieces = explode(',', $action);
                $data = [
                    'latitude' => $pieces[0],
                    'longitude' => $pieces[1],
                ];
            }elseif (isset($this->message['location'])){
                $data = [
                    'latitude' => $this->message['location']['latitude'],
                    'longitude' => $this->message['location']['longitude'],
                ];
            }else{
                $text = __('admin.restaurants_new_restaurant_location_not_found');
                return $this->sendMessage($text, 'HTML');
            }

            $restaurant_id = $this->user->last_value;
            $restaurant = $this->restaurantService->addRestaurantLocation($restaurant_id, $data);
            if(!$restaurant instanceof Restaurant){
                $text = __('admin.restaurants_new_restaurant_duplicate_error');
                return $this->sendMessage($text, 'HTML');
            }

//            $values = [
//                'last_step' => 'add_new_restaurant_partner_account',
//                'last_value' => $restaurant->id
//            ];
//
//            $this->userService->updateUserLastStep($this->user->telegram_id, $values);

            $partnerUsers = $this->userService->getActivePartners();

            $inline = [];
            if (count($partnerUsers) > 0){
                foreach($partnerUsers as $index => $item){
                    $inline[$index][0]['text'] = $item['name'];
                    $inline[$index][0]['callback_data'] = 'add_new_restaurant_partner_account/' . $item['id'];
                }
            }
            $inline[count($inline)][0] = [
                'text' => __('admin.restaurants_add_new_restaurant_partner_account_null'),
                'callback_data' => 'add_new_restaurant_partner_account/0'
            ];
            $markup = $this->getInlineKeyboard($inline);
            $text = __('admin.restaurants_add_new_restaurant_employee_account');
            $res = $this->sendMessage($text, 'HTML', $markup);
        }
        return $res;
    }


    public function sendStoreNewRestaurantEmployeeMessage($action){
        if ($action == __('admin.restaurants_cancel_creating') || $action == '/cancel'){
            $res = $this->cancelCreatingNewRestaurant();
        }else{

            $data = [
                'partner_id' => intval($action) == 0 ? null : intval($action),
            ];
            $restaurant_id = $this->user->last_value;
            $restaurant = $this->restaurantService->addRestaurantOwner($restaurant_id, $data);

            if(!$restaurant instanceof Restaurant){
                $text = __('admin.restaurants_new_restaurant_duplicate_error');
                return $this->sendMessage($text, 'HTML');
            }

           $this->userService->resetSteps($this->user->telegram_id);

            $text = $this->getEditRestaurantText($restaurant);

            $markup = $this->getEditRestaurantKeyboard($restaurant);
            $this->sendMessage($text, 'HTML', $markup);
            $text = __('admin.restaurants_add_new_restaurant_done');
            $markup = $this->getMainAdminMenuKeyboard($text);
            $res = $this->sendMessage($text, 'HTML', $markup);
        }
        return $res;
    }

    public function sendStoreEditRestaurantNameMessage($action){
        if ($action == __('admin.restaurants_cancel_editing') || $action == '/cancel'){
            $res = $this->cancelEditingRestaurant();
        }else{
            $data = [
                'name' => $action,
            ];
//            dd($data);
            $restaurant = $this->restaurantService->editRestaurantName($this->user->last_value, $data);
            if(!$restaurant instanceof Restaurant){
                $text = __('admin.restaurants_edit_restaurant_name_error');
                return $this->sendMessage($text, 'HTML');
            }
            $markup = $this->getEditRestaurantKeyboard($restaurant);
            $text = $this->getEditRestaurantText($restaurant);

            return $this->editMessageText($this->user->last_message_id, $text, 'HTML', $markup);
            $values = [
                'last_step' => null,
                'last_value' => null,
                'last_message_id' => null
            ];

            $this->deleteMessage($this->message['from']['id'], $this->message['message_id']);
            $this->deleteMessage($this->message['from']['id'], $this->message['message_id'] - 1);

            $this->userService->updateUserLastStep($this->user->telegram_id, $values);

            $text = __('admin.restaurants_edit_restaurant_updated_successfully');

            $markup = $this->getMainAdminMenuKeyboard();
            $res = $this->sendMessage($text, 'HTML', $markup, $this->user->last_message_id);
        }
        return  $res;
    }

    public function sendStoreEditRestaurantAddressMessage($action){
        if ($action == __('admin.restaurants_cancel_editing') || $action == '/cancel'){
            $res = $this->cancelEditingRestaurant();
        }else{
            $data = [
                'address' => $action,
            ];
            $restaurant = $this->restaurantService->editRestaurantAddress($this->user->last_value, $data);
            if(!$restaurant instanceof Restaurant){
                $text = __('admin.restaurants_edit_restaurant_address_error');
                return $this->sendMessage($text, 'HTML');
            }
            $markup = $this->getEditRestaurantKeyboard($restaurant);
            $text = $this->getEditRestaurantText($restaurant);

            return $this->editMessageText($this->user->last_message_id, $text, 'HTML', $markup);

            $values = [
                'last_step' => null,
                'last_value' => null,
                'last_message_id' => null
            ];

            $this->deleteMessage($this->message['from']['id'], $this->message['message_id']);
            $this->deleteMessage($this->message['from']['id'], $this->message['message_id'] - 1);

            $this->userService->updateUserLastStep($this->user->telegram_id, $values);

            $text = __('admin.restaurants_edit_restaurant_updated_successfully');

            $markup = $this->getMainAdminMenuKeyboard();
            $res = $this->sendMessage($text, 'HTML', $markup, $this->user->last_message_id);
        }
        return $res;
    }

    public function sendStoreEditRestaurantLocationMessage($action){
        if ($action == __('admin.restaurants_cancel_editing') || $action == '/cancel'){
            $res = $this->cancelEditingRestaurant();
        }else{
            $data = [
                'latitude' => $this->message['location']['latitude'],
                'longitude' => $this->message['location']['longitude'],
            ];
//            dd($data);
            $restaurant_id = $this->user->last_value;
            $restaurant = $this->restaurantService->editRestaurantLocation($restaurant_id, $data);
            if(!$restaurant instanceof Restaurant){
                $text = __('admin.restaurants_edit_restaurant_location_not_found');
                return $this->sendMessage($text, 'HTML');
            }

            $markup = $this->getEditRestaurantKeyboard($restaurant);
            $text = $this->getEditRestaurantText($restaurant);

            return $this->editMessageText($this->user->last_message_id, $text, 'HTML', $markup);

            $this->userService->resetSteps($this->user->telegram_id);

            $this->deleteMessage($this->message['from']['id'], $this->message['message_id']);
            $this->deleteMessage($this->message['from']['id'], $this->message['message_id'] - 1);

            $text = __('admin.restaurants_edit_restaurant_updated_successfully');

            $markup = $this->getMainAdminMenuKeyboard();
            $res = $this->sendMessage($text, 'HTML', $markup, $this->user->last_message_id);

        }
        return $res;
    }

    // restaurant location 2 for latitude and longitude
    public function sendStoreEditRestaurantLocation2Message($address_location){
        if ($address_location == __('admin.restaurants_cancel_editing') || $address_location == '/cancel'){
            $res = $this->cancelEditingRestaurant();
        }else{
            if (!str_contains($address_location, ',')){
                $text = __('operator.invalid_location_string');
                return $this->sendMessage($text, $parse_mode = 'HTML');
            }
            $points = explode(',', $address_location);
            $latitude = $points[0];
            $longitude = $points[1];
            $data = [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];

//            dd($data);
            $restaurant_id = $this->user->last_value;
            $restaurant = $this->restaurantService->editRestaurantLocation($restaurant_id, $data);
            if(!$restaurant instanceof Restaurant){
                $text = __('admin.restaurants_edit_restaurant_location_not_found');
                return $this->sendMessage($text, 'HTML');
            }

            $markup = $this->getEditRestaurantKeyboard($restaurant);
            $text = $this->getEditRestaurantText($restaurant);

            $this->editMessageText($this->user->last_message_id, $text, 'HTML', $markup);

            $this->userService->resetSteps($this->user->telegram_id);

            $this->deleteMessage($this->message['from']['id'], $this->message['message_id']);
            $this->deleteMessage($this->message['from']['id'], $this->message['message_id'] - 1);

            $text = __('admin.restaurants_edit_restaurant_updated_successfully');

            $markup = $this->getMainAdminMenuKeyboard();
            return $res = $this->sendMessage($text, 'HTML', $markup, $this->user->last_message_id);
        }
        return $res;
    }

    public function sendStoreEditRestaurantEmployeeMessage($action){
        if ($action == __('admin.restaurants_cancel_editing') || $action == '/cancel'){
            $res = $this->cancelEditingRestaurant();
        }else{

            $data = [
                'partner_user_id' => intval($action) == 0 ? null : intval($action),
            ];
            $restaurant_id = $this->user->last_value;
            $restaurant = $this->restaurantService->editRestaurantEmployee($restaurant_id, $data);

            if(!$restaurant instanceof Restaurant){
                $text = __('admin.restaurants_edit_restaurant_duplicate_error');
                return $this->sendMessage($text, 'HTML');
            }

            $markup = $this->getEditRestaurantKeyboard($restaurant);
            $text = $this->getEditRestaurantText($restaurant);

            $this->editMessageText($this->user->last_message_id, $text, 'HTML', $markup);


            $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
            $this->userService->resetSteps($this->user->telegram_id);

            $text = __('admin.restaurants_edit_restaurant_updated_successfully');

            $markup = $this->getMainAdminMenuKeyboard();
            $res = $this->sendMessage($text, 'HTML', $markup, $this->user->last_message_id);
        }
        return $res;
    }
    public function sendStoreEditRestaurantOwnerMessage($action){
        if ($action == __('admin.restaurants_cancel_editing') || $action == '/cancel'){
            $res = $this->cancelEditingRestaurant();
        }else{

            $data = [
                'partner_id' => intval($action) == 0 ? null : intval($action),
            ];
            $restaurant_id = $this->user->last_value;


            if(!$restaurant_id){
                $text = __('admin.restaurants_no_restaurant_found');
                return $this->sendMessage($text, 'HTML');
            }

            $restaurant = $this->restaurantService->editRestaurantOwner($restaurant_id, $data);
//            dd($restaurant);

            if(!$restaurant instanceof Restaurant){
                $text = __('admin.restaurants_edit_restaurant_duplicate_error');
                return $this->sendMessage($text, 'HTML');
            }

            $markup = $this->getEditRestaurantKeyboard($restaurant);
            $text = $this->getEditRestaurantText($restaurant);

            $res = $this->editMessageText($this->user->last_message_id, $text, 'HTML', $markup);


//            $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
//            $this->userService->resetSteps($this->user->telegram_id);

            $text = __('admin.restaurants_edit_restaurant_updated_successfully');

//            $markup = $this->getMainAdminMenuKeyboard();
//            $res = $this->sendMessage($text, 'HTML', $markup, $this->user->last_message_id);
        }
        return $res;
    }

    public function sendDeleteRestaurantEmployeeMessage($user_id){

        $user = $this->userService->find($user_id);

        if(!$user instanceof User){
            $text = __('admin.no_user_found');
            return $this->sendMessage($text, 'HTML');
        }
        $user->load('partner_operator');
        $partner_operator = $user->partner_operator;

        if(!$partner_operator instanceof PartnerOperator){
            $text = __('admin.no_user_found');
            return $this->sendMessage($text, 'HTML');
        }
        $restaurant_id = $partner_operator->restaurant_id;
        $partner_operator->restaurant_id = null;
        $partner_operator->save();
        $partner_operator->refresh();

        return $this->sendRestaurantEmployeesMessageForAdmin($restaurant_id);

    }
    public function sendAddEditRestaurantEmployeeMessage($action){
        if ($action == __('admin.restaurants_cancel_editing') || $action === '/cancel'){
            $res = $this->cancelEditingRestaurant();
        }else{

            $restaurant = Restaurant::find($action);

            if(!$restaurant instanceof Restaurant){
                $text = __('admin.restaurants_edit_restaurant_duplicate_error');
                return $this->sendMessage($text, 'HTML');
            }

            $markup = $this->getEditRestaurantKeyboard($restaurant);
            $text = $this->getEditRestaurantText($restaurant);

            return $this->editMessageText($this->user->last_message_id, $text, 'HTML', $markup);
            $this->deleteMessage($this->callback_query['from']['id'], $this->callback_query['message']['message_id']);
            $this->userService->resetSteps($this->user->telegram_id);
            $text = __('admin.restaurants_edit_restaurant_updated_successfully');

            $markup = $this->getMainAdminMenuKeyboard();
            $res = $this->sendMessage($text, 'HTML', $markup, $this->user->last_message_id);
        }

        return $res;
    }


    public function sendClientsForMailing($action){

        if ($action == __('admin.cancel_clients_report_button') || $action == '/cancel'){
            $this->userService->resetSteps($this->user->telegram_id);
            $res = $this->sendMessage(text: __("File generation cancelled"), reply_markup: $this->getMainAdminMenuKeyboard());
        }else{
            if (!str_contains($action, '-')){
                $text = __('admin.client_as_json_caption_incorrect_date');
                $this->sendMessage($text, 'HTML');
                return false;
            }
            $dates = explode("-", $action);
            $from = trim($dates[0]);
            $to = trim($dates[1]);

            $file_name_as_json = $from . "-" . $to . '.json';
            $file_name_as_vcf = $from . "-" . $to . '.vcf';
            try {
                $from_carbon = Carbon::createFromFormat("Y.m.d", $from);
                $to_carbon = Carbon::createFromFormat("Y.m.d", $to);
            }catch (\Exception $exception){
                $text = __('admin.client_as_json_caption_incorrect_date');
                $this->sendMessage($text, 'HTML');
                return false;
            }

            $clients = \App\Models\Client::query()
                ->select(
                    DB::raw('name'),
                    DB::raw("concat('998', phone_number) as phone")
                )
                ->whereNotNull('name')
                ->whereNotNull('phone_number')
                ->where('name', '!=', '-')
                ->where(DB::raw('DATE(created_at)'), '>=', $from_carbon->toDateString())
                ->where(DB::raw('DATE(created_at)'), '<=', $to_carbon->toDateString())
                ->get();

            $response = [
                'contacts' => $clients->toArray()
            ];

            $file_path = storage_path('clients-as-json-for-' . $file_name_as_json);

            file_put_contents($file_path, json_encode($response));

            $text = __('admin.client_as_json_caption', [
                'from' => $from,
                'to' => $to
            ]);
            $this->sendDocument($this->user->telegram_id, $file_path, $text);

            $vcards = "";
            foreach ($clients as $item){
                if ($item->phone){
                    $name = $item->name;
                    $phone = strlen($item->phone) == 9 ? "+998" . $item->phone : $item->phone ;
                    $vcards .= "BEGIN:VCARD\nVERSION:3.0\nN:$name;;;$name\nTEL;TYPE=CELL:$phone\nEND:VCARD\n";

                }
            }

            $file_path = '';
            $file_path = storage_path('clients-as-vcf-for-' . $file_name_as_vcf);
            file_put_contents($file_path, $vcards);

            $text = __('admin.client_as_json_caption', [
                'from' => $from,
                'to' => $to
            ]);
            $res = $this->sendDocument($this->user->telegram_id, $file_path, $text);

            $this->userService->resetSteps($this->user->telegram_id);

        }
        return $res;
    }

}
