<?php

namespace App\Http\Controllers\Bot\Core;

use App\Http\Controllers\Bot\Core\DTOs\UserObject;
use App\Models\Order;
use App\Models\User;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

trait RequestTrait
{



    public function setLang($lang = null)
    {
        if ($lang == null) {
            if ($this->user != null) {
                App::setLocale($this->user->language);
            } else {
                App::setLocale('uz');
            }
        } else {
            App::setLocale($lang);
        }
    }

    public function getUser($userID)
    {
        return $this->userService->getByTelegramID($userID);
    }
    public function setUser($userID)
    {
        $this->user = $this->getUser($userID);
        // creating if the user have no cart
        if ($this->user === null) {

            $userDTO = new UserObject();
            $userDTO->fromRequest($this->request);
            $this->user = $this->userService->saveUserData($userDTO);
            $this->setLang('uz');
        }

        if ($this->user->cart == null) {
            $this->user->cart()->create([
                'summary' => 0
            ]);
        }
        $this->setLang($this->user->language);
        return true;
    }



    public function sendMessage($text, $parse_mode = null, $reply_markup = [], $reply_to_message_id = null, $disable_web_page_preview = false)
    {
        $data = [
            'text' => $text
        ];
        if ($this->user && $this->user != null) {
            $data['chat_id'] = $this->user->telegram_id;
        } elseif (isset($this->message)) {
            $data['chat_id'] = $this->message['from']['id'];
        } else {
            $data['chat_id'] = $this->callback_query['from']['id'];
        }
        if ($parse_mode != null) {
            $data['parse_mode'] = $parse_mode;
        }
        if ($parse_mode != null) {
            $data['parse_mode'] = $parse_mode;
        }
        if ($reply_markup != []) {
            $data['reply_markup'] = $reply_markup;
        }
        if ($reply_to_message_id != null) {
            $data['reply_to_message_id'] = $reply_to_message_id;
        }
        $data['disable_web_page_preview'] = $disable_web_page_preview;
        return $this->postRequest('sendMessage', $data);
    }

    public function sendMessage2($chat_id = null, $text = '-', $parse_mode = null, $reply_markup = [], $reply_to_message_id = null, $disable_web_page_preview = false)
    {
        $data = [
            'text' => $text
        ];

        if ($chat_id != null){
            $data['chat_id'] = $chat_id;
        }else{
            if ($this->user != null) {
                $data['chat_id'] = $this->user->telegram_id;
            } elseif (isset($this->message)) {
                $data['chat_id'] = $this->message['from']['id'];
            } else {
                $data['chat_id'] = $this->callback_query['from']['id'];
            }
        }
        if ($parse_mode != null) {
            $data['parse_mode'] = $parse_mode;
        }
        if ($reply_markup != []) {
            $data['reply_markup'] = $reply_markup;
        }
        if ($reply_to_message_id != null) {
            $data['reply_to_message_id'] = $reply_to_message_id;
        }
        $data['disable_web_page_preview'] = $disable_web_page_preview;
        return $this->postRequest('sendMessage', $data);
    }

    public function editMessageText($message_id = null, $text = '-', $parse_mode = null, $reply_markup = [], $disable_web_page_preview = false)
    {
        $data = [
            'text' => $text
        ];

        if ($message_id != null) {
            $data['message_id'] = $message_id;
        } else {
            $data['message_id'] = $this->callback_query['message']['message_id'];
        }


        if ($this->user != null) {
            $data['chat_id'] = $this->user->telegram_id;
        } elseif (isset($this->message)) {
            $data['chat_id'] = $this->message['from']['id'];
        } else {
            $data['chat_id'] = $this->callback_query['from']['id'];
        }
        if ($parse_mode != null) {
            $data['parse_mode'] = $parse_mode;
        }
        if ($reply_markup != []) {
            $data['reply_markup'] = $reply_markup;
        }
        $data['disable_web_page_preview'] = $disable_web_page_preview;
        return $this->postRequest('editMessageText', $data);
    }

    public function editMessageCaption($message_id = null, $caption = '-', $parse_mode = null, $reply_markup = [])
    {
        $data = [
            'caption' => $caption
        ];

        if ($message_id != null) {
            $data['message_id'] = $message_id;
        } else {
            $data['message_id'] = $this->callback_query['message']['message_id'];
        }


        if ($this->user != null) {
            $data['chat_id'] = $this->user->telegram_id;
        } elseif (isset($this->message)) {
            $data['chat_id'] = $this->message['from']['id'];
        } else {
            $data['chat_id'] = $this->callback_query['from']['id'];
        }
        if ($parse_mode != null) {
            $data['parse_mode'] = $parse_mode;
        }
        if ($reply_markup != []) {
            $data['reply_markup'] = $reply_markup;
        }
        return $this->postRequest('editMessageCaption', $data);
    }


    public function deleteMessage($chat_id, $message_id)
    {
        $data = [
            'chat_id' => $chat_id,
            'message_id' => $message_id
        ];

        return $this->postRequest('deleteMessage', $data);
    }

    public function sendInvoice($chat_id, array $orderData = [], $title = 'Test zakaz',  $summary = '', array $prices = [])
    {

        // dd($orderData);

        $data = [
            'chat_id' => $chat_id,
            'title' => $title,
            'description' => $summary,
            'payload' => $orderData['order_id'],
            'provider_token' => env('CLICKUZ_BOT_PAYMENT_API_LIVE_TOKEN', '333605228:LIVE:13352_4384DECF8863F5CB89B2F70152CB2504323AFC77'),
            // 'provider_token' => env('STRIPE_BOT_PAYMENT_API_TEST_TOKEN', '371317599:TEST:1622369768686'),
            // 'provider_token' => env('PAYMEUZ_BOT_PAYMENT_API_TEST_TOKEN', '371317599:TEST:1622369768686'),
            // 'provider_token' => env('CLICKUZ_BOT_PAYMENT_API_TEST_TOKEN', '398062629:TEST:999999999_F91D8F69C042267444B74CC0B3C747757EB0E065'),
            'currency' => 'UZS',
            'prices' => json_encode($prices),
            'start_parameter' => 'start_parameter',
        ];
        if ($orderData['payment_method'] == 'payme') {
            $data['provider_token'] = env('PAYMEUZ_BOT_PAYMENT_API_LIVE_TOKEN', '371317599:TEST:1622369768686');
        }

        // dd($data);

        return $this->postRequest('sendInvoice', $data);
    }

    public function answerPreCheckoutQuery($pre_checkout_query_id = null)
    {
        $data = [
            'ok' => true
        ];
        $data['pre_checkout_query_id'] = $pre_checkout_query_id;
        if ($pre_checkout_query_id == null) {
            $data['pre_checkout_query_id'] = $this->message['pre_checkout_query']['id'];
        }

        return $this->postRequest('answerPreCheckoutQuery', $data);
    }

    public function sendPhoto($chat_id, $file_id, $caption = '', $markup = [])
    {
        $data = [
            'chat_id' => $chat_id,
            'photo' => $file_id,
            'parse_mode' => 'HTML'
        ];
        if ($markup != []) {
            $data['reply_markup'] = $markup;
        }
        if ($caption != '') {
            $data['caption'] = $caption;
        }

        return $this->postRequest('sendPhoto', $data);
    }
//    public function uploadFile($chat_id, $file, $caption = '', $markup = [])
//    {
//
//        $data = [
//            'chat_id' => $chat_id,
//            'document' => $file,
//            'parse_mode' => 'HTML'
//        ];
//        if ($markup != []) {
//            $data['reply_markup'] = $markup;
//        }
//        if ($caption != '') {
//            $data['caption'] = $caption;
//        }
//
//        dd($data);
//        return $this->postRequest('sendDocument', $data);
//    }


    public function answerCallbackQuery($text = '', bool $show_alert = true, $url = null)
    {

        if (isset($this->request['callback_query'])){
            $data = [
                'callback_query_id' => $this->request['callback_query']['id'],
            ];
            if ($this->user != null) {
                $data['chat_id'] = $this->user->telegram_id;
            } else {
                $data['chat_id'] = $this->request['callback_query']['from']['id'];
            }

            if ($text !== '') {
                $data['text'] = $text;
            }
            if ($show_alert === true) {
                $data['show_alert'] = true;
            }
            if ($url !== null) {
                $data['url'] = $url;
            }

            return $this->postRequest('answerCallbackQuery', $data);
        }
    }

    public function answerCallbackQueryTest($user_id, $text = '', $show_alert = true, $url = null)
    {
        $data['chat_id'] = $user_id;

        if ($text !== '') {
            $data['text'] = $text;
        }
        if ($show_alert === true) {
            $data['show_alert'] = true;
        }
        if ($url !== null) {
            $data['url'] = $url;
        }

        return $this->postRequest('answerCallbackQuery', $data);
    }

    public function postRequest($method, $params, $request_type = 'json')
    {

        if (!config('bot.BOT_DEBUG')) {
            $url = 'https://api.telegram.org/bot' . config('bot.token') . '/' . $method;
        } else {
            $url = 'http://localhost:5555/bot' . config('bot.token') . '/' . $method;
        }

        if ($request_type == 'multipart/form-data'){
            return $this->makeRequest('POST', $url, ['multipart' => $params]);
        }else{
            return $this->makeRequest('POST', $url, ['form_params' => $params, 'headers' => []]);
        }
    }

    public function getRequest($method, $params)
    {

        // dd(env('APP_ENV'));
        if (env('APP_ENV') == 'production') {
            $url = 'https://api.telegram.org/bot' . env('TELEGRAM_BOT_TOKEN', '5010992967:AAHFO45kiUqgZnDZQ4CvmVO-XoBFdVWtzuQ') . '/' . $method;
        } else {
            $url = 'http://localhost:5555/bot' . env('TELEGRAM_BOT_TOKEN', '5010992967:AAHFO45kiUqgZnDZQ4CvmVO-XoBFdVWtzuQ') . '/' . $method;
        }

        return $this->makeRequest('GET', $url, ['query' => $params]);
    }
    public function getFile($file_id)
    {

        if (env('APP_ENV') == 'production') {
            $url = 'https://api.telegram.org/file/bot' . env('TELEGRAM_BOT_TOKEN') . '/' . $file_id;
        } else {
            $url = 'http://localhost:5555/file/bot' . env('TELEGRAM_BOT_TOKEN') . '/' . $file_id;
        }

        // dd($url);
        return $this->makeRequest('GET', $url, ['query' => []]);
    }


    public function toMultiPart(array $arr) {
        $result = [];
        array_walk($arr, function($value, $key) use(&$result) {
            $result[] = ['name' => $key, 'contents' => $value];
        });
        return $result;
    }

    public function sendDocument($chat_id, $file_path, $caption = '', $markup = [])
    {
        $data = [
            'chat_id' => $chat_id,
            'parse_mode' => 'HTML',
            'document' => new \CurlFile($file_path)
        ];
        if ($markup != []) {
            $data['reply_markup'] = $markup;
        }
        if ($caption != '') {
            $data['caption'] = $caption;
        }
        if (env('APP_ENV') == 'production') {
            $url = 'https://api.telegram.org/bot' . env('TELEGRAM_BOT_TOKEN', '5010992967:AAHFO45kiUqgZnDZQ4CvmVO-XoBFdVWtzuQ') . '/sendDocument';
        } else {
            $url = 'http://localhost:5555/bot' . env('TELEGRAM_BOT_TOKEN', '5010992967:AAHFO45kiUqgZnDZQ4CvmVO-XoBFdVWtzuQ') . '/sendDocument';
        }
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            $res = curl_exec ($ch);
            curl_close ($ch);
        }catch (\Exception $e){
//            dd($e->getMessage());
            $res = $e->getMessage();
        }
        return $res;
    }


    protected function makeRequest($method, $url, $params)
    {
//         dd($this->toMultiPart($params['multipart']));
//         dd($params);
        // echo $url;
        $client = new Client();
        try {
            if ($method == 'GET') {
                $res = $client->request($method, $url, $params);
            } else {
                $res = $client->request($method, $url, $params);
            }
        } catch (ClientException $e){
            $res = $e->getResponse();

        }
        catch (\Exception $e) {

            dd($e);

            if (config('bot.debug')){
                return $e->getMessage();
            }
            return false;
        }

//        dd($res);
//        dd($res instanceof ClientException, json_decode($res->getBody(), 1));

        if ($res instanceof Response)
            if ($res->getStatusCode() !== 200) {
                return json_decode($res->getBody(), 1);
            }

        if ($res->getStatusCode() !== 200) {
            return false;
        }

        $response = json_decode($res->getBody(), 1);
        if (isset($response['ok']) && $response['ok'] !== true) {
            return false;
        }
        if (isset($response['result'])) {
            return $response['result'];
        }

        return $response;
    }

    protected function postJsonRequest($url, $params)
    {
        // dd($params, $url);
        $client = new Client();
        try {

            $response = $client->post($url, [
                'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'    => json_encode($params)
            ]);

            // dd($res);
        } catch (Exception $e) {
            echo  $e->getMessage();
            echo  $e->getCode();
            // dd($e);
            $response = $e->getResponse();
            if ($e->getCode() == 400) {
                return json_decode($response->getBody(), 1);
            }
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }


        $response = json_decode($response->getBody(), 1);
        if (isset($response['ok']) && $response['ok'] !== true) {
            return false;
        }
        // dd($response);
        if (isset($response['result'])) {
            return $response['result'];
        }

        return $response;
    }
}
