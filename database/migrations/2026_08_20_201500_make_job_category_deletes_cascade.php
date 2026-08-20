<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropForeign(['job_category']);
            $table->foreign('job_category')
                ->references('id')
                ->on('job_categories')
                ->cascadeOnDelete();
        });

        Schema::table('job_category_country', function (Blueprint $table) {
            $table->dropForeign(['job_category_id']);
            $table->foreign('job_category_id')
                ->references('id')
                ->on('job_categories')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropForeign(['job_category']);
            $table->foreign('job_category')
                ->references('id')
                ->on('job_categories');
        });

        Schema::table('job_category_country', function (Blueprint $table) {
            $table->dropForeign(['job_category_id']);
            $table->foreign('job_category_id')
                ->references('id')
                ->on('job_categories');
        });
    }
};
