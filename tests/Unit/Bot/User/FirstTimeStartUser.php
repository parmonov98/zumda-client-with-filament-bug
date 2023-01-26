<?php

namespace Tests\Unit\Bot\User;

use PHPUnit\Framework\TestCase;

class FirstTimeStartUser extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_that_true_is_true()
    {
        $this->withHeaders([
            'Accept' => 'json/application'
        ])->post(
            route('webhook'),
            json_decode('{"update_id":794737916,"message":{"message_id":14245,"from":{"id":94665561,"is_bot":false,"first_name":"Murod","last_name":"Parmonov","username":"parmonov98","language_code":"en"},"chat":{"id":94665561,"first_name":"Murod","last_name":"Parmonov","username":"parmonov98","type":"private"},"date":1670301662,"text":"\/start","entities":[{"offset":0,"length":6,"type":"bot_command"}]}}')
        );
        $this->assertTrue(true);
    }
}
