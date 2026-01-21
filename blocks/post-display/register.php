<?php
/**
 * Register Post Display Block
 * 
 * @package Wasmo_Theme
 * @subpackage Blocks
 */

namespace Wasmo_Theme\Blocks\PostDisplay;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the Post Display block
 */
function register_block(): void {
    register_block_type( __DIR__ );
}
add_action( 'init', __NAMESPACE__ . '\register_block' );
