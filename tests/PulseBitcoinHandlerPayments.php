<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Selfreliance\PulseBitcoin\PulseBitcoin;
class PulseBitcoinHandlerPayments extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
  //       $data = [
		// 	'tx' => [
		// 		'confirmations' => 0,
		// 		'height' => -1,
		// 		'output_addresses' => [
		// 			'1BF8oPtfCFsL6LgESQmvyhQHssMSVPoTeU',
		// 			'3PUg6jHN92zBVyBdFkiTJB8WopbAqWuxCE'
		// 		],
		// 		'value' => "0.001",
		// 		'input_addresses' => [
		// 			'3MwPN5MQzeiNWykhm3fhMP7nMAywegLghq',
		// 			'3FKofxhSRJgg2DH1RpMY4zXtBzqct5YKit'
		// 		],
		// 		'label' => null,
		// 		'txid' => 'ae02eb3887fc3808951543aafcaa8a54b42541ab5c045bba082154f29a09791d',
		// 		'date' => '----', 
		// 		'timestamp' => false
		// 	]
  //   	];
		// $b = new PulseBitcoin();
		
  //   	$result = $b->check_transaction($data, [], []);
  //       $this->assertTrue($result);


        $data = [
			'tx' => [
				'_id'           => '5a55dc891c210a1db78ead18',
				'TxID'          => '825db5e990e887269558ea90eae50cf5e748c07ff6440482ecfcce0555e10187',
				'Confirmations' => 6,
				'HashPay'       => md5('5a55dc891c210a1db78ead18'.'12345'),
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
