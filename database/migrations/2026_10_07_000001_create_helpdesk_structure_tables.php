<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('grace_hours');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('sla_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_public')->default(true);
            $table->text('signature')->nullable();
            $table->timestamps();
        });

        Schema::create('department_user', function (Blueprint $table) {
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['department_id', 'user_id']);
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('lead_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('team_user', function (Blueprint $table) {
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['team_id', 'user_id']);
        });

        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->nullable()->index();
            $table->string('phone', 50)->nullable();
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('company')->constrained()->nullOnDelete();
            $table->text('signature')->nullable()->after('is_active');
        });

        // Staff are called agents from now on, as in osTicket.
        DB::table('users')->where('role', 'staff')->update(['role' => 'agent']);

        Schema::create('help_topics', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('icon', 30)->default('chat');
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('sla_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('default_priority', 20)->default('normal');
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('help_topic_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            $table->foreignId('sla_plan_id')->nullable()->after('help_topic_id')->constrained()->nullOnDelete();
            $table->foreignId('team_id')->nullable()->after('assigned_to')->constrained()->nullOnDelete();
            $table->string('source', 20)->default('web')->after('site_address');
            $table->boolean('is_answered')->default(false)->after('status');
            $table->timestamp('due_at')->nullable()->after('is_answered');

            $table->index(['department_id', 'status']);
            $table->index(['status', 'due_at']);
        });

        Schema::create('ticket_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->json('data')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['ticket_id', 'created_at']);
        });

        Schema::create('canned_responses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('faq_categories', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('description')->nullable();
            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faq_category_id')->constrained()->cascadeOnDelete();
            $table->json('question');
            $table->json('answer');
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('faq_categories');
        Schema::dropIfExists('canned_responses');
        Schema::dropIfExists('ticket_events');

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('help_topic_id');
            $table->dropConstrainedForeignId('sla_plan_id');
            $table->dropConstrainedForeignId('team_id');
            $table->dropIndex(['status', 'due_at']);
            $table->dropColumn(['source', 'is_answered', 'due_at']);
            $table->string('category', 30)->default('other');
        });

        Schema::dropIfExists('help_topics');

        DB::table('users')->where('role', 'agent')->update(['role' => 'staff']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn('signature');
        });

        Schema::dropIfExists('organizations');
        Schema::dropIfExists('team_user');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('department_user');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('sla_plans');
    }
};
