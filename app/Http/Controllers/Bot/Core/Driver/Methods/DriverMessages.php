<?php

namespace App\Http\Controllers\Bot\Core\Driver\Methods;

use App\Models\Review;
use App\Models\User;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

trait DriverMessages
{


    // driver mode disabled
    public function greetDriverPlateRequired()
    {
        $text = __('driver.plate_required');
        $this->sendMessage($text, 'HTML', []);
        return false;
    }

    // driver mode disabled
    public function greetDriverPhoneRequired()
    {
        $text = __('driver.phone_required');
        $this->sendMessage($text, 'HTML', []);
        return false;
    }


    public function getMainDriverMenu($driver = null)
    {
        $options = [];
        if ($driver){
            if ($driver->self_status){
                if ($driver->self_status == true){
                    $options = [
                        [
                            ['text' => __('driver.keyboard_statistics')],
                            ['text' => __('driver.keyboard_on_activated')],
                        ],
                    ];
                }else{
                    $options = [
                        [
                            ['text' => __("driver.keyboard_statistics")],
                            ['text' => __("driver.keyboard_off_activated")],
                        ],
                    ];
                }
            }else{
                $options = [
                    [
                        ['text' => __("driver.keyboard_statistics")],
                        ['text' => __("driver.keyboard_off_activated")],
                    ],
                ];
            }
        }
        return $this->getKeyboard($options, $resize = true);
    }

    // with inline buttons
    public function getDriverTurnOnOfMenu($driver = null)
    {
        $options = [];
        if ($driver){

            if ($driver->self_status){
                if ($driver->self_status == 'active'){
                    $options = [
                        [
                            [
                                'text' => __('driver.keyboard_switch_on_activated'),
                                'callback_data' => 'keyboard_switch_on'
                            ],
                        ],
                        [
                            [
                                'text' => __('driver.keyboard_switch_off'),
                                'callback_data' => 'keyboard_switch_off'
                            ],
                        ],
                    ];
                }else{
                    $options = [
                        [
                            [
                                'text' => __('driver.keyboard_switch_on'),
                                'callback_data' => 'keyboard_switch_on'
                            ],
                        ],
                        [
                            [
                                'text' => __('driver.keyboard_switch_off_activated'),
                                'callback_data' => 'keyboard_switch_off'
                            ],
                        ],
                    ];
                }
            }else{

                $options = [
                    [
                        [
                            'text' => __('driver.keyboard_switch_on'),
                            'callback_data' => 'keyboard_switch_on'
                        ],
                    ],
                    [
                        [
                            'text' => __('driver.keyboard_switch_off'),
                            'callback_data' => 'keyboard_switch_off'
                        ],
                    ],
                ];
            }
        }
        return $this->getInlineKeyboard($options, $resize = true);
    }


    public function sendMainDriverMenu()
    {
        $this->userService->resetSteps($this->user->telegram_id);

        $text = __("driver.welcome_to_the_team", [
            'name'  => htmlspecialchars($this->user->first_name . ' ' . $this->user->last_name),
            'role' => __('Haydovchi')
        ]);
        $menu = $this->getMainDriverMenu($this->user);

        $this->sendMessage($text, 'HTML', $menu);
    }


    public function sendEnableDriverAvailibilityMessage()
    {
        $text = __('driver.availability_text_keyboard_on_message');
        $markup = $this->getMainDriverMenu($this->user->driver);
        $res = $this->sendMessage($text, 'HTML', $markup);
    }
    public function sendDisableDriverAvailibilityMessage()
    {
        $text = __('driver.availability_text_keyboard_off_message');
        $markup = $this->getMainDriverMenu($this->user->driver);
        $res = $this->sendMessage($text, 'HTML', $markup);
    }


    // driver off by admin
    public function greetOffDriver()
    {
        $text = __('driver.inactive_mode', ['name'  => $this->user?->first_name]);
        $this->sendMessage($text, 'HTML', []);
        return false;
    }
}
