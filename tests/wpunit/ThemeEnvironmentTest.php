<?php

/**
 * Basic sanity checks that the WP test environment is wired up correctly.
 *
 * These act as a version-gate: if the WP or PHP minimum bumps are applied in
 * updates.php / style.css, these tests confirm the installed versions match.
 */
class ThemeEnvironmentTest extends WP_UnitTestCase {

    public function test_wordpress_version_meets_minimum(): void {
        global $wp_version;
        $this->assertTrue(
            version_compare( $wp_version, '6.2', '>=' ),
            "WP {$wp_version} does not meet minimum 6.2"
        );
    }

    public function test_php_version_meets_minimum(): void {
        $this->assertTrue(
            version_compare( PHP_VERSION, '8.0', '>=' ),
            'PHP ' . PHP_VERSION . ' does not meet minimum 8.0'
        );
    }

    public function test_wp_can_create_and_query_a_post(): void {
        $post_id = self::factory()->post->create( [ 'post_title' => 'Wasmo Test Post' ] );
        $post    = get_post( $post_id );

        $this->assertInstanceOf( WP_Post::class, $post );
        $this->assertSame( 'Wasmo Test Post', $post->post_title );
    }
}
