<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('footer_links', function (Blueprint $table) {
            $table->id();
            $table->json('label');
            $table->string('url', 2048);
            $table->boolean('open_in_new_tab')->default(false);
            $table->boolean('is_visible')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('job_title')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('photo')->nullable();
            $table->boolean('is_visible')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
        Schema::dropIfExists('footer_links');
    }
};
