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

Route::get('/', 'IndexController@index')->middleware('guest');

Route::get('/projects', 'ProjectsController@index')
    ->name('projects.index');
Route::post('/projects', 'ProjectsController@store')
    ->name('projects.store')
    ->middleware('can:add-project');
Route::get('/projects/{project}', 'ProjectsController@show')
    ->name('projects.show');
Route::get('/projects/{project}/edit', 'ProjectsController@edit')
    ->name('projects.edit')
    ->middleware('can:modify-project,project');
Route::put('/projects/{project}', 'ProjectsController@update')
    ->name('projects.update')
    ->middleware('can:modify-project,project');
Route::post('/projects/{project}/file', 'FilesController@store')
    ->name('files.store')
    ->middleware('can:modify-project,project');

Route::get('/files/{file}/edit', 'FilesController@edit')
    ->name('files.edit')
    ->middleware('can:modify-file,file');
Route::post('/files/{file}/upload', 'FilesController@upload')
    ->name('files.upload')
    ->middleware('can:modify-file,file');
Route::get('/files/{file}/lang/{lang}', 'FilesController@translate')
    ->name('files.translate');
Route::get('/files/{file}/lang/{lang}/export', 'FilesController@export')
    ->name('files.export');
Route::post('/files/{file}/lang/{lang}/import', 'FilesController@import')
    ->name('files.import');

Route::get('/texts/{text}/lang/{lang}', 'TextsController@show')
    ->name('texts.show');
Route::post('/texts/{text}/lang/{lang}', 'TextsController@store')
    ->name('texts.store');

Route::get('/settings', 'IndexController@settings')
    ->name('settings')
    ->middleware('can:global-settings');
Route::post('/settings/language', 'IndexController@addLanguage')
    ->name('settings.addLanguage')
    ->middleware('can:global-settings');
Route::post('/settings/role/{user}', 'IndexController@changeRole')
    ->name('settings.changeRole')
    ->middleware('can:global-settings');

Route::get('/auth/{provider}', 'Auth\LoginController@redirectToProvider')
    ->name('auth.provider');
Route::get('/auth/{provider}/callback', 'Auth\LoginController@handleProviderCallback');

Route::get('/login', 'IndexController@login')
    ->name('login');
Route::get('/logout', 'Auth\LoginController@logout')
    ->name('auth.logout');
