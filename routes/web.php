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
Route::get('/help/{article?}', 'IndexController@help')
    ->name('help');
Route::get('/profile', 'IndexController@profile')
    ->name('profile')
    ->middleware('auth');
Route::put('/profile', 'IndexController@updateProfile')
    ->name('profile.update')
    ->middleware('auth');

Route::get('/projects', 'ProjectsController@index')
    ->name('projects.index');
Route::post('/projects', 'ProjectsController@store')
    ->name('projects.store')
    ->middleware('can:add-project');
Route::get('/projects/{project}/{display?}', 'ProjectsController@show')
    ->where('display', 'all|active')
    ->name('projects.show');
Route::get('/projects/{project}/export/{status?}', 'ProjectsController@export')
    ->where('status', 'all|complete')
    ->name('projects.export');
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
Route::put('/files/{file}', 'FilesController@update')
    ->name('files.update')
    ->middleware('can:modify-file,file');
Route::post('/files/{file}/upload', 'FilesController@upload')
    ->name('files.upload')
    ->middleware('can:modify-file,file');
Route::get('/files/{file}/lang/{lang}/{type?}', 'FilesController@translate')
    ->where('type', 'all|continue')
    ->name('files.translate');
Route::get('/files/{file}/lang/{lang}/export', 'FilesController@export')
    ->name('files.export');
Route::get('/files/{file}/export', 'FilesController@exportAll')
    ->name('files.exportAll');
Route::post('/files/{file}/lang/{lang}/import', 'FilesController@import')
    ->name('files.import')
    ->middleware('can:translate-file,file,lang');

Route::get('/texts/{text}/lang/{lang}', 'TextsController@show')
    ->name('texts.show')
    ->middleware('can:translate-text,text,lang');
Route::post('/texts/{text}/lang/{lang}', 'TextsController@store')
    ->name('texts.store')
    ->middleware('can:translate-text,text,lang');

Route::get('/languages', 'LanguagesController@index')
    ->name('languages.index')
    ->middleware('can:global-settings');
Route::post('/languages', 'LanguagesController@store')
    ->name('languages.store')
    ->middleware('can:global-settings');
Route::put('/languages/{language}', 'LanguagesController@update')
    ->name('languages.update')
    ->middleware('can:global-settings');

Route::get('/users', 'UsersController@index')
    ->name('users.index')
    ->middleware('can:global-settings');
Route::get('/users/{user}/edit', 'UsersController@edit')
    ->name('users.edit')
    ->middleware('can:global-settings');
Route::put('/users/{user}', 'UsersController@update')
    ->name('users.update')
    ->middleware('can:global-settings');

Route::get('/auth/{provider}', 'Auth\LoginController@redirectToProvider')
    ->name('auth.provider');
Route::get('/auth/{provider}/callback', 'Auth\LoginController@handleProviderCallback');

Route::get('/login', 'IndexController@login')
    ->name('login');
Route::get('/logout', 'Auth\LoginController@logout')
    ->name('auth.logout');
