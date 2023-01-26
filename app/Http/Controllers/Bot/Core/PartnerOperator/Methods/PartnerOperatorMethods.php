<?php

namespace App\Http\Controllers\Bot\Core\PartnerOperator\Methods;

use App\Http\Controllers\Bot\Core\SharedMethods\CategoryMethods;
use App\Models\Category;

trait PartnerOperatorMethods
{
    use CategoryMethods;
    public function getEditPartnerText($partner_operator){
        $restaurantText = '-';
        if ($partner_operator->restaurant){
            $restaurantText = $partner_operator->restaurant->translation->name;
        }
        $text = __('admin.users_partner_template', [
            'restaurant' => htmlspecialchars($restaurantText),
            'name' => htmlspecialchars($partner_operator->name),
            'phone_number' => $partner_operator?->user?->phone_number,
            'joined_at' => $partner_operator->created_at,
        ]);

        return $text;
    }

    public function getEditPartnerViewKeyboard($partner_operator){

        if ($partner_operator->status == 'active'){
            $statusButton = [
                'text' => __('admin.users_partner_operator_on'),
                'callback_data' => 'partner_operator_off/' . $partner_operator->id
            ];
        }else{
            $statusButton = [
                'text' => __('admin.users_partner_operator_off'),
                'callback_data' => 'partner_operator_on/' . $partner_operator->id
            ];
        }
        $options = [
            [
                [
                    'text' => __('admin.restaurant_employees_update_button'),
                    'callback_data' => 'update_restaurant_employee/' . $partner_operator->id
                ]
            ],
            [
                $statusButton
            ],
            [
                [
                    'text' => __('admin.users_back_to_partner_operators'),
                    'callback_data' => 'partner_operators'
                ]
            ]
        ];

        return $this->getInlineKeyboard($options);
    }

    public function toggleProductStatus($action){

        $product = $this->productService->find($action);
        if ($product->status == 'active'){
            $product->status = 'inactive';
        }else{
            $product->status = 'active';
        }
        $product->save();
        $product->refresh();

        return $this->sendCategoryProductsMessage($product->category->id, false);
    }

}