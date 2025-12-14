<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_in_pages = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
$base_path = $is_in_pages ? '../' : '';

// Ensure we don't reload valid translations if already present (though reloading is cheap here)
// The main issue is redeclaring the function.

// Basic language detection
$available = [];
foreach (glob($base_path . 'lang/*.php') as $lf) {
    $code = basename($lf, '.php');
    $available[$code] = true;
}

$req_lang = isset($_GET['lang']) ? $_GET['lang'] : null;

// Update session if requested
if ($req_lang && isset($available[$req_lang])) {
    $_SESSION['lang'] = $req_lang;
}

// Determine current language
if (isset($_SESSION['lang']) && isset($available[$_SESSION['lang']])) {
    $lang = $_SESSION['lang'];
} else {
    $lang = isset($available['en']) ? 'en' : 'ru';
    $_SESSION['lang'] = $lang; // Ensure session is populated
}

// Always reload translations to ensure scope correctness if included multiple times
$translations = [];
$file = $base_path . 'lang/' . $lang . '.php';
if (file_exists($file)) {
    $translations = require $file;
}
$fallback = $base_path . 'lang/en.php';
if (file_exists($fallback)) {
    $translations = array_merge(require $fallback, $translations);
}

if (!function_exists('t')) {
    function t($key) {
        global $translations;
        return isset($translations[$key]) ? $translations[$key] : $key;
    }
}
