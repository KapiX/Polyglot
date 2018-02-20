<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMoreMetadataToProjects extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('bugtracker_url')->nullable()
                ->after('url');
            $table->string('prerelease_url')->nullable()
                ->after('bugtracker_url');
            $table->char('icon', 40)->nullable()
                ->after('prerelease_url');
            $table->date('release_date')->nullable()
                ->after('icon');
            $table->string('url')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects', function(Blueprint $table) {
            $table->dropColumn('bugtracker_url');
            $table->dropColumn('prerelease_url');
            $table->dropColumn('release_date');
            $table->dropColumn('icon');
            $table->string('url')->nullable(false)->default(null)
                ->change();
        });
    }
}
