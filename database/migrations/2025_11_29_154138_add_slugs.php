<?php

use App\Models\GlossaryEntry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Models\File;
use App\Models\Language;
use App\Models\Project;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->string('slug')->after('name');
        });
        Schema::table('glossary', function (Blueprint $table) {
            $table->string('slug')->after('text');
        });
        Schema::table('languages', function (Blueprint $table) {
            $table->string('slug')->after('name');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->string('slug')->after('name');
        });

        foreach(File::where('slug', '')->cursor() as $file) {
            $file->generateSlug();
            $file->save();
        }
        foreach(GlossaryEntry::where('slug', '')->cursor() as $file) {
            $file->generateSlug();
            $file->save();
        }
        foreach(Language::where('slug', '')->cursor() as $language) {
            $language->generateSlug();
            $language->save();
        }
        foreach(Project::where('slug', '')->cursor() as $project) {
            $project->generateSlug();
            $project->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
        Schema::table('glossary', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
        Schema::table('languages', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
