<?php

use App\Models\ContactMessage;
use Illuminate\Support\Facades\Schedule;

Schedule::command('publications:process-lifecycle')->hourly()->withoutOverlapping()->onOneServer();

// Suppression des messages de contact au-delà de la durée de conservation (RGPD).
Schedule::command('model:prune', ['--model' => [ContactMessage::class]])->daily();

Schedule::command('visibility:expire')->hourly()->withoutOverlapping()->onOneServer();

Schedule::command('memberships:send-reminders')->dailyAt('08:00')->withoutOverlapping()->onOneServer();
Schedule::command('payments:reconcile')->everyTenMinutes()->withoutOverlapping()->onOneServer();
