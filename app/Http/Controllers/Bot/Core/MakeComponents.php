<?php

namespace App\Http\Controllers\Bot\Core;

trait MakeComponents
{

    public function getKeyboard(array $options, bool $resize = false, bool $onetime = true)
    {
        $keyboard = [
            'keyboard' => $options,
            'resize_keyboard' => $resize,
            'one_time_keyboard' => $onetime,
            'selective' => true
        ];

        $keyboard = json_encode($keyboard);
        return $keyboard;
    }

    public function getInlineKeyboard(array $options)
    {
        $keyboard = [
            'inline_keyboard' => $options,
        ];

        $keyboard = json_encode($keyboard);
        return $keyboard;
    }


    public function getProductQuantityMenu($product_id)
    {
        $numbers = [
            [
                [
                    'text' => 1,
                    'callback_data' =>  'add_to_cart/' . $product_id . '/1'
                ],
                [
                    'text' => 2,
                    'callback_data' => 'add_to_cart/' . $product_id . '/2'
                ],
                [
                    'text' => 3,
                    'callback_data' => 'add_to_cart/' . $product_id . '/3'
                ],
            ],
            [
                [
                    'text' => 4,
                    'callback_data' => 'add_to_cart/' . $product_id . '/4'
                ],
                [
                    'text' => 5,
                    'callback_data' => 'add_to_cart/' . $product_id . '/5'
                ],
                [
                    'text' => 6,
                    'callback_data' => 'add_to_cart/' . $product_id . '/6'
                ],
            ],
            [
                [
                    'text' => 7,
                    'callback_data' => 'add_to_cart/' . $product_id . '/7'
                ],
                [
                    'text' => 8,
                    'callback_data' => 'add_to_cart/' . $product_id . '/8'
                ],
                [
                    'text' => 9,
                    'callback_data' => 'add_to_cart/' . $product_id . '/9'
                ],
            ]
        ];

        $numbers[3][0]['text'] = __('client.back_to_product_list');
        $numbers[3][0]['callback_data'] = 'back_to_product_list/' . $product_id;
//        $numbers[4][0]['text'] = __('client.back_to_product_menu_on_product');
//        $numbers[4][0]['callback_data'] = 'back_to_main_category_menu_from_product';

        return $this->getInlineKeyboard($numbers);
    }

    public function getOrderText($order)
    {

        $items = $order->items;

        $payment_method = '';

        if ($order->payment_type == 'cash') {
            $payment_method = 'client.order_payment_method_cash';
        }
        if ($order->payment_type == 'payme') {
            $payment_method = 'client.order_payment_method_payme';
        }
        if ($order->payment_type == 'click') {
            $payment_method = 'client.order_payment_method_click';
        }

        $text = '';
        $summary = 0;
        $itemsText = '';

        $prices = [];
        foreach ($items as $key => $item) {
            $itemSummary = ($item->quantity * $item->price);
            $summary += $itemSummary;
            if ($item->product && $item->product->translation) {
                $itemsText .= $item->quantity . ' x ' . $item->product->translation->name . ' = ' . number_format($itemSummary, 0, '.', ',') . __('client.item_currency_lang');
                $prices[$key]['amount'] = $itemSummary;
                $prices[$key]['label'] = $item->product->translation->name;
            }else{
                continue;
            }
        }

        // dd($shipping_price);
        // $text .= $itemsText;
        $order->load(['customer']);
        $shipping_price = $order->shipping_price;
        $text .= __('admin.order_details', [
            'order_id' => $order->id,
            'first_name' => $order->customer->first_name,
            'phone' => $order->phone_number,
            'shipping_price' => number_format($shipping_price, 0, '.', ','),
            'shipping_address' => $order->address,
            'text' => $itemsText,
            'message' => $order->customer_note,
            'payment_method' => __($payment_method)
        ]);
        $text .= __('client.order_summary', [
            'summary' => number_format(($summary + $shipping_price), 0, '.', ',')
        ]);


        return $text;
    }

    public function getOrderInvoiceText($order)
    {

        $items = $order->items;
        $payment_method = 'client.order_payment_method_payme';



        $text = '';
        $summary = 0;
        $itemsText = '';

        $prices = [];
        foreach ($items as $key => $item) {
            $itemSummary = ($item->quantity * $item->price);
            $summary += $itemSummary;
            $itemsText .= $item->quantity . ' x ' . $item->product->translation->name . ' = ' . number_format($itemSummary, 0, '.', ',') . __('client.item_currency_lang');
            $prices[$key]['amount'] = $itemSummary;
            $prices[$key]['label'] = $item->product->translation->name;
        }

        $shipping_price = $order->shipping_price;

        $text .= __('client.order_invoice_details', [
            'text' => $itemsText,
            'shipping_price' => number_format($shipping_price, 0, '.', ','),
            'message' => $order->customer_note,
        ]);

        $text .= __('client.order_summary', [
            'summary' => number_format(($summary + $shipping_price), 0, '.', ',')
        ]);


        return $text;
    }

    public function generateHTMLText($text, $entities)
    {

        return $this->entitiesToHtml($text, $entities);
        // dd($entities, $text);
    }

    function mbStringToArray($string, $encoding = 'UTF-8')
    {
        $array = [];
        $strlen = mb_strlen($string, $encoding);
        while ($strlen) {
            $array[] = mb_substr($string, 0, 1, $encoding);
            $string = mb_substr($string, 1, $strlen, $encoding);
            $strlen = mb_strlen($string, $encoding);
        }
        return $array;
    }

    function parseTagOpen($textToParse, $entity, $oTag)
    {
        $i = 0;
        $textParsed = '';
        $nullControl = false;
        $string = $this->mbStringToArray($textToParse, 'UTF-16LE');
        foreach ($string as $s) {
            if ($s === "\0\0") {
                $nullControl = !$nullControl;
            } elseif (!$nullControl) {
                if ($i == $entity['offset']) {
                    $textParsed = $textParsed . $oTag;
                }
                $i++;
            }
            $textParsed = $textParsed . $s;
        }
        return $textParsed;
    }

    function parseTagClose($textToParse, $entity, $cTag)
    {
        $i = 0;
        $textParsed = '';
        $nullControl = false;
        $string = $this->mbStringToArray($textToParse, 'UTF-16LE');
        foreach ($string as $s) {
            $textParsed = $textParsed . $s;
            if ($s === "\0\0") {
                $nullControl = !$nullControl;
            } elseif (!$nullControl) {
                $i++;
                if ($i == ($entity['offset'] + $entity['length'])) {
                    $textParsed = $textParsed . $cTag;
                }
            }
        }
        return $textParsed;
    }

    function htmlEscape($textToParse)
    {
        $i = 0;
        $textParsed = '';
        $nullControl = false;
        $string = $this->mbStringToArray($textToParse, 'UTF-8');
        foreach ($string as $s) {
            if ($s === "\0") {
                $nullControl = !$nullControl;
            } elseif (!$nullControl) {
                $i++;
                $textParsed = $textParsed . str_replace(['&', '"', '<', '>'], ["&amp;", "&quot;", "&lt;", "&gt;"], $s);
            } else {
                $textParsed = $textParsed . $s;
            }
        }
        return $textParsed;
    }


    function entitiesToHtml($text, $entities)
    {
        $textToParse = mb_convert_encoding(htmlspecialchars($text), 'UTF-16BE', 'UTF-8');

        foreach ($entities as $entity) {
            $href = false;
            switch ($entity['type']) {
                case 'bold':
                    $tag = 'b';
                    break;
                case 'italic':
                    $tag = 'i';
                    break;
                case 'underline':
                    $tag = 'ins';
                    break;
                case 'strikethrough':
                    $tag = 'strike';
                    break;
                case 'code':
                    $tag = 'code';
                    break;
                case 'pre':
                    $tag = 'pre';
                    break;
                case 'text_link':
                    $tag = '<a href="' . $entity['url'] . '">';
                    $href = true;
                    break;
                case 'text_mention':
                    $tag = '<a href="tg://user?id=' . $entity['user']['id'] . '">';
                    $href = true;
                    break;
                default:
                    continue 2;
            }

            if ($href) {
                $oTag = "\0{$tag}\0";
                $cTag = "\0</a>\0";
            } else {
                $oTag = "\0<{$tag}>\0";
                $cTag = "\0</{$tag}>\0";
            }
            $oTag = mb_convert_encoding($oTag, 'UTF-16BE', 'UTF-8');
            $cTag = mb_convert_encoding($cTag, 'UTF-16BE', 'UTF-8');

            $textToParse = $this->parseTagOpen($textToParse, $entity, $oTag);
            $textToParse = $this->parseTagClose($textToParse, $entity, $cTag);
        }

        if (isset($entity)) {
            $textToParse = mb_convert_encoding($textToParse, 'UTF-8', 'UTF-16BE');
            $textToParse = $this->htmlEscape($textToParse);
            return str_replace("\0", '', $textToParse);
        }

        return htmlspecialchars($text);
    }

    public function getOrderInvoicePrices($order)
    {

        $items = $order->items;

        $summary = 0;

        $prices = [];
        foreach ($items as $key => $item) {
            $itemSummary = ($item->quantity * $item->price);
            $summary += $itemSummary;
            $prices[$key]['amount'] = $itemSummary;
            $prices[$key]['label'] = $item->product->translation->name;
        }

        $last_index = count($prices);
        $prices[$last_index]['amount'] = $order->shipping_price;
        $prices[$last_index]['label'] = __('client.order_delivery_service');

        return $prices;
    }


    public function getOrderInlineMenu($order)
    {

        // dd($order);
        $options = [
            [
                [
                    'text' => __('admin.order_move_to_cook_button'),
                    'callback_data' => 'order_move_to_cook_button/' . $order->id
                ],
                [
                    'text' => __('admin.order_move_to_driver_button'),
                    'callback_data' => 'order_move_to_driver_button/' . $order->id
                ]
            ],
            [
                [
                    'text' => __('admin.order_move_to_completed_button'),
                    'callback_data' => 'order_move_to_completed_button/' . $order->id
                ],
                [
                    'text' => __('admin.order_move_to_cancelled_button'),
                    'callback_data' => 'order_move_to_cancelled_button/' . $order->id
                ]
            ],
            [
                [
                    'text' => __('admin.order_update'),
                    'callback_data' => 'order_update/' . $order->id
                ]
            ]
        ];

        // dd($order->status);
        switch ($order->status) {
            case 'created':
                // return true;
                break;
            case 'preparing':
                $options[0][0]['text'] = __('admin.order_move_to_cook_button_activated');
                $options[0][0]['callback_data'] = 'order_move_to_cook_button';
                break;
            case 'delivering':
                $options[0][0]['text'] = __('admin.order_move_to_cook_button_activated');
                $options[0][0]['callback_data'] = 'order_move_to_cook_button';
                $options[0][1]['text'] = __('admin.order_move_to_driver_button_activated');
                $options[0][1]['callback_data'] = 'order_move_to_driver_button';
                break;
            case 'paid':
                // if ($order->status == 'cook') {
                //     $options[0][0][0]['text'] = __('admin.order_move_to_cook_button_activated');
                //     $options[0][0][0]['callback_data'] = 'order_move_to_cook_button';
                // }
                // if ($order->status == 'delivering') {
                //     $options[0][0][0]['text'] = __('admin.order_move_to_driver_button_activated');
                //     $options[0][0][0]['callback_data'] = 'order_move_to_driver_button';
                // }
                break;
            case 'delivered':
                $options[0][0]['text'] = __('admin.order_move_to_cook_button_activated');
                $options[0][0]['callback_data'] = 'order_move_to_cook_button';
                $options[0][1]['text'] = __('admin.order_move_to_driver_button_delivered');
                $options[0][1]['callback_data'] = 'order_move_to_driver_button';
                break;
            case 'completed':
                $options[0][0]['text'] = __('admin.order_move_to_cook_button_activated');
                $options[0][0]['callback_data'] = 'order_move_to_cook_button';
                $options[0][1]['text'] = __('admin.order_move_to_driver_button_delivered');
                $options[0][1]['callback_data'] = 'order_move_to_driver_button';
                $options[1][0]['text'] = __('admin.order_move_to_completed_button_activated');
                $options[1][0]['callback_data'] = 'order_move_to_driver_button';
                break;
            case 'canceled':
                $options[1][1]['text'] = __('admin.order_move_to_cancelled_button_activated');
                $options[1][1]['callback_data'] = 'order_move_to_cancelled_button';
                break;

            default:

                break;
        }

        // dd(1);

        // dd($options);


        return $this->getInlineKeyboard($options);
    }

    public function getOrderViewKeyboard($order, $user)
    {
        $options = [];
        if ($user->role == 'operator'){
//            dd($order, $user);
            switch ($order->status) {

                case 'created':
                    // return true;
                    break;
                case 'accepted':
                    $options = [
                        [
                            [
                                'text' => __('admin.order_resend_order_to_drivers_with_prepared_status_button'),
                                'callback_data' => 'order_resend_order_to_drivers_with_prepared_status/' . $order->id
                            ],
                            [
                                'text' => __("admin.order_move_to_driver_accepted_button"),
                                'callback_data' => 'order_move_to_driver_list/' . $order->id
                            ],
                        ],
                        [
                            [
                                'text' => __('admin.order_move_to_cancelled_button'),
                                'callback_data' => 'order_move_to_cancelled/' . $order->id
                            ],
                            [
                                'text' => __('admin.order_update'),
                                'callback_data' => 'order_update/' . $order->id
                            ]
                        ]
                    ];
                    break;
                case 'preparing':

                    $options = [
                        [
                            [
                                'text' => __('admin.order_resend_order_to_drivers_with_prepared_status_button'),
                                'callback_data' => 'order_resend_order_to_drivers_with_prepared_status/' . $order->id
                            ],
                            [
                                'text' => __("admin.order_move_to_driver_accepted_button"),
                                'callback_data' => 'order_move_to_driver_list/' . $order->id
                            ],
                        ],
                        [
                            [
                                'text' => __('admin.order_move_to_cancelled_button'),
                                'callback_data' => 'order_move_to_cancelled/' . $order->id
                            ],
                            [
                                'text' => __('admin.order_update'),
                                'callback_data' => 'order_update/' . $order->id
                            ]
                        ]
                    ];
                    break;
                case 'prepared':
                    $options = [
                        [
                            [
                                'text' => __('admin.order_resend_order_to_drivers_with_prepared_status_button'),
                                'callback_data' => 'order_resend_order_to_drivers_with_prepared_status/' . $order->id
                            ],
                            [
                                'text' => __("admin.order_move_to_driver_accepted_button"),
                                'callback_data' => 'order_move_to_driver_list/' . $order->id
                            ],
                        ],
                        [
                            [
                                'text' => __('admin.order_move_to_cancelled_button'),
                                'callback_data' => 'order_move_to_cancelled/' . $order->id
                            ],
                            [
                                'text' => __('admin.order_update'),
                                'callback_data' => 'order_update/' . $order->id
                            ]
                        ]
                    ];
                    break;
                case 'delivering':
                    $options = [
                        [
                            [
                                'text' => __('admin.order_resend_order_to_drivers_with_prepared_status_button'),
                                'callback_data' => 'order_resend_order_to_drivers_with_prepared_status/' . $order->id
                            ],
                            [
                                'text' => __("admin.order_move_to_driver_accepted_button"),
                                'callback_data' => 'order_move_to_driver_list/' . $order->id
                            ],
                        ],
                        [
                            [
                                'text' => __('admin.order_move_to_cancelled_button'),
                                'callback_data' => 'order_move_to_cancelled/' . $order->id
                            ],
                            [
                                'text' => __('admin.order_update'),
                                'callback_data' => 'order_update/' . $order->id
                            ]
                        ]
                    ];
                    break;
                case 'delivered':
                    $options = [];
                    break;
                case 'completed':
                    $options = [];
                    break;
                case 'canceled':
                    $options = [];
                    break;

                default:
                    $options = [];
                    break;
            }
        }
        if ($user->role == 'partner'){
//             dd($order->status);

            switch ($order->status) {
                case 'created':
                    // return true;
                    break;
                case 'accepted':
                    $options = [
                        [
                            [
                                'text' => __('admin.order_accept_and_move_to_cook_button'),
                                'callback_data' => 'order_accept_and_move_to_cook/' . $order->id
                            ],
                            [
                                'text' => __('admin.order_cancel_and_call_to_operator_button'),
                                'callback_data' => 'order_cancel_and_call_to_operator/' . $order->id
                            ]
                        ],
//                        [
//                            [
//                                'text' => __('admin.order_move_to_prepared_button'),
//                                'callback_data' => 'order_move_to_prepared_button/' . $order->id
//                            ],
////                            [
////                                'text' => __('admin.order_move_to_cancelled_button'),
////                                'callback_data' => 'order_move_to_cancelled_button/' . $order->id
////                            ]
//                        ],
                        [
                            [
                                'text' => __('admin.order_update'),
                                'callback_data' => 'order_update/' . $order->id
                            ]
                        ]
                    ];
                    break;
                case 'preparing':

                    $options = [
                        [
                            [
                                'text' => __('admin.order_move_to_cook_button_activated'),
                                'callback_data' => 'order_accept_and_move_to_cook'
                            ]
                        ],
//                        [
//                            [
//                                'text' => __('admin.order_move_to_prepared_button'),
//                                'callback_data' => 'order_move_to_prepared_button/' . $order->id
//                            ],
////                            [
////                                'text' => __('admin.order_move_to_cancelled_button'),
////                                'callback_data' => 'order_move_to_cancelled_button/' . $order->id
////                            ]
//                        ],
                        [
                            [
                                'text' => __('admin.order_update'),
                                'callback_data' => 'order_update/' . $order->id
                            ]
                        ]
                    ];
                    break;
                case 'prepared':
                    $options = [
                        [
                            [
                                'text' => __('admin.order_move_to_cook_button_activated'),
                                'callback_data' => 'order_accept_and_move_to_cook'
                            ]
                        ],
//                        [
//                            [
//                                'text' => __('admin.order_move_to_prepared_button_activated'),
//                                'callback_data' => 'order_move_to_prepared_button'
//                            ],
////                            [
////                                'text' => __('admin.order_move_to_cancelled_button'),
////                                'callback_data' => 'order_move_to_cancelled_button/' . $order->id
////                            ]
//                        ],
                        [
                            [
                                'text' => __('admin.order_update'),
                                'callback_data' => 'order_update/' . $order->id
                            ]
                        ]
                    ];
                    break;
                case 'delivering':
                case 'delivered':
                case 'completed':
                case 'canceled':
                default:
                    $options = [
                        [
                            [
                                'text' => __('admin.order_update'),
                                'callback_data' => 'order_update/' . $order->id
                            ]
                        ]
                    ];
                    break;
            }

        }
        if ($user->role == 'driver'){
//            dd($order);
            switch ($order->status) {
                case 'created':
                    // return true;
                    break;
                case 'accepted':
//                    dd($order);
                    if ($order->driver_id == null){
                        $options = [
                            [
                                [
                                    'text' => __('admin.order_accept_and_go_to_point_button'),
                                    'callback_data' => 'order_propose_and_go_to_point/' . $order->id
                                ]
                            ],
                            [
                                [
                                    'text' => __('admin.order_update'),
                                    'callback_data' => 'order_update/' . $order->id
                                ]
                            ]
                        ];
                    }else{
                        if ($order->is_assigned_by_operator){
                            $options = [
                                [
                                    [
                                        'text' => __('admin.order_driver_i_confirm_to_accept_button'),
                                        'callback_data' => 'driver_i_confirm_to_accept/' . $order->id
                                    ],
                                    [
                                        'text' => __('admin.order_driver_i_disconfirm_to_accept_button'),
                                        'callback_data' => 'driver_i_disconfirm_to_accept/' . $order->id
                                    ],
                                ],
                                [
                                    [
                                        'text' => __('admin.order_update'),
                                        'callback_data' => 'order_update/' . $order->id
                                    ]
                                ]
                            ];
                            if ($order->is_accepted_order_by_driver){
                                $options = [
                                    [
                                        [
                                            'text' => __('admin.order_picked_and_got_on_the_way_button'),
                                            'callback_data' => 'order_picked_and_got_on_the_way/' . $order->id
                                        ],
                                        [
                                            'text' => __('admin.order_delivered_and_got_payment_button'),
                                            'callback_data' => 'order_delivered_and_got_payment/' . $order->id
                                        ],
                                    ],
                                    [
                                        [
                                            'text' => __('admin.order_update'),
                                            'callback_data' => 'order_update/' . $order->id
                                        ]
                                    ]
                                ];
                            }
                        }else{

                            if ($order->driver_id === $this->user->id){
                                $options = [
                                    [
                                        [
                                            'text' => __('admin.order_picked_and_got_on_the_way_button'),
                                            'callback_data' => 'order_picked_and_got_on_the_way/' . $order->id
                                        ],
                                        [
                                            'text' => __('admin.order_delivered_and_got_payment_button'),
                                            'callback_data' => 'order_delivered_and_got_payment/' . $order->id
                                        ],
                                    ],
                                    [
                                        [
                                            'text' => __('admin.order_update'),
                                            'callback_data' => 'order_update/' . $order->id
                                        ]
                                    ]
                                ];
                            }else{
                                $options = [];
                            }

                        }

                    }



                    break;
                case 'preparing':
//                    dd($order, $this->user);

                    if ($order->driver_id == null){
                        $options = [
                            [
                                [
                                    'text' => __('admin.order_accept_and_go_to_point_button'),
                                    'callback_data' => 'order_propose_and_go_to_point/' . $order->id
                                ]
                            ],
                            [
                                [
                                    'text' => __('admin.order_update'),
                                    'callback_data' => 'order_update/' . $order->id
                                ]
                            ]
                        ];
                    }else{
                        if ($order->is_assigned_by_operator){

                            if ($order->is_accepted_order_by_driver && $order->driver_id === $this->user->id){
                                $options = [
                                    [
                                        [
                                            'text' => __('admin.order_picked_and_got_on_the_way_button'),
                                            'callback_data' => 'order_picked_and_got_on_the_way/' . $order->id
                                        ],
                                        [
                                            'text' => __('admin.order_delivered_and_got_payment_button'),
                                            'callback_data' => 'order_delivered_and_got_payment/' . $order->id
                                        ],
                                    ],
                                    [
                                        [
                                            'text' => __('admin.order_update'),
                                            'callback_data' => 'order_update/' . $order->id
                                        ]
                                    ]
                                ];
                            }else{
                                $options = [
                                    [
                                        [
                                            'text' => __('admin.order_driver_i_confirm_to_accept_button'),
                                            'callback_data' => 'driver_i_confirm_to_accept/' . $order->id
                                        ],
                                        [
                                            'text' => __('admin.order_driver_i_disconfirm_to_accept_button'),
                                            'callback_data' => 'driver_i_disconfirm_to_accept/' . $order->id
                                        ],
                                    ],
                                    [
                                        [
                                            'text' => __('admin.order_update'),
                                            'callback_data' => 'order_update/' . $order->id
                                        ]
                                    ]
                                ];
                            }
                        }else{
                            if ($order->driver_id === $this->user->id){
                                $options = [
                                    [
                                        [
                                            'text' => __('admin.order_picked_and_got_on_the_way_button'),
                                            'callback_data' => 'order_picked_and_got_on_the_way/' . $order->id
                                        ],
                                        [
                                            'text' => __('admin.order_delivered_and_got_payment_button'),
                                            'callback_data' => 'order_delivered_and_got_payment/' . $order->id
                                        ],
                                    ],
                                    [
                                        [
                                            'text' => __('admin.order_update'),
                                            'callback_data' => 'order_update/' . $order->id
                                        ]
                                    ]
                                ];
                            }else{
                                $options = [];
                            }

                        }

                    }



                    break;
                case 'prepared':

                    if ($order->driver_id != null && $order->driver_id == $this->user->id){
                        $options = [
                            [
                                [
                                    'text' => __('admin.order_picked_and_got_on_the_way_button'),
                                    'callback_data' => 'order_picked_and_got_on_the_way/' . $order->id
                                ],
                                [
                                    'text' => __('admin.order_delivered_and_got_payment_button'),
                                    'callback_data' => 'order_delivered_and_got_payment/' . $order->id
                                ],
                            ],
                            [
                                [
                                    'text' => __('admin.order_update'),
                                    'callback_data' => 'order_update/' . $order->id
                                ]
                            ]
                        ];
                    }else{

                        $options = [
                            [
                                [
                                    'text' => __('admin.order_update'),
                                    'callback_data' => 'order_update/' . $order->id
                                ]
                            ]
                        ];
                    }

                    break;
                case 'delivering':

                    if ($order->driver_id != null && $order->driver_id != $this->user->id) return $this->getInlineKeyboard([]);

                        $options = [
                            [
                                [
                                    'text' => __('admin.order_picked_and_got_on_the_way_button_activated'),
                                    'callback_data' => 'order_picked_and_got_on_the_way'
                                ],
                                [
                                    'text' => __('admin.order_delivered_and_got_payment_button'),
                                    'callback_data' => 'order_delivered_and_got_payment/' . $order->id
                                ]
                            ],
                            [
                                [
                                    'text' => __('admin.order_update'),
                                    'callback_data' => 'order_update/' . $order->id
                                ]
                            ]
                        ];

                    break;
                case 'delivered':

                    if ($order->driver_id != null && $order->driver_id != $this->user->id) return $this->getInlineKeyboard([]);

                    $options = [
                        [
                            [
                                'text' => __('admin.order_picked_and_got_on_the_way_button_activated'),
                                'callback_data' => 'order_picked_and_got_on_the_way'
                            ],
                            [
                                'text' => __('admin.order_delivered_and_got_payment_button_activated'),
                                'callback_data' => 'order_delivered_and_got_payment'
                            ],
                        ],
                        [
                            [
                                'text' => __('admin.order_update'),
                                'callback_data' => 'order_update/' . $order->id
                            ]
                        ]
                    ];
                    break;
                case 'completed':
                    if ($order->driver_id != null && $order->driver_id != $this->user->id) return $this->getInlineKeyboard([]);
                    $options = [
                        [
                            [
                                'text' => __('admin.order_picked_and_got_on_the_way_button_activated'),
                                'callback_data' => 'order_picked_and_got_on_the_way'
                            ],
                            [
                                'text' => __('admin.order_delivered_and_got_payment_button_activated'),
                                'callback_data' => 'order_delivered_and_got_payment'
                            ],
                        ],
                        [
                            [
                                'text' => __('admin.order_update'),
                                'callback_data' => 'order_update/' . $order->id
                            ]
                        ]
                    ];
                    break;
                case 'canceled':
                    $options = [];
                default:
                    $options = [
                        [
                            [
                                'text' => __('admin.order_update'),
                                'callback_data' => 'order_update/' . $order->id
                            ]
                        ]
                    ];
                    break;
            }
        }


//        dd($options);

        return $this->getInlineKeyboard($options);
    }
}
