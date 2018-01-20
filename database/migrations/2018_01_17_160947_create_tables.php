<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('provider');
            $table->string('provider_id');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('url');
            $table->timestamps();
        });

        Schema::create('texts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('file_id')->unsigned()->index();
            $table->text('text');
            $table->text('comment');
            $table->text('context');
            $table->timestamps();
        });

        Schema::create('languages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('iso_code', 7);
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('text_id')->unsigned()->index();
            $table->integer('language_id')->unsigned()->index();
            $table->integer('author_id')->unsigned();
            $table->text('translation');
            $table->boolean('needs_work');
            $table->timestamps();
        });

        Schema::create('language_project', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('project_id')->unsigned()->index();
            $table->integer('language_id')->unsigned()->index();
            $table->timestamps();
        });

        Schema::create('project_user', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index();
            $table->integer('project_id')->unsigned()->index();
            $table->smallInteger('role')->unsigned();
            $table->timestamps();
        });

        Schema::create('files', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('project_id')->unsigned()->index();
            $table->string('name');
            $table->string('path');
            $table->string('mime_type');
            $table->char('checksum', 64);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('texts');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('translations');
        Schema::dropIfExists('language_project');
        Schema::dropIfExists('user_project');
        Schema::dropIfExists('files');
    }
}
