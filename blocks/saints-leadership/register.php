<?php
/**
 * Register Saints Leadership Block
 * 
 * @package Wasmo_Theme
 * @subpackage Blocks
 */

namespace Wasmo_Theme\Blocks\SaintsLeadership;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the Saints Leadership block
 */
function register_block(): void {
    register_block_type( __DIR__ );
}
add_action( 'init', __NAMESPACE__ . '\register_block' );
