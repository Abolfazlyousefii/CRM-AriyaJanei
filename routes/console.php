<?php

Schedule::call(fn() => Log::info('end'))
    ->everyTenSeconds();

Schedule::call(fn() => Artisan::call('reports:send-reminder'))->dailyAt('21:00');
