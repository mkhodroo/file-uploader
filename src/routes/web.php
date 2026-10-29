<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use BehinFileControl\Controllers\FileController;
use BehinFileControl\Controllers\FileUploadSettingController;
use Mkhodroo\AgencyInfo\Controllers\GetAgencyController;
use UserProfile\Controllers\ChangePasswordController;
use UserProfile\Controllers\GetUserAgenciesController;
use UserProfile\Controllers\NationalIdController;
use UserProfile\Controllers\UserProfileController;

Route::name('user-profile.')->prefix('user-profile')->middleware(['web','auth'])->group(function(){
    Route::post('getUserAgencies', [GetUserAgenciesController::class, 'get'])->name('getUserAgencies');
    Route::get('agency/edit-location/{parent_id}', [GetUserAgenciesController::class, 'getLocation'])->name('getLocation');


    Route::get('/', [UserProfileController::class, 'index'])->name('profile');

    Route::get('/change-password', [ChangePasswordController::class, 'edit'])->name('change-password');
    Route::post('', [NationalIdController::class, 'store'])->name('storeNationalId');
    Route::put('/', [ChangePasswordController::class, 'update'])->name('update-password');

});

Route::get('file/{guid}', [FileController::class, 'download'])->name('file.download');

Route::prefix('file-upload-settings')->name('file-upload-settings.')->group(function () {
    Route::get('settings', [FileUploadSettingController::class, 'index'])->name('index');
    Route::post('settings', [FileUploadSettingController::class, 'store'])->name('store');
    Route::post('test-connection', [FileUploadSettingController::class, 'testConnection'])->name('test');
});
