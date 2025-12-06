<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('eligibility_docs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('path')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('company_name')->nullable();
            $table->string('position')->nullable();
            $table->string('status')->default('draft'); // draft, submitted, approved, rejected
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('internships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()->constrained('applications')->nullOnDelete();
            $table->string('company_name')->nullable();
            $table->string('supervisor_name')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('pending'); // pending, active, completed, rejected
            $table->timestamps();
        });

        Schema::create('logbook_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('week_number')->default(1);
            $table->text('entry_text')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->default('draft'); // draft, submitted, pending_review, approved, rejected
            $table->json('ai_analysis_json')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        // Standard Laravel notifications table (not present in this starter)
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('logbook_entries');
        Schema::dropIfExists('internships');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('eligibility_docs');
    }
};
