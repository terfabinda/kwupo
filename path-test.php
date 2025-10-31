<?php
echo "<h2>Corrected Path Test</h2>";
echo "Current script: " . __FILE__ . "<br>";

// CORRECT PATH (uploads inside kwupo)
$upload_dir = __DIR__ . '/uploads/profiles/';
echo "Uploads dir: " . $upload_dir . "<br>";
echo "Uploads exists: " . (is_dir($upload_dir) ? 'YES' : 'NO') . "<br>";
echo "Uploads writable: " . (is_writable($upload_dir) ? 'YES' : 'NO') . "<br>";

// Test write
$test_file = $upload_dir . 'test_write.txt';
file_put_contents($test_file, 'test');
echo "Write test: " . (file_exists($test_file) ? 'SUCCESS' : 'FAILED') . "<br>";
if (file_exists($test_file)) unlink($test_file);
?>