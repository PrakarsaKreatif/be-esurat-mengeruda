<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Generate a valid token for admin@mengeruda.id by directly calling SSO, or just generate it locally since JWT secret is same
$user = \App\Models\User::first();
$token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

$ssoApiUrl = 'http://127.0.0.1:8002/api';
$response = \Illuminate\Support\Facades\Http::withToken($token)
    ->get($ssoApiUrl . '/admin/users/pending');

echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";
