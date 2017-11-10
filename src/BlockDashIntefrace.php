<?php
namespace Selfreliance\BlockDash;
use Illuminate\Http\Request;
interface BlockDashInterface {
   public function balance();
   public function form($payment_id, $amount, $units);
   public function check_transaction($data);
   public function send_money($data);
   public function cancel_payment(Request $request);
}