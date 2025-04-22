<?php

use App\Http\Controllers\CoursesController;
use App\Models\User;
use App\Notifications\ExpoNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

Route::get('/', function () {
    return view('welcome');
});

Route::get('test', function () {
    $users = User::query()->where('id', '!=', 2)->get();
    Notification::send($users, new ExpoNotification("Annulation de cous", "Un cours a ete annulé"));
    return response()->json(['message' => 'Message envoyé avec succès', 'user' => $users]);
});
