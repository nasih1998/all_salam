<?php

// Import translated Kurdish translations from CSV
// Run this file with: php import_kurdish_translations.php your_translated_file.csv

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get filename from command line argument
$filename = $argv[1] ?? null;

if (!$filename) {
    echo "❌ Error: Please provide the CSV filename\n";
    echo "Usage: php import_kurdish_translations.php your_translated_file.csv\n";
    exit(1);
}

if (!file_exists($filename)) {
    echo "❌ Error: File not found: $filename\n";
    exit(1);
}

echo "Importing Kurdish translations from: $filename\n";

$file = fopen($filename, 'r');

// Skip BOM if present
$bom = fread($file, 3);
if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
    rewind($file);
}

// Auto-detect delimiter (comma or tab)
$firstLine = fgets($file);
rewind($file);

// Skip BOM again after rewind
$bom = fread($file, 3);
if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
    rewind($file);
}

$delimiter = ','; // Default to comma
if (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
    $delimiter = "\t";
}

echo "Detected delimiter: " . ($delimiter === "\t" ? "TAB" : "COMMA") . "\n";

// Skip header row
$header = fgetcsv($file, 0, $delimiter);

$count = 0;
$updated = 0;
$skipped = 0;

while (($row = fgetcsv($file, 0, $delimiter)) !== false) {
    $count++;
    
    if (count($row) < 3) {
        echo "⚠️  Skipping row $count: Invalid format\n";
        $skipped++;
        continue;
    }
    
    $key = trim($row[0]);
    $kurdishValue = trim($row[2]);  // Third column is Kurdish translation
    
    // Skip if Kurdish translation is empty
    if (empty($kurdishValue)) {
        $skipped++;
        continue;
    }
    
    // Update the translation
    DB::table('translations')->updateOrInsert(
        ['locale' => 'ckb', 'key' => $key],
        ['value' => $kurdishValue]
    );
    
    $updated++;
    
    if ($updated % 100 == 0) {
        echo "Imported $updated translations...\n";
    }
}

fclose($file);

echo "\n✅ Import complete!\n";
echo "📊 Statistics:\n";
echo "   - Total rows processed: $count\n";
echo "   - Translations updated: $updated\n";
echo "   - Rows skipped: $skipped\n";
echo "\nDon't forget to clear cache: php artisan cache:clear\n";
