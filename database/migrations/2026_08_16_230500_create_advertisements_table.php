<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('placement', 60)->index();
            $table->string('type', 20)->default('image');
            $table->string('image_path')->nullable();
            $table->string('target_url')->nullable();
            $table->string('alt_text')->nullable();
            $table->longText('custom_code')->nullable();
            $table->boolean('open_in_new_tab')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['placement', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
