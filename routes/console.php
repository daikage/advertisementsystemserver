<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Schedule::command('ads:prune-unpaid --hours=48')->daily();
Schedule::command('ads:check-expired --archive')->hourly();

