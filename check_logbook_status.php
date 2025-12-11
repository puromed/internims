<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\LogbookEntry;

$logbooks = LogbookEntry::all();

foreach ($logbooks as $log) {
    echo "Logbook ID: {$log->id}\n";
    echo "Week: {$log->week_number}\n";
    echo "Status: {$log->status}\n";
    echo "Supervisor Status: {$log->supervisor_status}\n";
    echo "---\n";
}

// Update status to pending_review
echo "\nUpdating logbooks to pending_review status...\n";
LogbookEntry::query()->update(['status' => 'pending_review']);

echo "\n✅ Updated all logbooks to pending_review status\n";
