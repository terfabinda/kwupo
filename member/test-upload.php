<?php
if ($_POST) {
    $dir = dirname(__DIR__) . '/uploads/profiles/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    
    if (!empty($_FILES['file']['name'])) {
        $dest = $dir . 'test.jpg';
        if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
            echo "SUCCESS: Saved to $dest";
        } else {
            echo "FAILED";
            print_r(error_get_last());
        }
    } else {
        echo "No file uploaded";
    }
    exit;
}
?>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="file" accept="image/*" required>
    <button>Upload</button>
</form>