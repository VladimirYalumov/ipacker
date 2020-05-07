<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/picture_form', function () {
    return view('welcome_picture');
})->name('picture_form');

Route::post('login', 'Auth\LoginController@login')->name('login');
Route::post('logout', 'Auth\LoginController@logout')->name('logout');

Route::get('/home', 'HomeController@index')->name('home');

Route::post('/getFinalImageStandard', 'StandardPackController@index')->name('pack_images');
Route::post('/getFinalImagePictures', 'PicturesPackController@index')->name('pack_images_pictures');
