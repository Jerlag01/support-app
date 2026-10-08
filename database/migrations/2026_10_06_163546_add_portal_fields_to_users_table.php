<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('customer')->after('password')->index();
            $table->string('company')->nullable()->after('name');
            $table->string('phone', 50)->nullable()->after('email');
            $table->string('locale', 5)->default('nl')->after('role');
            $table->boolean('is_active')->default(true)->after('locale');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'company', 'phone', 'locale', 'is_active']);
        });
    }
};
