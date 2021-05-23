<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGlossaryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('glossary', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('language_id')->index();
            $table->unsignedInteger('author_id');
            $table->text('text');
            $table->text('translation');
            $table->timestamps();
        });

        Schema::table('glossary', function (Blueprint $table) {
            $table->foreign('language_id')->references('id')->on('languages')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('glossary');
    }
}
