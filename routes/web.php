<?php

use App\Models\User;
use App\Notifications\ExpoNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {


    return view('welcome');
});

Route::get('test', function () {
    $users = User::where('id', 15)->first();

    $response = Notification::send($users, new ExpoNotification("Annulation de cours", "Un cours a été annulé"));

    return response()->json([
        'message' => 'Message envoyé avec succès',
        'notification_response' => $response,
        'users' => $users
    ]);
});
