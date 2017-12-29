<?php

namespace Selfreliance\PulseBitcoin;

use Illuminate\Http\Request;
use Config;
use Route;
use Log;

use Illuminate\Foundation\Validation\ValidatesRequests;

use Selfreliance\PulseBitcoin\Events\PulseBitcoinPaymentIncome;
use Selfreliance\PulseBitcoin\Events\PulseBitcoinPaymentCancel;

use Selfreliance\PulseBitcoin\PulseBitcoinInterface;
use Selfreliance\PulseBitcoin\Exceptions\PulseBitcoinException;
use GuzzleHttp\Client;

class PulseBitcoin implements PulseBitcoinInterface
{
	use ValidatesRequests;
	public $client;

	public function __construct(){
		$this->client = new Client([
		    'base_uri' => 'http://165.227.210.114/api/',
			'form_params' => [
		        'key' => 12345
		    ]		    
		]);
	}

	function balance($currency = 'BTC'){
		if($currency != 'BTC'){
			throw new \Exception('Only currency dash');	
		}
		$response = $this->client->request('POST', 'getbalance');
		$body     = $response->getBody();
		$code     = $response->getStatusCode();
		$resp     = json_decode($body->getContents());
		

		if(property_exists($resp, 'code')){
			throw new \Exception($resp->res->msg);
		}
		return $resp->result->confirmed+$resp->result->unconfirmed;
	}

	function form($payment_id, $sum, $units='BTC'){
		$sum = number_format($sum, 2, ".", "");

		$response = $this->client->request('POST', 'createnewaddress', [
			'form_params' => [
				'key'      => 12345,
		    ]
		]);
		$body     = $response->getBody();
		$code     = $response->getStatusCode();
		$resp     = json_decode($body->getContents());	
		$PassData = new \stdClass();

		if(property_exists($resp, 'code')){
			$PassData->error = $resp->res->msg;
		}else{
			$PassData->address = $resp->result;
			$PassData->another_site = false;
		}
		
		return $PassData;
	}

	public function check_transaction(array $request, array $server, $headers = []){
		
	}

	public function validateIPN(array $post_data, array $server_data){
		
	}

	public function validateIPNRequest(Request $request) {
        return $this->check_transaction($request->all(), $request->server(), $request->headers);
    }

	public function send_money($payment_id, $amount, $address, $currency){
		$fee = 0.0001;
		$response = $this->client->request('POST', 'payto', [
			'form_params' => [
				'key'      => '12345',
				'address'  => $address,
				'amount'   => $amount,
				'fee'      => $fee,
				'password' => 'password'
		    ]
		]);
		// $response = $this->client->request('POST', 'paytomany', [
		// 	'json' => [
		// 		'key'     => '12345',
		// 		'outputs' => [
		// 			[
		// 				$address,
		// 				0.0001,
		// 			]
		// 		],
		// 		'fee'      => $fee,
		// 		'password' => 'password'
		//     ]
		// ]);
		$body     = $response->getBody();
		$code     = $response->getStatusCode();
		$resp     = json_decode($body->getContents());

		Log::info('PulseBitcoin', [
			'request' => $resp
		]);

		if(property_exists($resp, 'code')){
			throw new \Exception($resp->res->msg);
		}

		if(property_exists($resp, 'result') && $resp->result[0] == true){
			$PassData              = new \stdClass();
			$PassData->transaction = $resp->result[1];
			$PassData->sending     = true;
			$PassData->add_info    = [
				"fee"       => $fee,
				"full_data" => $resp
			];
			return $PassData;
		}else{
			// throw new \Exception($resp->response->message);	
		}
	}

	public function cancel_payment(Request $request){

	}
}