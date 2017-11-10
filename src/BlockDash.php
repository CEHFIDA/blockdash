<?php

namespace Selfreliance\BlockDash;

use Illuminate\Http\Request;
use Config;
use Route;

use Illuminate\Foundation\Validation\ValidatesRequests;

use Selfreliance\BlockDash\Events\BlockDashPaymentIncome;
use Selfreliance\BlockDash\Events\BlockDashPaymentCancel;

use Selfreliance\BlockDash\BlockDashInterface;
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

	function balance(){
		$response = $this->client->request('GET', 'wallet/balance');
		$body     = $response->getBody();
		$code     = $response->getStatusCode();
		$resp     = json_decode($body->getContents());

		$PassData = new \stdClass();
		if($resp->code == 200){
			$PassData->status = true;
			$PassData->balance = $resp->response->balance;
		}else{
			$PassData->status = false;
			$PassData->message = $resp->response->message;
		}
		return $PassData;
	}

	function form($payment_id, $sum, $units='USD'){
		$sum = number_format($sum, 2, ".", "");

		$response = $this->client->request('POST', 'wallet/create_address', [
			'form_params' => [
		        'order_id' => $payment_id,
		        'user_fields' => [
		        	'hash_pay' => str_random(50)
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

	public function check_transaction($request){
		$PassData                 = new \stdClass();
		$PassData->amount         = $PAYMENT_AMOUNT;
		$PassData->payment_id     = $PAYMENT_ID;
		$PassData->payment_system = 4;
		$PassData->transaction    = $PAYMENT_BATCH_NUM;

		event(new BlockDashPaymentIncome($PassData));
	}

	public function send_money($data){

	}

	function cancel_payment(Request $request){
		$PassData     = new \stdClass();
		$PassData->id = $request->input('PAYMENT_ID');
		
		event(new BlockDashPaymentCancel($PassData));

		return redirect()->route('personal.index');
	}
}