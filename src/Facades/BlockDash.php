<?php 
namespace Selfreliance\BlockDash\Facades;  

use Illuminate\Support\Facades\Facade;  

class BlockDash extends Facade 
{
	protected static function getFacadeAccessor() { 
		return 'blockdash';
	}
}
