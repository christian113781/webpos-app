<?php

return [
    'user_password' => env('SEED_USER_PASSWORD', env('SEED_ADMIN_PASSWORD')),
    'admin_password' => env('SEED_SYSTEM_ADMIN_PASSWORD', env('SEED_SUPER_ADMIN_PASSWORD')),
];
