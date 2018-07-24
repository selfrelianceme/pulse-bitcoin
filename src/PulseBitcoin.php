<?php

namespace Selfreliance\PulseBitcoin;

use Illuminate\Http\Request;
use Config;
use Route;
use Log;

use Illuminate\Foundation\Validation\ValidatesRequests;

use Selfreliance\PulseBitcoin\Events\PulseBitcoinPaymentIncome;
use Selfreliance\PulseBitcoin\Events\PulseBitcoinPaymentCancel;
use Selfreliance\PulseBitcoin\Events\PulseBitcoinPaymentConfirms;

use Selfreliance\PulseBitcoin\PulseBitcoinInterface;
use Selfreliance\PulseBitcoin\Exceptions\PulseBitcoinException;
use GuzzleHttp\Client;
use App\Models\Users_History;

class PulseBitcoin implements PulseBitcoinInterface
{
	use ValidatesRequests;
	public $client;

	public function __construct(){
		$this->client = new Client([
		    'base_uri' => Config::get('pulsebitcoin.base_uri'),
			'form_params' => [
		        'key' => Config::get('pulsebitcoin.secret_key')
		    ]		    
		]);
	}

	function balance($currency = 'BTC'){
		$response = $this->client->request('POST', 'getbalance');
		$body     = $response->getBody();
		$code     = $response->getStatusCode();
		$resp     = json_decode($body->getContents());
		

		if(property_exists($resp, 'code')){
			throw new \Exception($resp->res->msg);
		}
		$unconfirmed = 0;
		if(property_exists($resp->result, 'unconfirmed')){
			$unconfirmed = $resp->result->unconfirmed;
		}
		return $resp->result->confirmed+$unconfirmed;
	}

	function form($payment_id, $sum, $units='BTC'){
		$sum = number_format($sum, 2, ".", "");
		$response = $this->client->request('POST', 'createnewaddress', [
			'form_params' => [
				'key'      => Config::get('pulsebitcoin.secret_key'),
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

	public function check_transaction(array $request, array $server, $headers = [], $ip = ''){
		Log::info('Pulse Bitcoin IPN', [
			'request' => $request
		]);
		$textReponce = [
			'_id'    => null,
			'msg'    => 'Server error pulse bitcoin',
			'status' => 'error'
		];
		try{
			$is_complete = $this->validateIPN($request, $server, $ip);
			if($is_complete){
				
				$PassData                     = new \stdClass();
				$PassData->amount             = $request['tx']['Amount'];
				$PassData->payment_id         = $request['tx']['Address'];
				$PassData->transaction        = $request['tx']['TxID'];
				$PassData->add_info           = [
					"full_data_ipn" => json_encode($request)
				];
				event(new PulseBitcoinPaymentIncome($PassData));
				
				$textReponce = [
					'_id'    => $request['tx']['_id'],
					'msg'    => 'Payment successfully confirm',
					'status' => 'success'
				];

				// TODO
				// change seach transaction
				// $history = Users_History::
				// 	where('type', 'CREATE_DEPOSIT')->
				// 	whereIn('payment_system', [1,2])->
				// 	where('status', 'pending')->
				// 	where('transaction', '')->
				// 	where('data_info->address', $request['tx']['Address'])->
				// 	value('id');
				// if($history){
				// 	$PassData                     = new \stdClass();
				// 	$PassData->amount             = $request['tx']['Amount'];
				// 	$PassData->payment_id         = $history;
				// 	$PassData->transaction        = $request['tx']['TxID'];
				// 	$PassData->add_info           = [
				// 		"full_data_ipn" => json_encode($request)
				// 	];
				// 	event(new PulseBitcoinPaymentIncome($PassData));
				// 	$textReponce = [
				// 		'_id'    => $request['tx']['_id'],
				// 		'msg'    => 'Payment successfully confirm',
				// 		'status' => 'success'
				// 	];
				// }else{
				// 	Log::notice('Pulse Bitcoin IPN', [
				// 		'message' => 'Don\'t find history',
				// 		'data'    => $request
				// 	]);
				// 	$textReponce = [
				// 		'_id'    => $request['tx']['_id'],
				// 		'msg'    => 'Don\'t find in history',
				// 		'status' => 'error_find_history'
				// 	];
				// }
			}else{
				$textReponce = [
					'_id'    => $request['tx']['_id'],
					'status' => 'error'
				];
			}		
		}catch(PulseBitcoinException $e){
			Log::notice('Pulse Bitcoin Exception', [
				'message' => $e->getMessage(),
				'data'    => $request
			]);
			
			$textReponce = [
				'_id'    => (isset($request['tx']['_id']))?$request['tx']['_id']:null,
				'msg'    => $e->getMessage(),
				'status' => 'continue'
			];
		}

		return \Response::json($textReponce, "200");
	}

	public function validateIPN(array $post_data, array $server_data, $ip){
		if(!isset($post_data['tx']['Confirmations'])){
			throw new PulseBitcoinException("Missing the required confirmations");
		}
		
		$PassData                = new \stdClass();
		$PassData->confirmations = $post_data['tx']['Confirmations'];
		$PassData->transaction   = $post_data['tx']['TxID'];
		$PassData->amount        = $post_data['tx']['Amount'];
		$PassData->address       = $post_data['tx']['Address'];
		event(new PulseBitcoinPaymentConfirms($PassData));

		if($post_data['tx']['Confirmations'] < 6){
			throw new PulseBitcoinException("Missing the required number of confirmations ".$post_data['tx']['Confirmations'].' of 6');
		}

		if(!isset($post_data['tx']['TxID'])){
			throw new PulseBitcoinException("Need transaction");	
		}

		if(!isset($post_data['tx']['Amount'])){
			throw new PulseBitcoinException("Missing the required amount");
		}

		if($post_data['tx']['Amount'] <= 0){
			throw new PulseBitcoinException("Need amount for transaction");	
		}

		if(!isset($post_data['tx']['HashPay'])){
			throw new PulseBitcoinException("The transaction need HashPay");	
		}

		if(!isset($post_data['tx']['_id'])){
			throw new PulseBitcoinException("The transaction need identy");	
		}

		// if($ip != Config::get('pulsebitcoin.ip_server')){
		// 	throw new PulseBitcoinException("Not verify the IP address");		
		// }

		if($post_data['tx']['HashPay'] != md5($post_data['tx']['_id'].Config::get('pulsebitcoin.secret_key'))){
			throw new PulseBitcoinException("The transaction failed to authenticate");	
		}

		// if($post_data['tx']['timestamp'] == false){
		// 	throw new PulseBitcoinException("Need timestamp");	
		// }

		if(!isset($post_data['tx']['Address'])){
			throw new PulseBitcoinException("Need Address");	
		}

		return true;
	}

	public function validateIPNRequest(Request $request) {
        $ip = real_ip();
        return $this->check_transaction($request->all(), $request->server(), $request->headers, $ip);
    }

	public function send_money($payment_id, $amount, $address, $currency){
		$fee = 0.0003;
		$response = $this->client->request('POST', 'payto', [
			'form_params' => [
				'key'      => (string)Config::get('pulsebitcoin.secret_key'),
				'address'  => $address,
				'amount'   => $amount,
				'fee'      => $fee,
				'password' => Config::get('pulsebitcoin.password')
		    ]
		]);
		// $response = $this->client->request('POST', 'paytomany', [
		// 	'json' => [
		// 		'key'     => (string)Config::get('pulsebitcoin.secret_key'),
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

		// Log::info('PulseBitcoin send transaction', [
		// 	'request' => $resp
		// ]);

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
			throw new \Exception($resp->error->message);	
		}
	}

	public function cancel_payment(Request $request){

	}
	/**
	 *
	 *[
	 *	'address', amount
	 *]
	 *
	 * 
	 */
	public function send_multi($address_and_amount){
		$fee = 0.0003;
		$response = $this->client->request('POST', 'paytomany', [
			'json' => [
				'key'      => (string)Config::get('pulsebitcoin.secret_key'),
				'outputs'  => $address_and_amount,
				'fee'      => $fee,
				'password' => Config::get('pulsebitcoin.password')
		    ]
		]);
		$body     = $response->getBody();
		$code     = $response->getStatusCode();
		$resp     = json_decode($body->getContents());

		// Log::info('PulseBitcoin send multi', [
		// 	'request' => $resp
		// ]);

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
			throw new \Exception($resp->error->message);	
		}
	}

	public function history(){
		$response = $this->client->request('POST', 'history', [
			'form_params' => [
				'key'      => Config::get('pulsebitcoin.secret_key'),
		    ]
		]);
		$body     = $response->getBody();
		$code     = $response->getStatusCode();
		$resp     = json_decode($body->getContents());

		return $resp;
	}

	public function balance_address($address){
		$response = $this->client->request('POST', 'getaddressbalance', [
			'form_params' => [
				'key'     => Config::get('pulsebitcoin.secret_key'),
				'address' => $address,
		    ]
		]);
		$body     = $response->getBody();
		$code     = $response->getStatusCode();
		$resp     = json_decode($body->getContents());

		return $resp;
	}
}