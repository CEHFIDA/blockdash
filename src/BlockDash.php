<?php

namespace Selfreliance\BlockDash;

use Illuminate\Http\Request;
use Config;
use Route;

use Illuminate\Foundation\Validation\ValidatesRequests;

use Selfreliance\BlockDash\Events\BlockDashPaymentIncome;
use Selfreliance\BlockDash\Events\BlockDashPaymentCancel;

use Selfreliance\BlockDash\BlockDashInterface;
use Selfreliance\BlockDash\Exceptions\BlockDashException;
use GuzzleHttp\Client;

class BlockDash implements BlockDashInterface
{
	use ValidatesRequests;
	public $client;

	public function __construct(){
		$this->client = new Client([
		    'base_uri' => 'https://blockdash.io/api/v1/',
			'headers' => [
		        'auth-api-token' => Config::get('blockdash.token')
		    ]		    
		]);
	}

	function balance($currency = 'DASH'){
		if($currency != 'DASH'){
			throw new \Exception('Only currency dash');	
		}
		$response = $this->client->request('GET', 'wallet/balance');
		$body     = $response->getBody();
		$code     = $response->getStatusCode();
		$resp     = json_decode($body->getContents());

		if($resp->code != 200){
			throw new \Exception($resp->response->message);
		}

		return $resp->response->balance;
	}

	function form($payment_id, $sum, $units='DASH'){
		$sum = number_format($sum, 2, ".", "");

		$response = $this->client->request('POST', 'wallet/create_address', [
			'form_params' => [
		        'order_id' => $payment_id,
		        'user_fields' => [
		        	'hash_pay' => md5($payment_id.Config::get('blockdash.secret_key'))
		        ]
		    ]
		]);
		$body     = $response->getBody();
		$code     = $response->getStatusCode();
		$resp     = json_decode($body->getContents());	
		$PassData = new \stdClass();

		if($resp->code == 200){
			$PassData->address = $resp->response->address;
			$PassData->another_site = false;
		}else{
			$PassData->error = $resp->response->message;
		}

		return $PassData;
	}

	public function check_transaction(array $request, array $server, $headers = []){
		Log::info('Blockdash IPN', [
			'request' => $request,
			'headers' => $headers,
			'server'  => array_intersect_key($server, [
				'PHP_AUTH_USER', 'PHP_AUTH_PW'
			])
		]);

		try{
			$is_complete = $this->validateIPN($request, $server);
			if($is_complete){
				$PassData                     = new \stdClass();
				$PassData->amount             = $request['amount'];
				$PassData->payment_id         = $request['order_id'];
				$PassData->search_by_currency = true;
				$PassData->currency           = 'DASH';
				$PassData->transaction        = $request['txid'];
				$PassData->add_info           = [
					"address"       => $request['address'],
					"full_data_ipn" => json_encode($request)
				];
				event(new BlockDashPaymentIncome($PassData));			
			}

		}catch(BlockDashException $e){
			Log::error('BlockDash IPN', [
				'message' => $e->getMessage()
			]);
		}
	}

	public function validateIPN(array $post_data, array $server_data){
		if(!isset($post_data['order_id'])){
			throw new BlockDashException("For validate IPN need order id");
		}

		if($post_data['amount'] <= 0){
			throw new BlockDashException("Need amount for transaction");	
		}

		if($post_data['confirmations'] < 6){
			throw new BlockDashException("Missing the required number of confirmations");
		}

		$hash = md5($post_data['order_id'].Config::get('blockdash.secret_key'));
		if($hash != $post_data['hashpay']){
			throw new BlockDashException("Hash pay not confirmed");	
		}

		return true;
	}

	public function validateIPNRequest(Request $request) {
        return $this->income_payment($request->all(), $request->server(), $request->headers);
    }

	public function send_money($payment_id, $amount, $address, $currency){
		if($currency != 'DASH'){
			throw new \Exception('Only currency dash');	
		}
		$response = $this->client->request('POST', 'wallet/sending_funds', [
			'form_params' => [
				'amount'  => $amount,
				'address' => $address,
				'note'    => $payment_id
		    ]
		]);
		$body     = $response->getBody();
		$code     = $response->getStatusCode();
		$resp     = json_decode($body->getContents());
		if($resp->code == 200){
			$PassData              = new \stdClass();
			$PassData->transaction = $resp->response->txid;
			$PassData->sending     = true;
			$PassData->add_info    = [
				"fee"       => $resp->response->fee,
				"full_data" => $resp
			];
			return $PassData;
		}else{
			throw new \Exception($resp->response->message);	
		}
	}

	function cancel_payment(Request $request){
		// $PassData     = new \stdClass();
		// $PassData->id = $request->input('PAYMENT_ID');
		
		// event(new BlockDashPaymentCancel($PassData));

		// return redirect()->route('personal.index');
	}
}