<?php

// Minimal WordPress stubs for unit testing without a full WordPress installation.

if (! defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

if (! class_exists('WP_Theme')) {
    class WP_Theme {
        public function get(string $header): string {
            return '';
        }
        public function exists(): bool {
            return true;
        }
    }
}

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
