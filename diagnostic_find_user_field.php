<?php
// Diagnostic script to find where 'user' field is being used instead of 'user_id'
$files = [
    'app/Http/Controllers/HsseCommentController.php',
    'app/Models/HsseComment.php',
    'app/Filament/Resources/DocumentResource.php',
    'app/Observers/DocumentObserver.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, "'user' =>") !== false || strpos($content, '"user" =>') !== false) {
            echo "Found potential issue in: $file\n";
            echo "Line containing 'user' =>\n";
            
            $lines = explode("\n", $content);
            foreach ($lines as $lineNum => $line) {
                if (strpos($line, "'user' =>") !== false || strpos($line, '"user" =>') !== false) {
                    echo "Line " . ($lineNum + 1) . ": $line\n";
                }
            }
            echo "\n";
        }
    }
}

// Also check for any mass assignment issues
echo "Checking fillable fields in HsseComment model:\n";
$modelContent = file_get_contents('app/Models/HsseComment.php');
if (strpos($modelContent, 'user_id') !== false) {
    echo "✓ HsseComment model correctly uses 'user_id'\n";
} else {
    echo "✗ HsseComment model might be missing 'user_id' in fillable\n";
}
