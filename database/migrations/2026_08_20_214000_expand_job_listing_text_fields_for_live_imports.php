<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            // Real job-board descriptions can easily exceed MySQL TEXT's
            // ~64 KB byte limit, especially when UTF-8 content is present.
            $table->longText('description')->nullable()->change();

            // External source URLs may be longer than a conventional VARCHAR.
            // Keeping them as TEXT prevents a single legitimate source record
            // from aborting a bulk live-job import.
            $table->text('employer_logo')->nullable()->change();
            $table->text('employer_website')->nullable()->change();
            $table->text('google_link')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
            $table->string('employer_logo')->nullable()->change();
            $table->string('employer_website')->nullable()->change();
            $table->string('google_link')->nullable()->change();
        });
    }
};
