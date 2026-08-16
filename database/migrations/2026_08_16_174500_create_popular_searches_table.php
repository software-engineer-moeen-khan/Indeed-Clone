<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('popular_searches', function (Blueprint $table) {
            $table->id();
            $table->string('label', 80);
            $table->string('search_query', 120);
            $table->unsignedTinyInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        $now = now();

        DB::table('popular_searches')->insert([
            [
                'label' => 'Laravel',
                'search_query' => 'Laravel',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'label' => 'Software Engineer',
                'search_query' => 'Software Engineer',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'label' => 'Remote',
                'search_query' => 'Remote',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'label' => 'Internship',
                'search_query' => 'Internship',
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('popular_searches');
    }
};
