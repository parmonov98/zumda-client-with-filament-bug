<?php

namespace App\Providers;

use App\Filament\Resources\CommonCategoryResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\RestaurantDishResource;
use App\Filament\Resources\RestaurantResource;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // pick multiples columns and return as array
        Collection::macro('pick', function ($cols = ['*']) {
            $cols = is_array($cols) ? $cols : func_get_args();
            $obj = clone $this;

            // Just return the entire collection if the asterisk is found.
            if (in_array('*', $cols)) {
                return $this;
            }

            return $obj->transform(function ($value) use ($cols) {
                $ret = [];
                foreach ($cols as $col) {
                    // This will enable us to treat the column as a if it is a
                    // database query in order to rename our column.
                    $name = $col;
                    if (preg_match('/(.*) as (.*)/i', $col, $matches)) {
                        $col = $matches[1];
                        $name = $matches[2];
                    }

                    // If we use the asterisk then it will assign that as a key,
                    // but that is almost certainly **not** what the user
                    // intends to do.
                    $name = str_replace('.*.', '.', $name);

                    // We do it this way so that we can utilise the dot notation
                    // to set and get the data.
                    Arr::set($ret, $name, data_get($value, $col));
                }

                return $ret;
            });
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

    }
}
