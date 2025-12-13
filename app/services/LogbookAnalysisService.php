<?php

namespace App\Services;

use Gemini\Data\GenerationConfig;
use Gemini\Enums\ResponseMimeType;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;

class LogbookAnalysisService
{
    protected string $model;

    // Varied stub responses to make demos feel alive
    protected array $stubResponses = [
        [
            'summary' => 'Strong technical progress this week with effective problem-solving and collaborative debugging efforts.',
            'skills_identified' => ['Problem Solving', 'Debugging', 'Team Collaboration', 'Code Review'],
            'sentiment' => 'positive',
        ],
        [
            'summary' => 'Demonstrated initiative in learning new technologies while contributing to ongoing project deliverables.',
            'skills_identified' => ['Self-Learning', 'Adaptability', 'Technical Writing', 'Time Management'],
            'sentiment' => 'positive',
        ],
        [
            'summary' => 'Solid week focused on implementation tasks with attention to code quality and documentation.',
            'skills_identified' => ['Implementation', 'Documentation', 'Attention to Detail', 'Laravel'],
            'sentiment' => 'positive',
        ],
        [
            'summary' => 'Made progress on assigned tasks while navigating some technical challenges that required research.',
            'skills_identified' => ['Research', 'Perseverance', 'Critical Thinking', 'Communication'],
            'sentiment' => 'neutral',
        ],
        [
            'summary' => 'Productive week with meaningful contributions to the codebase and active participation in team discussions.',
            'skills_identified' => ['Teamwork', 'Active Listening', 'PHP', 'Git'],
            'sentiment' => 'positive',
        ],
        [
            'summary' => 'Focused on testing and quality assurance while supporting the team with code reviews and feedback.',
            'skills_identified' => ['Testing', 'Quality Assurance', 'Peer Review', 'Mentoring'],
            'sentiment' => 'positive',
        ],
    ];

    public function __construct()
    {
        $this->model = config('gemini.model', 'gemini-2.0-flash');
    }

    public function analyze(string $text): ?array
    {
        // Try real AI first
        $analysis = $this->tryGeminiAnalysis($text);

        // Fallback to stub if AI fails
        if (!$analysis) {
            Log::info('Using stub analysis (AI unavailable)');
            $analysis = $this->getStubAnalysis($text);
        }

        return $analysis;
    }

    protected function tryGeminiAnalysis(string $text): ?array
    {
        if (empty(config('gemini.api_key'))) {
            Log::warning('Gemini API key not configured, using stub.');
            return null;
        }

        try {
            $result = Gemini::generativeModel(model: $this->model)
                ->withGenerationConfig(
                    new GenerationConfig(
                        responseMimeType: ResponseMimeType::APPLICATION_JSON
                    )
                )
                ->generateContent($this->buildPrompt($text));

            $rawJson = $result->text();
            $analysis = json_decode($rawJson, true);

            if (!$analysis) {
                Log::error('Gemini returned invalid JSON: ' . $rawJson);
                return null;
            }

            $analysis['analyzed_at'] = now()->toISOString();
            $analysis['source'] = 'gemini'; // Mark as real AI

            return $analysis;

        } catch (\Exception $e) {
            Log::warning('Gemini API failed, falling back to stub: ' . $e->getMessage());
            return null;
        }
    }

    protected function getStubAnalysis(string $text): array
    {
        // Pick a response based on text hash for consistency (same text = same response)
        $index = crc32($text) % count($this->stubResponses);
        $stub = $this->stubResponses[$index];

        return [
            'summary' => $stub['summary'],
            'skills_identified' => $stub['skills_identified'],
            'sentiment' => $stub['sentiment'],
            'analyzed_at' => now()->toISOString(),
            'source' => 'stub', // Mark as stub for transparency
        ];
    }

    protected function buildPrompt(string $text): string
    {
        return <<<EOT
You are a supervisor analyzing an intern's weekly logbook entry.
Analyze the following text and return a JSON object with these exact keys:
- "summary": A 1-2 sentence professional summary of their progress.
- "skills_identified": An array of technical or soft skills mentioned (max 5).
- "sentiment": One of "positive", "neutral", or "negative".

Logbook Entry:
"{$text}"
EOT;
    }
}