<?php

// Copy all English translations to Kurdish Sorani
// Run this file with: php copy_translations_to_kurdish.php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Copying English translations to Kurdish (ckb)...\n";

$englishTranslations = DB::table('translations')
    ->where('locale', 'en')
    ->get(['key', 'value']);

$count = 0;
foreach ($englishTranslations as $translation) {
    DB::table('translations')->updateOrInsert(
        ['locale' => 'ckb', 'key' => $translation->key],
        ['value' => $translation->value]
    );
    $count++;
    
    if ($count % 100 == 0) {
        echo "Processed $count translations...\n";
    }
}

echo "\nDone! Copied $count translations to Kurdish.\n";
echo "You can now translate them from English to Kurdish Sorani.\n";
