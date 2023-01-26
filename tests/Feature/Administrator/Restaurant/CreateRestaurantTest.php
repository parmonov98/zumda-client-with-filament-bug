<?php

namespace Tests\Feature\Administrator\Restaurant;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CreateRestaurantTest extends TestCase
{
    use WithFaker;
    protected static array $data = [];


    function setVariables($reqData, $resData, $extra = []){
        if (count($extra) > 0) $data['extra'] = $extra;
        if (is_array($reqData)) $reqData = json_encode($reqData);
        $data['last_request'] = $reqData;
        $data['last_response'] = $resData;
        $data['last_message_id'] = $resData['message_id'] ?? null;
        $data['last_callback_query_id'] = $resData['callback_query']['id'] ?? null;
        return $data;
    }

    function &getData(){
        static $data = [];
        return $data;
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_listing($rerun = true)
    {

        $reqData = json_decode('{"update_id":794738640,"message":{"message_id":15315,"from":{"id":94665561,"is_bot":false,"first_name":"Murod","last_name":"Parmonov","username":"parmonov98","language_code":"en"},"chat":{"id":94665561,"first_name":"Murod","last_name":"Parmonov","username":"parmonov98","type":"private"},"date":1672465073,"text":"Restoranlar"}}', 1);
        $response = $this->withHeaders([
            'Accept' => 'json/application'
        ])->post(
            route('webhook'),
            $reqData
        );
//        dd($response->getContent());
        if ($response->assertStatus(200)
            ->assertJsonFragment([
                'text' => __("admin.restaurants_main_page")
            ])){
            $resData = $response->json();
            $newData = $this->setVariables($reqData, $resData);
            $data = &$this->getData();
            $data = $newData;
        }else{
            return new \Exception("No message_id");
        }

    }

    public function test_creating($rerun = true)
    {
        $data = &$this->getData();

        $message_id = $data['last_message_id'] ?? null;
        $reqData = str_replace('"message_id":MESSAGE_ID', '"message_id":' . $message_id, '{"update_id":794738529,"callback_query":{"id":"406585492721546143","from":{"id":94665561,"is_bot":false,"first_name":"Murod","last_name":"Parmonov","username":"parmonov98","language_code":"en"},"message":{"message_id":MESSAGE_ID,"from":{"id":5010992967,"is_bot":true,"first_name":"ZumdaDevBot2","username":"Zumda2DevBot"},"chat":{"id":94665561,"first_name":"Murod","last_name":"Parmonov","username":"parmonov98","type":"private"},"date":1672284970,"text":"Tizimdagi foydalanuvchilar bilan ishlash!","reply_markup":{"inline_keyboard":[[{"text":"Operatorlar","callback_data":"operators"}],[{"text":"Haydovchilar","callback_data":"drivers"}],[{"text":"Partnyor(restoran egasi)","callback_data":"partners"}],[{"text":"Partnyor operator","callback_data":"partner_operators"}],[{"text":"Yangi foydalanuvchi \u2795","callback_data":"add_new_user"}]]}},"chat_instance":"5724290121642304888","data":"add_new_restaurant"}}');
        $response = $this->withHeaders([
            'Accept' => 'json/application'
        ])->postJson(
            route('webhook'),
            json_decode($reqData, 1)
        );

        if ($response->assertStatus(200)
            ->assertSeeText(
                 "Restoran nomini kiriting"
            )){
            $resData = $response->json();
            $newData = $this->setVariables($reqData, $resData);
            $data = &$this->getData();
            $data = $newData;
        }else{
            return new \Exception("No message_id");
        }
    }

    public function test_entered_name()
    {
        $data = &$this->getData();
        $message_id = $data['last_message_id'] ?? null;
        $name = $this->faker->firstName . ' Cafe';
        $reqData = json_decode('{"update_id":794738657,"message":{"message_id":15407,"from":{"id":94665561,"is_bot":false,"first_name":"Murod","last_name":"Parmonov","username":"parmonov98","language_code":"en"},"chat":{"id":94665561,"first_name":"Murod","last_name":"Parmonov","username":"parmonov98","type":"private"},"date":1672466175,"text":":MESSAGE_TEXT"}}', 1);
        $reqData['message']['message_id'] = $message_id;
        $reqData['message']['text'] = $name;

        $response = $this->withHeaders([
            'Accept' => 'json/application'
        ])->postJson(
            route('webhook'),
            $reqData
        );

        if ($response->assertStatus(200)
            ->assertSeeText(
                "Restoran manzilini kiriting"
            )){
            $resData = $response->json();
            $newData = $this->setVariables($reqData, $resData, ['name' => $name]);
            $data = &$this->getData();
            $data = $newData;
        }else{
            return new \Exception("No message_id");
        }
    }

    public function test_entered_address()
    {
        $data = &$this->getData();
        $last_request = $data['last_request'];
        $address = $this->faker->address();
        $message_id = $data['last_message_id'] ?? null;
        $reqData = json_decode($last_request, 1);
        $reqData['message']['message_id'] = $message_id;
        $reqData['message']['text'] = $address;

        $response = $this->withHeaders([
            'Accept' => 'json/application'
        ])->postJson(
            route('webhook'),
            $reqData
        );

        if ($response->assertStatus(200)
            ->assertSeeText(
                "Restoran yangi lokatsiyasini yuboring"
            )){
            $resData = $response->json();
            $newData = $this->setVariables($reqData, $resData, ['address' => $address]);
            $data = &$this->getData();
            $data = $newData;
        }else{
            return new \Exception("No message_id");
        }
    }

    public function test_entered_longitde_and_latitude()
    {
        $data = &$this->getData();
        $last_request = $data['last_request'];
        $latitude = $this->faker->latitude();
        $longitude = $this->faker->longitude();
        $message_id = $data['last_message_id'] ?? null;

        $locationString = $latitude . ',' . $longitude;

        $reqData = json_decode($last_request, 1);
        $reqData['message']['message_id'] = $message_id;
        $reqData['message']['text'] = $locationString;

        $response = $this->withHeaders([
            'Accept' => 'json/application'
        ])->postJson(
            route('webhook'),
            $reqData
        );

        if ($response->assertStatus(200)
            ->assertSeeText(
                "Restoran xodimini tanlang:"
            )){
            $resData = $response->json();
            $newData = $this->setVariables($reqData, $resData, ['location' => $locationString]);
            $data = &$this->getData();
            $data = $newData;
        }else{
            return new \Exception("No location found");
        }
    }
    public function test_chosen_partner()
    {
        $data = &$this->getData();
        $last_request = $data['last_request'];
        $message_id = $data['last_message_id'] ?? null;
        $reString = '{"update_id":794738735,"callback_query":{"id":"406585489606024379","from":{"id":94665561,"is_bot":false,"first_name":"Murod","last_name":"Parmonov","username":"parmonov98","language_code":"en"},"message":{"message_id":15797,"from":{"id":5010992967,"is_bot":true,"first_name":"ZumdaDevBot2","username":"Zumda2DevBot"},"chat":{"id":94665561,"first_name":"Murod","last_name":"Parmonov","username":"parmonov98","type":"private"},"date":1672560043,"text":"Restoran xodimini tanlang:","reply_markup":{"inline_keyboard":[[{"text":"Xodim keyin qo\'shaman","callback_data":"add_new_restaurant_partner_account\/0"}]]}},"chat_instance":"5724290121642304888","data":"add_new_restaurant_partner_account\/0"}}';

        $callbackData = 'add_new_restaurant_partner_account/0'; // adding/attaching partner later
        $reqData = json_decode($reString, 1);
        $reqData['callback_query']['callback_query']['message_id'] = $message_id;
        $reqData['callback_query']['data'] = $callbackData;

        $response = $this->withHeaders([
            'Accept' => 'json/application'
        ])->postJson(
            route('webhook'),
            $reqData
        );

        if ($response->assertStatus(200)
            ->assertSeeText(
                json_decode(__("admin.restaurants_add_new_restaurant_done"), 1)
            )){
            $resData = $response->json();
            $newData = $this->setVariables($reqData, $resData, ['data' => $callbackData]);
            $data = &$this->getData();
            $data = $newData;
        }else{
            return new \Exception("No partner attached");
        }
    }

    public function test_cancel_creating()
    {
        app()->setLocale('uz');
        $reqData = json_decode('{"update_id":794738640,"message":{"message_id":15315,"from":{"id":94665561,"is_bot":false,"first_name":"Murod","last_name":"Parmonov","username":"parmonov98","language_code":"en"},"chat":{"id":94665561,"first_name":"Murod","last_name":"Parmonov","username":"parmonov98","type":"private"},"date":1672465073,"text":"'. __("admin.restaurants_cancel_creating") .'"}}', 1);
        $response = $this->withHeaders([
            'Accept' => 'json/application'
        ])->post(
            route('webhook'),
            $reqData
        );
        if ($response->assertStatus(200)){
            echo 'cancelled';
        }else{
            return new \Exception("No message_id");
        }

    }
}
