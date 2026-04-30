<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    NotificationChannels\WebPush\WebPushServiceProvider::class,
];
