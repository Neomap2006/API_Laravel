<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::create([
    'name' => 'Admin Test',
    'email' => 'admin@gmail.com',
    'password' => Hash::make('password'),
]);

echo "User created with ID: " . $user->id;
