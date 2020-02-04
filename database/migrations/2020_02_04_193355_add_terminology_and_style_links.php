<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTerminologyAndStyleLinks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->string('style_guide_url')->nullable()
                ->after('name');
            $table->string('terminology_url')->nullable()
                ->after('style_guide_url');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('languages', function(Blueprint $table) {
            $table->dropColumn('style_guide_url');
            $table->dropColumn('terminology_url');
        });
    }
}
