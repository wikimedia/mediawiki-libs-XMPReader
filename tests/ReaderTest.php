<?php

namespace Wikimedia\XMPReader\Test;

use Exception;
use PHPUnit\Framework\TestCase;
use Wikimedia\XMPReader\Reader;

/**
 * @group Media
 * @covers \Wikimedia\XMPReader\Reader
 */
class ReaderTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		# Requires libxml to do XMP parsing
		if ( !extension_loaded( 'exif' ) ) {
			$this->markTestSkipped( "PHP extension 'exif' is not loaded, skipping." );
		}
	}

	/**
	 * Put XMP in, compare what comes out...
	 *
	 * @param string $xmp The actual xml data.
	 * @param array $expected Expected result of parsing the xmp.
	 * @param string $info Short sentence on what's being tested.
	 *
	 * @throws Exception
	 * @dataProvider provideXMPParse
	 */
	public function testXMPParse( $xmp, $expected, $info ) {
		if ( !is_string( $xmp ) || !is_array( $expected ) ) {
			throw new Exception( "Invalid data provided to " . __METHOD__ );
		}
		$reader = new Reader;
		$reader->parse( $xmp );
		$this->assertEqualsWithDelta( $expected, $reader->getResults(), 0.0000000001, $info );
	}

	public static function provideXMPParse() {
		$xmpPath = __DIR__ . '/data/';
		$data = [];

		// $xmpFiles format: array of arrays with first arg file base name,
		// with the actual file having .xmp on the end for the xmp
		// and .result.php on the end for a php file containing the result
		// array. The second argument is some info on what's being tested.
		$xmpFiles = [
			[ '1', 'parseType=Resource test' ],
			[ '2', 'Structure with mixed attribute and element props' ],
			[ '3', 'Extra qualifiers (that should be ignored)' ],
			[ '3-invalid', 'Test ignoring qualifiers that look like normal props' ],
			[ '4', 'Flash as qualifier' ],
			[ '5', 'Flash as qualifier 2' ],
			[ '6', 'Multiple rdf:Description' ],
			[ '7', 'Generic test of several property types' ],
			[ '8', 'GPano property types' ],
			[ 'flash', 'Test of Flash property' ],
			[ 'invalid-child-not-struct', 'Test child props not in struct or ignored' ],
			[ 'no-recognized-props', 'Test namespace and no recognized props' ],
			[ 'no-namespace', 'Test non-namespaced attributes are ignored' ],
			[ 'bag-for-seq', "Allow bag's instead of seq's. (T29105)" ],
			[ 'utf16BE', 'UTF-16BE encoding' ],
			[ 'utf16LE', 'UTF-16LE encoding' ],
			[ 'utf32BE', 'UTF-32BE encoding' ],
			[ 'utf32LE', 'UTF-32LE encoding' ],
			[ 'xmpExt', 'Extended XMP missing second part' ],
			[ 'gps', 'Handling of exif GPS parameters in XMP' ],
		];

		$xmpFiles[] = [ 'doctype-included', 'XMP includes doctype' ];

		foreach ( $xmpFiles as $file ) {
			$xmp = file_get_contents( $xmpPath . $file[0] . '.xmp' );
			// I'm not sure if this is the best way to handle getting the
			// result array, but it seems kind of big to put directly in the test
			// file.
			$result = require $xmpPath . $file[0] . '.result.php';
			$data[] = [ $xmp, $result, '[' . $file[0] . '.xmp] ' . $file[1] ];
		}

		return $data;
	}

	/** Test ExtendedXMP block support. (Used when the XMP has to be split
	 * over multiple jpeg segments, due to 64k size limit on jpeg segments.
	 *
	 * @todo This is based on what the standard says. Need to find a real
	 * world example file to double-check the support for this is right.
	 */
	public function testExtendedXMP() {
		$xmpPath = __DIR__ . '/data/';
		$standardXMP = file_get_contents( $xmpPath . 'xmpExt.xmp' );
		$extendedXMP = file_get_contents( $xmpPath . 'xmpExt2.xmp' );

		// md5sum of xmpExt2.xmp
		$md5sum = '28C74E0AC2D796886759006FBE2E57B7';
		$length = pack( 'N', strlen( $extendedXMP ) );
		$offset = pack( 'N', 0 );
		$extendedPacket = $md5sum . $length . $offset . $extendedXMP;

		$reader = new Reader();
		$reader->parse( $standardXMP );
		$reader->parseExtended( $extendedPacket );
		$actual = $reader->getResults();

		$expected = [
			'xmp-exif' => [
				'DigitalZoomRatio' => '0/10',
				'Flash' => 9,
				'FNumber' => '2/10',
			]
		];

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * This test has an extended XMP block with a wrong guid (md5sum)
	 * and thus should only return the StandardXMP, not the ExtendedXMP.
	 */
	public function testExtendedXMPWithWrongGUID() {
		$xmpPath = __DIR__ . '/data/';
		$standardXMP = file_get_contents( $xmpPath . 'xmpExt.xmp' );
		$extendedXMP = file_get_contents( $xmpPath . 'xmpExt2.xmp' );

		// Note that the last digit is wrong (proper hash is 28C74E0AC2D796886759006FBE2E57B7)
		$md5sum = '28C74E0AC2D796886759006FBE2E57B9';
		$length = pack( 'N', strlen( $extendedXMP ) );
		$offset = pack( 'N', 0 );
		$extendedPacket = $md5sum . $length . $offset . $extendedXMP;

		$reader = new Reader();
		$reader->parse( $standardXMP );
		$reader->parseExtended( $extendedPacket );
		$actual = $reader->getResults();

		$expected = [
			'xmp-exif' => [
				'DigitalZoomRatio' => '0/10',
				'Flash' => 9,
			]
		];

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Have a high offset to simulate a missing packet,
	 * which should cause it to ignore the ExtendedXMP packet.
	 */
	public function testExtendedXMPMissingPacket() {
		$xmpPath = __DIR__ . '/data/';
		$standardXMP = file_get_contents( $xmpPath . 'xmpExt.xmp' );
		$extendedXMP = file_get_contents( $xmpPath . 'xmpExt2.xmp' );

		// md5sum of xmpExt2.xmp
		$md5sum = '28C74E0AC2D796886759006FBE2E57B7';
		$length = pack( 'N', strlen( $extendedXMP ) );
		$offset = pack( 'N', 2048 );
		$extendedPacket = $md5sum . $length . $offset . $extendedXMP;

		$reader = new Reader();
		$reader->parse( $standardXMP );
		$reader->parseExtended( $extendedPacket );
		$actual = $reader->getResults();

		$expected = [
			'xmp-exif' => [
				'DigitalZoomRatio' => '0/10',
				'Flash' => 9,
			]
		];

		$this->assertEquals( $expected, $actual );
	}

	public static function provideCheckParseSafety() {
		return [
			'Doctype is detected in fragmented XML' => [
				'doctype-included.xmp',
				false,
				[]
			],
			'False-positive detecting doctype in fragmented XML' => [
				'doctype-not-included.xmp',
				true,
				[
					'xmp-exif' => [
						'DigitalZoomRatio' => '0/10',
						'Flash' => '9'
					]
				]
			],
			'XML containing null bytes (T320282)' => [
				'null-byte.xmp',
				true,
				[
					'xmp-exif' => [
						'Flash' => "9"
					]
				]
			]
		];
	}

	/**
	 * Test for multi-section, hostile XML
	 * @dataProvider provideCheckParseSafety
	 */
	public function testCheckParseSafety( $fileName, $expectValid, $expectResults ) {
		// Test for detection
		$xmpPath = __DIR__ . '/data/';
		$file = fopen( $xmpPath . $fileName, 'rb' );
		$valid = false;
		$reader = new Reader();
		do {
			$chunk = fread( $file, 10 );
			$chunk = str_replace( '~', "\0", $chunk );
			$valid = $reader->parse( $chunk, feof( $file ) );
		} while ( !feof( $file ) );
		$this->assertSame( $expectValid, $valid );
		$this->assertEquals( $expectResults, $reader->getResults() );
		fclose( $file );
	}

	public function testIsSupported() {
		$this->assertTrue( Reader::isSupported() );
	}
}
