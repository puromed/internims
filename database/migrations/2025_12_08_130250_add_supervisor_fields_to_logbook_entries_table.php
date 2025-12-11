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
        Schema::table('logbook_entries', function (Blueprint $table) {
            $table->string('supervisor_status')
                ->default('pending')
                ->after('status');

            $table->text('supervisor_comment')
                ->nullable()
                ->after('supervisor_status');

            $table->timestamp('reviewed_at')
                ->nullable()
                ->after('supervisor_comment');

            $table->foreignId('reviewed_by')
                ->nullable()
                ->after('reviewed_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logbook_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'supervisor_status',
                'supervisor_comment',
                'reviewed_at',
            ]);
        });
    }
};
