<?php

use Illuminate\Support\Facades\Schedule;

// Schedule sync setiap 30 menit
Schedule::command('master-pks:sync')->everyThirtyMinutes()->withoutOverlapping();

// Schedule retry failed records setiap jam
Schedule::command('master-pks:sync --retry')->hourly()->withoutOverlapping();
