<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePastTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('past_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('translation_id')->index();
            $table->unsignedInteger('author_id')->index();
            $table->text('translation');
            $table->timestamps();
        });

        Schema::table('past_translations', function (Blueprint $table) {
            $table->foreign('translation_id')->references('id')->on('translations')
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
        Schema::dropIfExists('past_translations');
    }
}
