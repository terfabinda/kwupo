<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file = $_FILES['test'];
    $upload_dir = __DIR__ . '/uploads/';
    
    // Create uploads dir
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $dest = $upload_dir . 'test.jpg';
    $result = move_uploaded_file($file['tmp_name'], $dest);
    
    echo "<h2>Move Result</h2>";
    echo "Source: " . $file['tmp_name'] . "<br>";
    echo "Destination: " . $dest . "<br>";
    echo "Success: " . ($result ? 'YES' : 'NO') . "<br>";
    
    if (!$result) {
        echo "Error: " . print_r(error_get_last(), true);
    } else {
        echo "File exists: " . (file_exists($dest) ? 'YES' : 'NO');
    }
    exit;
}
?>
<form method="POST" enctype="multipart/form-data">
  <input type="file" name="test" accept="image/*">
  <button type="submit">Upload</button>
</form>