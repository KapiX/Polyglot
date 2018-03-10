<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCascadeDeleteTranslationsConstraint extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // clean up the database
        DB::table('translations')
            ->whereNotIn('text_id', DB::table('texts')->select('id'))
            ->delete();

        Schema::table('translations', function (Blueprint $table) {
            $table->foreign('text_id')->references('id')->on('texts')
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
        Schema::table('translations', function (Blueprint $table) {
            $table->dropForeign('translations_text_id_foreign');
        });
    }
}
