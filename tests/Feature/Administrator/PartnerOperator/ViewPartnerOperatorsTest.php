<?php

namespace Tests\Feature\Administrator\PartnerOperator;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ViewPartnerOperatorsTest extends TestCase
{
    protected array $data = [];

    function setVariables($reqData, $resData){
        $data['last_request'] = $reqData;
        $data['last_response'] = $resData;
        $data['last_message_id'] = $resData['message_id'] ?? null;
        $data['last_callback_query_id'] = $resData['callback_query']['id'] ?? null;
        return $data;
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_listing()
    {
        $reqData = json_decode('{"update_id":794738525,"message":{"message_id":14950,"from":{"id":94665561,"is_bot":false,"first_name":"Murod","last_name":"Parmonov","username":"parmonov98","language_code":"en"},"chat":{"id":94665561,"first_name":"Murod","last_name":"Parmonov","username":"parmonov98","type":"private"},"date":1672283385,"text":"Foydalanuvchilar"}}', 1);
        $response = $this->withHeaders([
            'Accept' => 'json/application'
        ])->post(
            route('webhook'),
            $reqData
        );
        if ($response->assertStatus(200)
            ->assertJsonStructure([
                'reply_markup' => [
                    'inline_keyboard' => [
                        '*'=> [
                            '*' => [
                                'text',
                                'callback_data'
                            ]
                        ]
                    ]
                ]
            ])){
            $resData = $response->json();
            return $this->setVariables($reqData, $resData);
        }else{
            return new \Exception("No message_id");
        }

    }

    public function test_viewing()
    {
        $data = $this->test_listing();
        $message_id = $data['last_message_id'] ?? null;
        $reqData = str_replace('"message_id":MESSAGE_ID', '"message_id":' . $message_id, '{"update_id":794738529,"callback_query":{"id":"406585492721546143","from":{"id":94665561,"is_bot":false,"first_name":"Murod","last_name":"Parmonov","username":"parmonov98","language_code":"en"},"message":{"message_id":MESSAGE_ID,"from":{"id":5010992967,"is_bot":true,"first_name":"ZumdaDevBot2","username":"Zumda2DevBot"},"chat":{"id":94665561,"first_name":"Murod","last_name":"Parmonov","username":"parmonov98","type":"private"},"date":1672284970,"text":"Tizimdagi foydalanuvchilar bilan ishlash!","reply_markup":{"inline_keyboard":[[{"text":"Operatorlar","callback_data":"operators"}],[{"text":"Haydovchilar","callback_data":"drivers"}],[{"text":"Partnyor(restoran egasi)","callback_data":"partners"}],[{"text":"Partnyor operator","callback_data":"partner_operators"}],[{"text":"Yangi foydalanuvchi \u2795","callback_data":"add_new_user"}]]}},"chat_instance":"5724290121642304888","data":"partner_operators"}}');
        $response = $this->withHeaders([
            'Accept' => 'json/application'
        ])->postJson(
            route('webhook'),
            json_decode($reqData, 1)
        );
        if ($response->assertStatus(200)
            ->assertJsonStructure([
                'reply_markup' => [
                    'inline_keyboard' => [
                        '*'=> [
                            '*' => [
                                'text',
                                'callback_data'
                            ]
                        ]
                    ]
                ]
            ])){
            $resData = $response->json();
            return $this->setVariables($reqData, $resData);
        }else{
            return new \Exception("No message_id");
        }
    }
}
