<?php

Route::post('pulsebitcoin/cancel', 'Selfreliance\PulseBitcoin\PulseBitcoin@cancel_payment')->name('pulsebitcoin.cancel');
Route::post('pulsebitcoin/confirm', 'Selfreliance\PulseBitcoin\PulseBitcoin@validateIPNRequest')->name('pulsebitcoin.confirm');