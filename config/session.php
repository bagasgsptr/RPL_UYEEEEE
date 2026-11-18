<?php
// config/session.php

if (session_status() === PHP_SESSION_NONE) {
    $sess_dir = __DIR__ . '/../sessions';
    if (!is_dir($sess_dir)) {
        @mkdir($sess_dir, 0777, true);
    }
    if (is_dir($sess_dir) && is_writable($sess_dir)) {
        @session_save_path($sess_dir);
    }
    @session_start();
}
