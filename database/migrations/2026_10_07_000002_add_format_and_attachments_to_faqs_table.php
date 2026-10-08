<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->string('format', 20)->default('rich')->after('question');
            $table->json('attachments')->nullable()->after('answer');
            $table->json('attachment_names')->nullable()->after('attachments');
        });

        // Answers written before this were plain text, which Markdown renders closest to.
        DB::table('faqs')->update(['format' => 'markdown']);
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn(['format', 'attachments', 'attachment_names']);
        });
    }
};
