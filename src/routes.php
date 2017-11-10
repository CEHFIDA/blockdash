<?php

Route::post('blockdash/cancel', 'Selfreliance\BlockDash\BlockDash@cancel_payment')->name('blockdash.cancel');
Route::post('blockdash/confirm', 'Selfreliance\BlockDash\BlockDash@confirm_payment')->name('blockdash.confirm');