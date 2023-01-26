<?php

namespace Tests\Feature\Administrator\Operator;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CreateOperatorTest extends TestCase
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
    public function test_before_creating()
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

    public function test_creating()
    {
        $data = $this->test_before_creating();
        $message_id = $data['last_message_id'] ?? null;
        $reqData = str_replace('"message_id":MESSAGE_ID', '"message_id":' . $message_id, '{"update_id":794738529,"callback_query":{"id":"406585492721546143","from":{"id":94665561,"is_bot":false,"first_name":"Murod","last_name":"Parmonov","username":"parmonov98","language_code":"en"},"message":{"message_id":MESSAGE_ID,"from":{"id":5010992967,"is_bot":true,"first_name":"ZumdaDevBot2","username":"Zumda2DevBot"},"chat":{"id":94665561,"first_name":"Murod","last_name":"Parmonov","username":"parmonov98","type":"private"},"date":1672284970,"text":"Tizimdagi foydalanuvchilar bilan ishlash!","reply_markup":{"inline_keyboard":[[{"text":"Operatorlar","callback_data":"operators"}],[{"text":"Haydovchilar","callback_data":"drivers"}],[{"text":"Partnyor(restoran egasi)","callback_data":"partners"}],[{"text":"Partnyor operator","callback_data":"partner_operators"}],[{"text":"Yangi foydalanuvchi \u2795","callback_data":"add_new_user"}]]}},"chat_instance":"5724290121642304888","data":"add_new_user"}}');
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

    public function test_creating_choose_role()
    {
        $data = $this->test_creating();
        $request = $data['last_request'] ?? null;
        $message_id = $data['last_message_id'] ?? null;

        $reqData = str_replace('"message_id":MESSAGE_ID', '"message_id":' . $message_id, '{"update_id":794738539,"callback_query":{"id":"406585492681487362","from":{"id":94665561,"is_bot":false,"first_name":"Murod","last_name":"Parmonov","username":"parmonov98","language_code":"en"},"message":{"message_id":MESSAGE_ID,"from":{"id":5010992967,"is_bot":true,"first_name":"ZumdaDevBot2","username":"Zumda2DevBot"},"chat":{"id":94665561,"first_name":"Murod","last_name":"Parmonov","username":"parmonov98","type":"private"},"date":1672454113,"edit_date":1672454113,"text":"Yangi xodimning rolini tanlang:","reply_markup":{"inline_keyboard":[[{"text":"Operator","callback_data":"choose_operator"}],[{"text":"Haydovchi","callback_data":"choose_driver"}],[{"text":"Restoran xodimi","callback_data":"choose_partner"}],[{"text":"Ortga","callback_data":"back_to_users"}]]}},"chat_instance":"5724290121642304888","data":"choose_operator"}}');
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
            $entity = $resData['entities'][0];
            $text = $resData['text'];
            $activation_code = substr($text, $entity['offset'], $entity['length']);

            $data['last_message_id'] = $resData['message_id'];
            $data['activation_code'] = $activation_code;

            return $this->setVariables($reqData, $resData);
        }else{
            return new \Exception("No message_id");
        }
    }

}
