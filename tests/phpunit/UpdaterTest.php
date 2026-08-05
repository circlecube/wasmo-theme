<?php

namespace Circlecube\WasmoTheme\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use WP_Forge\WPUpdateHandler\ThemeUpdater;

class UpdaterTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        // Stub WordPress hook functions so ThemeUpdater can register its hooks
        // without a WordPress installation.
        Functions\when('add_filter')->justReturn(true);
        Functions\when('add_action')->justReturn(true);
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_theme_updater_constructs_with_mocked_theme(): void {
        $theme   = Mockery::mock('WP_Theme');
        $url     = 'https://api.github.com/repos/circlecube/wasmo-theme/releases/latest';
        $updater = new ThemeUpdater($theme, $url);
        $this->assertInstanceOf(ThemeUpdater::class, $updater);
    }

    public function test_data_map_is_accepted(): void {
        $theme   = Mockery::mock('WP_Theme');
        $url     = 'https://api.github.com/repos/circlecube/wasmo-theme/releases/latest';
        $updater = new ThemeUpdater($theme, $url);

        $updater->setDataMap([
            'download_link' => 'assets.0.browser_download_url',
            'last_updated'  => 'published_at',
            'version'       => 'tag_name',
        ]);

        // No exception thrown — the configured data map is valid.
        $this->assertTrue(true);
    }
}
