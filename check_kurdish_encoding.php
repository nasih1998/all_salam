<?php

// Check Kurdish translations encoding in the database

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking Kurdish translations encoding...\n\n";

// Check table charset and collation
$tableInfo = DB::select("SHOW TABLE STATUS WHERE Name = 'translations'");
if (!empty($tableInfo)) {
    echo "📋 Translations Table Info:\n";
    echo "   Collation: " . $tableInfo[0]->Collation . "\n";
    echo "   Engine: " . $tableInfo[0]->Engine . "\n\n";
}

// Check column details
$columns = DB::select("SHOW FULL COLUMNS FROM translations WHERE Field IN ('key', 'value')");
echo "📊 Column Details:\n";
foreach ($columns as $col) {
    echo "   {$col->Field}:\n";
    echo "      Type: {$col->Type}\n";
    echo "      Collation: {$col->Collation}\n";
}

// Get some sample Kurdish translations
echo "\n📝 Sample Kurdish Translations from Database:\n";
echo "Key\t\t\t|\tValue (first 50 chars)\n";
echo str_repeat("-", 80) . "\n";

$samples = DB::table('translations')
    ->where('locale', 'ckb')
    ->limit(10)
    ->get(['key', 'value']);

foreach ($samples as $sample) {
    $value = mb_substr($sample->value, 0, 50);
    echo "$sample->key\t|\t$value\n";
}

// Check if values are stored as question marks
echo "\n🔍 Checking for question marks in stored values...\n";
$questionMarks = DB::table('translations')
    ->where('locale', 'ckb')
    ->where('value', 'LIKE', '%?%')
    ->count();

echo "   Translations with '?' character: $questionMarks\n";

// Check the HEX representation of a Kurdish value
$firstKurdish = DB::table('translations')
    ->where('locale', 'ckb')
    ->first();

if ($firstKurdish) {
    echo "\n🔤 HEX representation of first Kurdish value:\n";
    echo "   Key: $firstKurdish->key\n";
    echo "   Value: $firstKurdish->value\n";
    echo "   HEX: " . bin2hex($firstKurdish->value) . "\n";
    
    // Check if it's real Kurdish or question marks
    if ($firstKurdish->value === '??????' || strpos($firstKurdish->value, '?') !== false) {
        echo "   ❌ PROBLEM: Value is stored as question marks!\n";
    } else {
        $bytes = unpack('C*', $firstKurdish->value);
        if (!empty($bytes)) {
            echo "   ✅ Value contains non-ASCII bytes (likely Kurdish)\n";
        }
    }
}
