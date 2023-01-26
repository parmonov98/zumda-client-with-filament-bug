<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $createAction = new CreateNewUser();
        $createAction->create(
            [
                'name' => 'murod',
                'email' => 'parmonov98@yandex.ru',
                'role' => 'developer',
                'password' => '1',
                'password_confirmation' => '1',
            ]
        );
        $this->call(UsersTableSeeder::class);
//        $this->call(PersonalAccessTokensTableSeeder::class);

//        $this->call(CartsTableSeeder::class);
//        $this->call(CartItemsTableSeeder::class);

        $this->call(AdministratorsTableSeeder::class);
        $this->call(OperatorsTableSeeder::class);
//        $this->call(DriversTableSeeder::class);
//        $this->call(ClientsTableSeeder::class);
//        $this->call(PartnerOperatorsTableSeeder::class);
//        $this->call(PartnersTableSeeder::class);
        $this->call(RestaurantsTableSeeder::class);
        $this->call(RestaurantTranslationsTableSeeder::class);

        $this->call(CategoriesTableSeeder::class);
        $this->call(CategoryTranslationsTableSeeder::class);

        $this->call(PhotosTableSeeder::class);
        $this->call(ProductOptionsTableSeeder::class);
        $this->call(ProductTranslationsTableSeeder::class);
        $this->call(ProductsTableSeeder::class);

        $this->call(CommonCategoriesTableSeeder::class);
        $this->call(CommonCategoryTranslationsTableSeeder::class);
        $this->call(FeedbackTableSeeder::class);
        $this->call(MessagesTableSeeder::class);
        $this->call(OrderItemsTableSeeder::class);
        $this->call(OrdersTableSeeder::class);

        $this->call(ReviewsTableSeeder::class);
//        $this->call(SessionsTableSeeder::class);
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->call(RestaurantDishesTableSeeder::class);
        $this->call(ProductDishesTableSeeder::class);
    }
}
