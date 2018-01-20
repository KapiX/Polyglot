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

Route::get('/', 'IndexController@index');
Route::resource('projects', 'ProjectsController');
Route::resource('files', 'FilesController');
Route::post('/files/{file}/upload', 'FilesController@upload')->name('files.upload');
Route::get('/files/{file}/lang/{lang}', 'FilesController@translate')->name('files.translate');
Route::get('/texts/{text}/lang/{lang}', 'TextsController@show')->name('texts.show');
Route::post('/texts/{text}/lang/{lang}', 'TextsController@store')->name('texts.store');
Route::get('/settings', 'IndexController@settings')->name('settings')->middleware('auth');
Route::post('/settings/language', 'IndexController@addLanguage')->name('settings.addLanguage')->middleware('auth');
Route::get('/files/{file}/lang/{lang}/export', 'FilesController@export')->name('files.export');
Route::post('/files/{file}/lang/{lang}/import', 'FilesController@import')->name('files.import');
Route::get('/auth/{provider}', 'Auth\LoginController@redirectToProvider')->name('auth.provider');
Route::get('/auth/{provider}/callback', 'Auth\LoginController@handleProviderCallback');
Route::get('/login', 'IndexController@login')->name('login');
Route::get('/logout', 'Auth\LoginController@logout')->name('auth.logout');
