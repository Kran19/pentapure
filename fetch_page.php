<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

$user = User::where('role', 'FINISHED')->first();
// Login the user in the session
session(['auth_user' => $user->toArray()]);
Auth::login($user);

// Simulate GET request to /finished/action
$request = Illuminate\Http\Request::create('/finished/action', 'GET');
$response = $app->handle($request);

file_put_to_file:
file_put_contents('page.html', $response->getContent());
echo "Page fetched and saved to page.html\n";
