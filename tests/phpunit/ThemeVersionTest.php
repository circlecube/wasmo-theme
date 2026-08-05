<?php

namespace Circlecube\WasmoTheme\Tests;

use PHPUnit\Framework\TestCase;

class ThemeVersionTest extends TestCase {

    private string $root;

    protected function setUp(): void {
        $this->root = dirname(__DIR__, 2);
    }

    public function test_package_json_version_matches_style_css(): void {
        $pkg        = json_decode(file_get_contents($this->root . '/package.json'), true);
        $pkgVersion = $pkg['version'] ?? null;
        $this->assertNotNull($pkgVersion, 'Could not read version from package.json');

        $css = file_get_contents($this->root . '/style.css');
        preg_match('/^\s*Version:\s*([0-9.]+)\s*$/m', $css, $matches);
        $cssVersion = $matches[1] ?? null;
        $this->assertNotNull($cssVersion, 'Could not parse Version from style.css');

        $this->assertSame(
            $pkgVersion,
            $cssVersion,
            "package.json version ($pkgVersion) does not match style.css Version: ($cssVersion)"
        );
    }

    public function test_theme_updater_class_is_autoloaded(): void {
        $this->assertTrue(
            class_exists(\WP_Forge\WPUpdateHandler\ThemeUpdater::class),
            'ThemeUpdater should be available via Composer autoload'
        );
    }
}
