<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('field_data_sources');
        Schema::dropIfExists('data_sources');
    }

    public function down(): void
    {
        Schema::create('data_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->json('configuration')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('field_data_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')->constrained()->onDelete('cascade');
            $table->foreignId('data_source_id')->constrained()->onDelete('cascade');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['field_id', 'data_source_id']);
        });
    }
};
