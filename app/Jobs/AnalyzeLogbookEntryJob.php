<?php

namespace App\Jobs;

use App\Models\LogbookEntry;
use App\Services\LogbookAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeLogbookEntryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10; // seconds between retries

    public function __construct(
        public LogbookEntry $entry
    ) {}

    public function handle(LogbookAnalysisService $service): void
    {
        Log::info("Analyzing logbook entry #{$this->entry->id}");

        $analysis = $service->analyze($this->entry->entry_text);

        if ($analysis) {
            $this->entry->update([
                'ai_analysis_json' => $analysis,
            ]);
            Log::info("Logbook entry #{$this->entry->id} analysis complete.");
        } else {
            Log::warning("Logbook entry #{$this->entry->id} analysis failed.");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("AnalyzeLogbookEntryJob failed for entry #{$this->entry->id}: " . $exception->getMessage());
    }
}