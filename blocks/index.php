<?php
/**
 * Blocks Index
 * 
 * This file includes all block registration files.
 * Add new blocks by including their register.php file here.
 * 
 * @package Wasmo_Theme
 * @subpackage Blocks
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include block registration files
require_once __DIR__ . '/user-directory/register.php';
require_once __DIR__ . '/taxonomy-cloud/register.php';
require_once __DIR__ . '/post-display/register.php';
require_once __DIR__ . '/saints-leadership/register.php';
