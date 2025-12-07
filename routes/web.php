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

use App\Http\Controllers\AppSettingsController;
use App\Http\Controllers\FilesController;
use Illuminate\Http\Request;
use App\Models\Project;

Route::get('/', 'IndexController@index')->middleware('guest')
    ->name('index');
Route::get('/help/{article?}', 'IndexController@help')
    ->name('help');
Route::get('/profile', 'IndexController@profile')
    ->name('profile')
    ->middleware('auth');
Route::put('/profile', 'IndexController@updateProfile')
    ->name('profile.update')
    ->middleware('auth');
Route::get('/settings', AppSettingsController::class)
    ->name('settings')
    ->middleware('can:global-settings');

Route::get('/projects', 'ProjectsController@index')
    ->name('projects.index');
Route::post('/projects', 'ProjectsController@store')
    ->name('projects.store')
    ->middleware('can:add-project');
Route::get('/projects/{project}/{display?}', 'ProjectsController@show')
    ->where('display', 'all|active')
    ->name('projects.show')
    ->missing(function (Request $request) {
        return Redirect::route('projects.show',
            [Project::where('id', $request->project)->firstOrFail()->slug], 301);
    });
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
Route::get('/projects/{project}/files/{file}/edit', [FilesController::class, 'edit'])
    ->scopeBindings()
    ->name('files.edit')
    ->middleware('can:modify-file,file');
Route::put('/projects/{project}/files/{file}', 'FilesController@update')
    ->scopeBindings()
    ->name('files.update')
    ->middleware('can:modify-file,file');
Route::post('/projects/{project}/files/{file}/upload', 'FilesController@upload')
    ->scopeBindings()
    ->name('files.upload')
    ->middleware('can:modify-file,file');
Route::get('/projects/{project}/files/{file}/{language}/{type?}', 'FilesController@translate')
    ->setBindingFields(['file' => 'slug', 'project' => 'slug'])
    ->where('type', 'all|continue')
    ->name('files.translate')
    ->middleware('can:translate-file,file,language');
Route::get('/projects/{project}/files/{file}/{language}/pretranslate', 'FilesController@pretranslate')
    ->setBindingFields(['file' => 'slug', 'project' => 'slug'])
    ->name('files.pretranslate')
    ->middleware('can:translate-file,file,language');
Route::get('/projects/{project}/files/{file}/{language}/export', 'FilesController@export')
    ->setBindingFields(['file' => 'slug', 'project' => 'slug'])
    ->name('files.export');
Route::get('/projects/{project}/files/{file}/export', 'FilesController@exportAll')
    ->scopeBindings()
    ->name('files.exportAll');
Route::post('/projects/{project}/files/{file}/{language}/import', 'FilesController@import')
    ->setBindingFields(['file' => 'slug', 'project' => 'slug'])
    ->name('files.import')
    ->middleware('can:translate-file,file,language');

Route::get('/texts/{text}/{language}', 'TextsController@show')
    ->name('texts.show')
    ->middleware('can:translate-text,text,language');
Route::get('/texts/{text}/{language}/history', 'TextsController@history')
    ->name('texts.history')
    ->middleware('can:translate-text,text,language');
Route::post('/texts/{text}/{language}', 'TextsController@store')
    ->name('texts.store')
    ->middleware('can:translate-text,text,language');
Route::put('/texts/{language}', 'TextsController@bulkTranslate')
    ->name('texts.bulkTranslate');

Route::get('/languages', 'LanguagesController@index')
    ->name('languages.index')
    ->middleware('can:global-settings');
Route::get('/languages/create', 'LanguagesController@create')
    ->name('languages.create')
    ->middleware('can:global-settings');
Route::post('/languages', 'LanguagesController@store')
    ->name('languages.store')
    ->middleware('can:global-settings');
Route::get('/languages/{language}/edit', 'LanguagesController@edit')
    ->name('languages.edit')
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

Route::get('/glossaries', 'GlossaryEntryController@list')->name('glossaries');
Route::resource('glossaries.entries', 'GlossaryEntryController')->except(['show']);