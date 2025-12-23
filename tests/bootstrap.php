<?php
/**
 * PHPUnit Bootstrap File
 *
 * @package GravityFormsIBANExtractor
 */

// Define WordPress stubs for testing outside of WordPress.
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

// Load Composer autoloader.
require_once dirname(__DIR__) . '/vendor/autoload.php';
