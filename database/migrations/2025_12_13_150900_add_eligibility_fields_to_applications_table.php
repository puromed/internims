<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('eligibility_status')->default('pending')->after('status');
            $table->timestamp('eligibility_reviewed_at')->nullable()->after('eligibility_status');
            $table->foreignId('eligibility_reviewed_by')->nullable()->constrained('users')->after('eligibility_reviewed_at');

            // Document paths
            $table->string('resume_path')->nullable()->after('eligibility_reviewed_by');
            $table->string('transcript_path')->nullable()->after('resume_path');
            $table->string('advisor_letter_path')->nullable()->after('transcript_path');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'eligibility_status',
                'eligibility_reviewed_at',
                'eligibility_reviewed_by',
                'resume_path',
                'transcript_path',
                'advisor_letter_path',
            ]);
        });
    }
};
