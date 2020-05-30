<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MergeMetadataInFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('files', function (Blueprint $table) {
            $table->json('metadata')->nullable()->default(null)
                ->after('path');
        });
        // XXX: MySQL specific
        // mime_type and checksum can be null, so use coalesce to avoid
        // setting entire metadata to null
        DB::table('files')->whereRaw('1=1')
            ->update(['metadata' => DB::raw(
                'json_object('
                . '"mime_type", coalesce(mime_type, ""), '
                . '"checksum", coalesce(checksum, ""))'
            )]);
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn('mime_type');
            $table->dropColumn('checksum');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('files', function (Blueprint $table) {
            $table->string('mime_type')->after('path');
            $table->char('checksum', 64)->after('mime_type');
        });
        // XXX: MySQL specific
        DB::table('files')->whereRaw('1=1')
            ->update([
                'mime_type' => DB::raw('json_unquote(json_extract(metadata, "$.mime_type"))'),
                'checksum' => DB::raw('json_unquote(json_extract(metadata, "$.checksum"))'),
            ]);
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
}
