<?php

// Check and add Kurdish (Sorani) language to the system

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking languages in the database...\n\n";

// Get all languages
$languages = DB::table('languages')->get();

echo "📋 Current Languages:\n";
echo "ID\tName\t\tLocale\tActive\tDefault\n";
echo "------------------------------------------------\n";
foreach ($languages as $lang) {
    echo "$lang->id\t$lang->name\t\t$lang->locale\t$lang->is_active\t$lang->is_default\n";
}

// Check if Kurdish (Sorani) exists
$kurdish = DB::table('languages')->where('locale', 'ckb')->first();

if ($kurdish) {
    echo "\n✅ Kurdish (Sorani) language already exists!\n";
    echo "   - ID: $kurdish->id\n";
    echo "   - Name: $kurdish->name\n";
    echo "   - Locale: $kurdish->locale\n";
    echo "   - Active: " . ($kurdish->is_active ? 'Yes' : 'No') . "\n";
    
    if (!$kurdish->is_active) {
        echo "\n⚠️  Kurdish is not active. Activating it now...\n";
        DB::table('languages')
            ->where('locale', 'ckb')
            ->update(['is_active' => 1]);
        echo "✅ Kurdish language has been activated!\n";
    }
} else {
    echo "\n⚠️  Kurdish (Sorani) language not found. Adding it now...\n";
    
    DB::table('languages')->insert([
        'name' => 'کوردی (سۆرانی)',  // Kurdish (Sorani) in Kurdish
        'locale' => 'ckb',
        'flag' => 'IQ',  // Iraq flag code
        'is_active' => 1,
        'is_default' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    echo "✅ Kurdish (Sorani) language has been added successfully!\n";
}

echo "\n✅ All done! Kurdish translations are now available in your system.\n";
echo "\nTo use Kurdish in the system:\n";
echo "1. Go to Settings > Languages\n";
echo "2. You should see 'کوردی (سۆرانی)' in the list\n";
echo "3. Users can now select Kurdish as their language preference\n";
