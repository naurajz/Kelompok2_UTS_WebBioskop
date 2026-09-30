<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/Database.php';

spl_autoload_register(function (string $class_name) {
    $file = __DIR__ . '/classes/' . strtolower($class_name) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
