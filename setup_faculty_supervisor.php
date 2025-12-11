<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Internship;
use App\Models\User;

$faculty = User::where('email', 'faculty@example.com')->first();
$student = User::where('email', 'fan@internims.test')->first();
$internship = Internship::first();

echo "Faculty ID: {$faculty->id}\n";
echo "Student ID: {$student->id}\n";
echo "Internship ID: {$internship->id}\n";
echo 'Current faculty_supervisor_id: '.($internship->faculty_supervisor_id ?? 'NULL')."\n";

// Assign faculty supervisor to the internship
$internship->faculty_supervisor_id = $faculty->id;
$internship->save();

echo "\n✅ Updated faculty_supervisor_id to: {$internship->faculty_supervisor_id}\n";
