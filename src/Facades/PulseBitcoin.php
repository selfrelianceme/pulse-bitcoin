<?php 
namespace Selfreliance\PulseBitcoin\Facades;  

use Illuminate\Support\Facades\Facade;  

class PulseBitcoin extends Facade 
{
	protected static function getFacadeAccessor() { 
		return 'pulsebitcoin';
	}
}
