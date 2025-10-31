<?php
// includes/helpers.php

function base_url($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    // Detect if in subdirectory (e.g., /kwupo/)
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    $subdir = ($script_dir === '/' || $script_dir === '\\') ? '' : $script_dir;
    
    // On production (kwupo.org.ng), use root; else use subdir
    if (strpos($host, 'kwupo.org.ng') !== false) {
        $base = $protocol . '://' . $host;
    } else {
        $base = $protocol . '://' . $host . $subdir;
    }
    
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}
?>