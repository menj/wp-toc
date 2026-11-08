<?php
/**
 * Checks that the docs and plugin header agree with SSOT.md.
 *
 * @package WP_TOC
 */

use PHPUnit\Framework\TestCase;

/**
 * Docs consistency.
 */
class Test_WP_TOC_Docs extends TestCase {

	/**
	 * Reads a repository file.
	 *
	 * @param string $path Path relative to the repository root.
	 * @return string
	 */
	private function file( $path ) {
		$full = dirname( __DIR__ ) . '/' . $path;
		$this->assertFileExists( $full );
		return (string) file_get_contents( $full );
	}

	/**
	 * Reads a value from the SSOT identity table.
	 *
	 * @param string $key Row label.
	 * @return string
	 */
	private function ssot( $key ) {
		$this->assertSame( 1, preg_match( '/^\| ' . preg_quote( $key, '/' ) . ' \| (.+?) \|$/m', $this->file( 'SSOT.md' ), $m ), "SSOT.md has no '$key' row" );
		return trim( $m[1], " `" );
	}

	/**
	 * Reads a "Key: value" line.
	 *
	 * @param string $text Text.
	 * @param string $key  Key.
	 * @return string
	 */
	private function header_value( $text, $key ) {
		$this->assertSame( 1, preg_match( '/^[\s*]*' . preg_quote( $key, '/' ) . ':\s*(.+)$/mi', $text, $m ), "Missing '$key'" );
		return trim( $m[1] );
	}

	public function test_required_docs_exist() {
		foreach ( array( 'readme.txt', 'README.md', 'SSOT.md', 'UPGRADING.md', 'CHANGELOG.md', 'LICENSE', 'LICENSE-MIT-ORIGINAL', 'languages/wp-toc.pot' ) as $path ) {
			$this->assertNotSame( '', trim( $this->file( $path ) ), "$path is empty" );
		}
	}

	public function test_version_matches_everywhere() {
		$version = $this->ssot( 'Version' );
		$plugin  = $this->file( 'wp-toc.php' );
		$readme  = $this->file( 'readme.txt' );

		$this->assertSame( $version, $this->header_value( $plugin, 'Version' ), 'wp-toc.php header' );
		$this->assertSame( $version, WP_TOC_VERSION, 'WP_TOC_VERSION' );
		$this->assertSame( $version, $this->header_value( $readme, 'Stable tag' ), 'readme.txt Stable tag' );
		$this->assertStringContainsString( "= $version =", $readme, 'readme.txt changelog/upgrade notice' );
		$this->assertStringContainsString( "**Version:** $version", $this->file( 'README.md' ), 'README.md' );

		preg_match( '/^## \[(\d+\.\d+\.\d+)\]/m', $this->file( 'CHANGELOG.md' ), $m );
		$this->assertSame( $version, $m[1] ?? null, 'Latest CHANGELOG.md release' );

		$this->assertStringContainsString( $version, $this->file( 'UPGRADING.md' ), 'UPGRADING.md' );
		$this->assertStringContainsString( 'Project-Id-Version: WP TOC ' . $version, $this->file( 'languages/wp-toc.pot' ), 'POT file' );
	}

	public function test_requirements_match_everywhere() {
		$wp     = $this->ssot( 'Requires at least (WordPress)' );
		$php    = $this->ssot( 'Requires PHP' );
		$tested = $this->ssot( 'Tested up to (WordPress)' );
		$plugin = $this->file( 'wp-toc.php' );
		$readme = $this->file( 'readme.txt' );

		$this->assertSame( $wp, $this->header_value( $plugin, 'Requires at least' ) );
		$this->assertSame( $wp, $this->header_value( $readme, 'Requires at least' ) );
		$this->assertSame( $php, $this->header_value( $plugin, 'Requires PHP' ) );
		$this->assertSame( $php, $this->header_value( $readme, 'Requires PHP' ) );
		$this->assertSame( $tested, $this->header_value( $readme, 'Tested up to' ) );
		$this->assertStringContainsString( "WordPress $wp+ · PHP $php+", $this->file( 'README.md' ) );

		$composer = json_decode( $this->file( 'composer.json' ), true );
		$this->assertSame( ">=$php", $composer['require']['php'] );
		$this->assertArrayNotHasKey( 'php', $composer['config']['platform'] ?? array() );
	}

	public function test_identity_and_license_match_everywhere() {
		$plugin   = $this->file( 'wp-toc.php' );
		$readme   = $this->file( 'readme.txt' );
		$composer = json_decode( $this->file( 'composer.json' ), true );

		$this->assertSame( $this->ssot( 'Name' ), $this->header_value( $plugin, 'Plugin Name' ) );
		$this->assertStringStartsWith( '=== ' . $this->ssot( 'Name' ) . ' ===', $readme );
		$this->assertSame( $this->ssot( 'Text domain' ), $this->header_value( $plugin, 'Text Domain' ) );
		$this->assertSame( $this->ssot( 'Author' ), $this->header_value( $plugin, 'Author' ) );
		$this->assertSame( $this->ssot( 'Author URI' ), $this->header_value( $plugin, 'Author URI' ) );
		$this->assertSame( $this->ssot( 'Plugin URI' ), $this->header_value( $plugin, 'Plugin URI' ) );

		$license = $this->ssot( 'License' );
		$uri     = $this->ssot( 'License URI' );
		$this->assertSame( $license, $this->header_value( $plugin, 'License' ) );
		$this->assertSame( $uri, $this->header_value( $plugin, 'License URI' ) );
		$this->assertSame( $uri, $this->header_value( $readme, 'License URI' ) );
		$this->assertSame( $license, $composer['license'] );
		$this->assertStringContainsString( 'GNU GENERAL PUBLIC LICENSE', $this->file( 'LICENSE' ) );
		$this->assertStringContainsString( 'Version 3', $this->file( 'LICENSE' ) );
	}
}
