<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// TODO (Phase 2): schedule()->call() here to auto-expire ads whose
// featured_until has passed (proposal §9 "الخدمات المستقبلية").
