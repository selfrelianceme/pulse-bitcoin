<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Selfreliance\PulseBitcoin\PulseBitcoin;
use Config;
class PulseBitcoinHandlerPayments extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        $data = [
			'tx' => [
				'_id'           => '5a55dc891c210a1db78ead18',
				'TxID'          => '825db5e990e887269558ea90eae50cf5e748c07ff6440482ecfcce0555e10187',
				'Confirmations' => 6,
				'HashPay'       => md5('5a55dc891c210a1db78ead18'.Config::get('pulsebitcoin.secret_key')),
				'Amount'        => 1,
				'Address' => [
					'14LDbJ4ZRxzb54MMzh8mY6uMx28Qy27uzY',
					'3PUg6jHN92zBVyBdFkiTJB8WopbAqWuxCE'
				]
			]
    	];
		$b = new PulseBitcoin();
		
    	$result = $b->check_transaction($data, [], []);
        // $this->assertTrue($result);
        $this->assertEquals(200, $result->status());
        // dd($result);

    }
}
