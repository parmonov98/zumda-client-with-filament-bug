<?php

namespace App\Http\Controllers\Bot\Core\Administrator;

use App\Http\Controllers\Bot\Core\Administrator\Methods\AdministratorCallbacks;
use App\Http\Controllers\Bot\Core\Administrator\Methods\AdministratorMessages;
use App\Http\Controllers\Bot\Core\Customer\Methods\UserMethods;
use App\Http\Controllers\Bot\Core\MakeComponents;
use App\Http\Controllers\Bot\Core\RequestTrait;
use App\Http\Controllers\Bot\Core\SharedMethods\CategoryMethods;
use App\Http\Controllers\Bot\Core\SharedMethods\ProductMethods;
use App\Http\Controllers\Bot\Core\SharedMethods\RestaurantMethods;
use App\Http\Controllers\Bot\Services\CategoryService;
use App\Http\Controllers\Bot\Services\ProductService;
use App\Http\Controllers\Bot\Services\RestaurantService;
use App\Http\Controllers\Bot\Services\UserService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdministratorRouter extends Controller
{

    use RequestTrait;
    use MakeComponents;
    use AdministratorMessages;
    use AdministratorCallbacks;
    use RestaurantMethods;
    use CategoryMethods;
    use ProductMethods;
    use UserMethods;

    protected string $type;
    protected mixed $message;
    protected mixed $text;
    protected mixed $action;
    protected mixed $callback_data;
    protected mixed $callback_query;
    protected mixed $user;

    /**
     * Store a new user.
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

    public function routes(User $user, string $type, mixed $action = null)
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

    public function messages($text){
        $this->text = $text;
        $action = $text;
        switch ($action) {
            case '/start':
                return $this->sendMessage(
                    __("Assalomu alaykum, Admin aka! nima xizmat?!"),
                    'HTML',
                    $this->getMainAdminMenuKeyboard()
                );
        }
        if ($this->user->last_step != null) {

            switch ($this->user->last_step) {
                case 'get_client_as_json':
                    return $this->sendClientsForMailing($action);
                    break;
                case 'add_new_restaurant_name':
                    return $this->sendStoreNewRestaurantNameMessage($action);
                    break;
                case 'add_new_restaurant_address':
                    return $this->sendStoreNewRestaurantAddressMessage($action);
                    break;
                case 'add_new_restaurant_location':
                    return $this->sendStoreNewRestaurantLocationMessage($action);
                    break;
                case 'add_new_restaurant_partner_account':
//                    this step moved to callback
                    if ($action == __('admin.restaurants_cancel_creating')){
                        return $this->cancelCreatingNewRestaurant();
                    }
                    break;
                case 'add_new_category_name':
                    return $this->sendStoreNewCategoryNameMessage($action);
                    break;
                case 'add_new_category_description':
                    return $this->sendStoreNewCategoryDescriptionMessage($action);
                    break;


                case 'add_new_product_name':
                    return $this->sendStoreNewProductNameMessage($action);
                    break;
                case 'add_new_product_photo':
                    return $this->sendStoreNewProductPhotoMessage($action);
                    break;
                case 'add_new_product_price':
                    return $this->sendStoreNewProductPriceMessage($action);
                    break;
                case 'add_new_product_description':
                    return $this->sendStoreNewDescriptionMessage($action);
                    break;
//                case 'add_new_restaurant_partner_account':
////                    this step moved to callback
//                    if ($action == __('admin.restaurants_cancel_creating')){
//                        $this->cancelCreatingNewRestaurant();
//                    }
//                    break;
                case 'edit_restaurant_name':
                    return $this->sendStoreEditRestaurantNameMessage($action);
                    break;
                case 'edit_restaurant_address':
                    return $this->sendStoreEditRestaurantAddressMessage($action);
                    break;
                case 'edit_restaurant_location':
                    if (isset($this->message['location'])){
                        return $this->sendStoreEditRestaurantLocationMessage($action);
                    }else{
                        return $this->sendStoreEditRestaurantLocation2Message($action);
                    }
                    break;
                case 'edit_category_name':
                    return $this->sendStoreEditCategoryNameMessage($action);
                    break;
                case 'edit_category_description':
                    return $this->sendStoreEditCategoryDescriptionMessage($action);
                    break;
                case 'edit_product_name':
                    return $this->sendStoreEditProductNameMessage($action);
                    break;
                case 'edit_product_price':
                    return $this->sendStoreEditProductPriceMessage($action);
                    break;
                case 'edit_product_percentage':
                    return $this->sendStoreEditProductPercentageMessage($action);
                    break;
                case 'edit_product_photo':
                    return $this->sendStoreEditProductPhotoMessage($action);
                    break;
                case 'edit_product_description':
                    return $this->sendStoreEditProductDescriptionMessage($action);
                    break;
                case 'mailing':
                    return $this->storeMailingMessage();
                    break;
                default:
                    # code...
                    break;
            }
        }
        if (!$this->user->last_step){
            if (isset($this->message['photo'])) {

                $text = $this->printArray($this->message['photo']);

                return $this->sendMessage($text, $parse_mode = 'HTML');
            }
        }
        if ($action == __('admin.admin_keyboard_restaurants')) {
            return $this->sendRestaurantsMenuForAdmin();
        }
        if ($action == __('admin.admin_keyboard_back_to_home')) {
            return $this->sendMainMenuForAdmin();
        }
        if ($action == __('admin.admin_keyboard_reports')) {
            return $this->sendReportsMenu();
        }
        if ($action == __('admin.admin_keyboard_driver_reports')) {
            return $this->sendDriverReports();
        }
        if ($action == __('admin.admin_keyboard_order_reports')) {
            return $this->sendOrderReports();
        }
        if ($action == __('admin.admin_keyboard_clients_as_json')) {
            return $this->sendClientsAsJsonCalendarMenuForAdmin();
        }
        if ($action == __('admin.admin_keyboard_users')) {
            return $this->sendUsersMenuForAdmin();
        }
        if ($this->text == __('admin.admin_keyboard_statistics')) {
            return $this->sendStatistics();
        }
        if ($this->text == __('admin.admin_keyboard_mailing')) {

            $this->userService->resetSteps($this->user->telegram_id);

            $text = __('admin.admin_mailing_text');
            return $this->sendMessage($text, $parse_mode = 'HTML');

            // return $this->sendReports();

            return;
        }

        if ($this->text == '/menu') {
            return $this->sendMainMenuForAdmin();
        }


//        if ($action){
////            $strWithSlashes = filter_var($action, FILTER_SANITIZE_ADD_SLASHES);
////            dd($strWithSlashes);
////            dd(htmlspecialchars($action));
//            return $this->sendMessage(htmlspecialchars($action), 'HTML');
//        }
    }

    public function callbacks($action){
        $this->callback_data = $action;
        //ConsoleOutput::writeln($action);
//        if ($this->user->status !== 'active'){
//            $this->greetUserDisabled();
//            return 'OK_OPERATOR_DISABLED';
//        }

//        if (
//            $this->user->last_step
//        ){
//            $text = __('admin.please_complete_editing_or_creating');
//            dd($this->user->last_step, $text);
//
//            return $this->answerCallbackQuery($text);
//        }

        if($action == 'add_new_user'){
            return $this->sendAddNewUserMenuForAdmin();
        }

        if($action == 'choose_operator'){
            return $this->sendAddNewOperatorMenuForAdmin();
        }

        if($action == 'choose_driver'){
            return $this->sendAddNewDriverMenuForAdmin();
        }

        if($action == 'choose_partner_operator'){
            return $this->sendAddNewPartnerOperatorMenuForAdmin();
        }

        if($action == 'choose_partner'){
            return $this->sendAddNewPartnerMenuForAdmin();
        }

        if($action == 'operators'){
            return $this->sendOperatorsMenuForAdmin();
        }
        if($action == 'partner_operators'){
            return $this->sendPartnerOperatorsMenuForAdmin();
        }
        if($action == 'partners'){
            return $this->sendPartnersMenuForAdmin();
        }
        if($action == 'drivers'){
            return $this->sendDriversMenuForAdmin();
        }
        if($action == 'back_to_users'){
            return $this->sendUsersMenuForAdmin('', true);
        }
        if($action == 'back_to_restaurants'){
            return $this->sendBackToRestaurantsForAdmin();
        }
        preg_match("/^operator\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendOperatorViewMenuForAdmin($tokens[1]);
        }
        preg_match("/^delete_operator\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendDeleteOperatorMessage($tokens[1]);
        }
        preg_match("/^delete_operator_back\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendOperatorViewMenuForAdmin($tokens[1]);
        }
        preg_match("/^delete_operator_confirm\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $this->deleteOperatorByAdminConfirmation($tokens[1]);
        }
        preg_match("/^operator_on\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendToggleOperatorStatusRequest($tokens[1]);
        }
        preg_match("/^operator_off\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendToggleOperatorStatusRequest($tokens[1]);
        }
        preg_match("/^driver\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendDriverViewMenuForAdmin($tokens[1]);
        }
        preg_match("/^driver_on\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->toggleDriverStatus($tokens[1]);
        }
        preg_match("/^delete_driver\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendDeleteDriverMessage($tokens[1]);
        }
        // driver_off\/([0-9]+)
        preg_match("/^driver_off\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->toggleDriverStatus($tokens[1]);
        }
        preg_match("/^delete_driver_back\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendDriverViewMenuForAdmin($tokens[1]);
        }
        preg_match("/^delete_partner_operator_back\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendPartnerOperatorViewMenuForAdmin($tokens[1]);
        }
        preg_match("/^delete_partner_operator_confirm\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $this->deletePartnerOperatorByAdminConfirmation($tokens[1]);
        }
        preg_match("/^delete_driver_confirm\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $this->deleteDriverByAdminConfirmation($tokens[1]);
        }
        preg_match("/^update_driver\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $this->updateDriverViewForAdmin($tokens[1]);
        }
        preg_match("/^partner\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendPartnerViewMenuForAdmin($tokens[1]);
        }
        preg_match("/^partner_operator\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendPartnerOperatorViewMenuForAdmin($tokens[1]);
        }
        preg_match("/^delete_partner_operator\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendDeletePartnerOperatorMessage($tokens[1]);
        }
        preg_match("/^update_restaurant_employee\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $this->updateRestaurantViewForAdmin($tokens[1]);
        }
        preg_match("/^partner_operator_on\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->togglePartnerOperatorStatus($tokens[1]);
        }
        preg_match("/^partner_operator_off\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->togglePartnerOperatorStatus($tokens[1]);
        }
        preg_match("/^restaurant\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendRestaurantViewMenuForAdmin($tokens[1]);
        }
        preg_match("/^back_restaurant\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendRestaurantViewMenuForAdmin($tokens[1]);
        }
        preg_match("/^restaurants_go_to_categories\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendRestaurantCategoriesMenuForAdmin($tokens[1]);
        }
        preg_match("/^add_new_restaurant_partner_account\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendStoreNewRestaurantEmployeeMessage($tokens[1]);
        }
        preg_match("/^restaurant_on\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendToggleRestaurantStatusRequest($tokens[1]);
        }
        preg_match("/^restaurant_off\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendToggleRestaurantStatusRequest($tokens[1]);
        }
        preg_match("/^delete_restaurant\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendDeleteRestaurantByAdmin($tokens[1]);
        }
        preg_match("/^delete_restaurant_back_button\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendEditRestaurantMenuForAdmin($tokens[1]);
        }
        preg_match("/^delete_restaurant_confirm_button\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendDeleteRestaurantConfirmationByAdmin($tokens[1]);
        }
        preg_match("/^edit_restaurant\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendEditRestaurantMenuForAdmin($tokens[1]);
        }
        preg_match("/^edit_restaurant_dishes\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendEditRestaurantDishesMenuForAdmin($tokens[1]);
        }
        preg_match("/^back_to_edit_restaurant\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendEditRestaurantMenuForAdmin($tokens[1]);
        }
        preg_match("/^edit_restaurant_name\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendEditRestaurantNameMessageForAdmin($tokens[1]);
        }
        preg_match("/^edit_category_name\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendEditCategoryNameMessageForAdmin($tokens[1]);
        }
        preg_match("/^edit_product_name\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendEditProductNameMessageForAdmin($tokens[1]);
        }
        preg_match("/^edit_product_price\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendEditProductPriceMessageForAdmin($tokens[1]);
        }
        preg_match("/^edit_product_percentage\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendEditProductPercentageMessageForAdmin($tokens[1]);
        }
        preg_match("/^edit_product_photo\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendEditProductPhotoMessageForAdmin($tokens[1]);
        }
        preg_match("/^edit_product_description\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendEditProductDescriptionMessageForAdmin($tokens[1]);
        }
        preg_match("/^edit_category_description\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendEditCategoryDescriptionMessageForAdmin($tokens[1]);
        }
        preg_match("/^edit_restaurant_address\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendEditRestaurantAddressMessageForAdmin($tokens[1]);
        }
        preg_match("/^edit_restaurant_location\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendEditRestaurantLocationMessageForAdmin($tokens[1]);
        }
        preg_match("/^add_restaurant_employee_account\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendEditRestaurantEmployeesAccountMessageForAdmin($tokens[1]);
        }
        preg_match("/^edit_restaurant_employees\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendRestaurantEmployeesMessageForAdmin($tokens[1]);
        }
        preg_match("/^update_restaurant_owner\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendRestaurantOwnerMessageForAdmin($tokens[1]);
        }
        preg_match("/^set_new_restaurant_owner\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendStoreEditRestaurantOwnerMessage($tokens[1]);
        }
        preg_match("/^back_to_edit_restaurant_employees\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendRestaurantEmployeesMessageForAdmin($tokens[1]);
        }
        preg_match("/^view_restaurant_employee_account\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendViewRestaurantEmployeeMessageForAdmin($tokens[1]);
        }
        preg_match("/^toggle_restaurant_employee\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendToggleRestaurantEmployeeMessageForAdmin($tokens[1]);
        }
        preg_match("/^set_restaurant_employee_account\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendStoreEditRestaurantEmployeeMessage($tokens[1]);
        }
        preg_match("/^confirm_add_restaurant_employee_account\/([0-9]+)\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendConfirmAddRestaurantEmployeeMessage($tokens[1], $tokens[2]);
        }
        preg_match("/^delete_restaurant_employee\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendDeleteRestaurantEmployeeMessage($tokens[1]);
        }
        preg_match("/^add_restaurant_partner_account\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendAddEditRestaurantEmployeeMessage($tokens[1]);
        }
        preg_match("/^categories_back_to_restaurant\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendRestaurantViewMenuForAdmin($tokens[1]);
        }
        preg_match("/^back_to_categories\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            $restaurant_id = $tokens[1];
            return $this->sendRestaurantCategoriesMenu($restaurant_id, false);
        }


        if ($action == 'add_new_restaurant') {
            return $this->sendCreateNewRestaurantMessage();
        }

        /* categories */

        preg_match("/^add_new_category\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendAddCategoryMessage($tokens[1]);
        }
        preg_match("/^edit_category\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendEditCategoryMenuForAdmin($tokens[1]);
        }
        preg_match("/^category\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendCategoryViewMessage($tokens[1]);
        }

        preg_match("/^add_new_product\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendAddProductMessage($tokens[1]);
        }
        // product\/([0-9]+)
        preg_match("/^product\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendProductViewMessage($tokens[1], false);
        }
        preg_match("/^product_from_edit\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendProductViewMessage($tokens[1]);
        }
        preg_match("/^edit_product\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendEditProductMenuForAdmin($tokens[1]);
        }
        preg_match("/^category_on\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->toggleCategoryStatus($tokens[1]);
        }
        preg_match("/^category_off\/([0-9]+)/", $action, $tokens);
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
        preg_match("/^delete_category\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendDeleteCategoryByAdmin($tokens[1]);
        }
        preg_match("/^delete_product\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendDeleteProductByAdmin($tokens[1]);
        }
        preg_match("/^categories_go_to_products\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendCategoryProductsMenuForAdmin($tokens[1]);
        }
        preg_match("/^categories_go_to_products_from_product\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendCategoryProductsFromProductViewMenuForAdmin($tokens[1], true);
        }
        if ($action == '/menu') {
            return $this->sendMainMenu();
        }

        preg_match("/^order_move_to_cook_button\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->moveOrderToCookAndSendDrivers($tokens);
        }
        preg_match("/^order_move_to_completed_button\/([0-9]+)/", $action, $tokens);

        if (isset($tokens[1])) {
            return $this->completeOrder($tokens[1]);
        }

        preg_match("/^start_mailing\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->mailToUsers($tokens);
        }

        preg_match("/^set_year_from_date\/([0-9]+)/", $action, $tokens);
        if (isset($tokens[1])) {
            return $this->sendSetFromDateYearClientsAsJsonCalendarMenuForAdmin($tokens[1]);
        }

        if (!$action){
            $action = '-';
        }
        return $this->answerCallbackQuery($action, false);

    }

}
