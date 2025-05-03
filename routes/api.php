<?php

use App\Http\Controllers\AuthentificationController;
use App\Http\Controllers\ClassesController;
use App\Http\Controllers\CoursesController;
use App\Http\Controllers\EdtProvisoireController;
use App\Http\Controllers\EnseignantController;
use App\Http\Controllers\FiliereController;
use App\Http\Controllers\MatieresController;
use App\Http\Controllers\NiveauController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::post('/register', [AuthentificationController::class, 'register']);
Route::post('/login', [AuthentificationController::class, 'login']);


Route::get('/auth/google/redirect', [AuthentificationController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthentificationController::class, 'loginWithGoogle']);

Route::get('/auth/github/redirect', [AuthentificationController::class, 'redirectToGithub']);
Route::get('/auth/github/callback', [AuthentificationController::class, 'loginWithGithub']);

Route::get('/auth/facebook/redirect', [AuthentificationController::class, 'redirectToFacebook']);
Route::get('/auth/facebook/callback', [AuthentificationController::class, 'loginWithFacebook']);

// Uniquement pour le mobile
Route::post('auth/provider', [AuthentificationController::class, 'loginWithProviderForMobile']);
//

Route::apiResource('/filieres', FiliereController::class);
Route::apiResource('/matieres', MatieresController::class);
Route::apiResource('/niveaux', NiveauController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/courses', CoursesController::class);
    Route::apiResource('/salles', ClassesController::class);

    Route::apiResource('enseignants', EnseignantController::class);

    Route::get('week/courses', [CoursesController::class, 'weekCourses']);
    Route::get('pending-validation/courses', [CoursesController::class, 'pendingValidation']);
    Route::put('cancel/courses/{course}', [CoursesController::class, 'cancelCourse']);
    Route::put('accept/courses/{course}', [CoursesController::class, 'acceptCourse']);
    Route::put('complete/courses/{course}', [CoursesController::class, 'courseCompleted']);

    Route::post('/logout', [AuthentificationController::class, 'logout']);

    Route::post('profile/modify', [ProfileController::class, 'modifyProfile']);
    Route::post('password/change', [ProfileController::class, 'modifyPassword']);

    Route::post('emploi-du-temps/provisoire', [EdtProvisoireController::class, 'create']);
    Route::delete('emploi-du-temps/provisoire/{date_creation}/{niveau_id}', [EdtProvisoireController::class, 'destroy']);
    Route::post('emploi-du-temps/provisoire/{id}', [EdtProvisoireController::class, 'ValidateEdt']);
    Route::post('emploi-du-temps/provisoire/{id}', [EdtProvisoireController::class, 'RefuseEdt']);
});


Route::get('emploi-du-temps/provisoire', [EdtProvisoireController::class, 'index']);

Route::get('notifications', [NotificationController::class, 'index'])->middleware(['auth:sanctum']);
