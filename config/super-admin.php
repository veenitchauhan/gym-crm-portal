<?php

return [
    'username' => env('SUPER_ADMIN_USERNAME'),
    'password' => env('SUPER_ADMIN_PASSWORD'),
    'name' => env('SUPER_ADMIN_NAME', 'Platform Administrator'),
    'client_temporary_password' => env('CLIENT_TEMPORARY_PASSWORD', 'P@ssw0rd'),
];
