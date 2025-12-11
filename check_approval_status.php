<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\LogbookEntry;

$logbook = LogbookEntry::find(2);

echo "Logbook ID: {$logbook->id}\n";
echo "Week: {$logbook->week_number}\n";
echo "Status: {$logbook->status}\n";
echo "Supervisor Status: {$logbook->supervisor_status}\n";
echo 'Supervisor Comment: '.($logbook->supervisor_comment ?? 'NULL')."\n";
echo 'Reviewed At: '.($logbook->reviewed_at ?? 'NULL')."\n";
echo 'Reviewed By: '.($logbook->reviewed_by ?? 'NULL')."\n";
