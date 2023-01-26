<?php

namespace App\Http\Controllers\Bot\Core\DTOs;

use App\Http\Controllers\Bot\Core\DTObject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserObject extends DTObject
{
    public int|null $telegram_id = null;
    public string $first_name = '';
    public string|null $last_name = null;
    public string|null $username = null;
    public User|null $user = null;
    public bool|null $is_bot = null;
    public string $language_code = '';

    public function fromRequest(Request $request){

        $message = $request->get('message');
        $callback = $request->get('callback_query');
        if ($message){
            $from = $message['from'];
            $this->telegram_id = $from['id'];
            $this->username = $from['username'] ?? null;
            $this->is_bot = $from['is_bot'] ?? null;
            $this->first_name = $from['first_name'];
            $this->last_name = $from['last_name'] ?? null;
            $this->language_code = $from['language_code'] ?? null;
        }
        if ($callback){
            $from = $callback['from'];
            $this->telegram_id = $from['id'];
            $this->username = $from['username'] ?? null;
            $this->is_bot = $from['is_bot'] ?? null;
            $this->first_name = $from['first_name'];
            $this->last_name = $from['last_name'] ?? null;
            $this->language_code = $from['language_code'] ?? null;
        }
    }

    public static function fromWebhook(array $params)
    {

        return new self([
            'checkout_id' => $params['id'],
            'completed_at' => $params['completed_at']
        ]);

    }
}
