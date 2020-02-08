<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddIndicesToTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // clean up the database - delete duplicates
        $duplicated = DB::table('translations')
            ->select(DB::raw('min(id) as id'), 'text_id', 'language_id')
            ->groupBy('text_id', 'language_id')
            ->havingRaw('count(text_id) > 1 and count(language_id) > 1')->get();
        foreach($duplicated as $saved) {
            DB::table('translations')
                ->where('id', '!=', $saved->id)
                ->where('text_id', $saved->text_id)
                ->where('language_id', $saved->language_id)
                ->delete();
        }
        Schema::table('translations', function (Blueprint $table) {
            $table->index(['text_id', 'language_id', 'needs_work']);
            $table->unique(['text_id', 'language_id']);
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
            $table->dropIndex(['text_id', 'language_id', 'needs_work']);
            $table->dropUnique(['text_id', 'language_id']);
        });
    }
}
