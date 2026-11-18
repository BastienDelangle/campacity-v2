<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('publications:process-lifecycle')->hourly()->withoutOverlapping()->onOneServer();
