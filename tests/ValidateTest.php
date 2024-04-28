<?php

namespace Wikimedia\XMPReader\Test;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Wikimedia\XMPReader\Validate;

/**
 * @group Media
 *
 * @covers \Wikimedia\XMPReader\Validate
 */
class ValidateTest extends TestCase {

	/**
	 * @dataProvider provideBoolean
	 */
	public function testValidateBoolean( $value, $expected ) {
		$validate = new Validate( new NullLogger() );
		$validate->validateBoolean( [], $value, true );
		$this->assertEquals( $expected, $value );
	}

	public static function provideBoolean() {
		return [
			// [ 'True', 'True' ],
			// [ 'False', 'False' ],

			// Invalid
			// [ true, null ],
			[ 'true', null ],
			[ false, null ],
			[ 'false', null ],
			[ 'lol', null ],
		];
	}

	/**
	 * @dataProvider provideRational
	 */
	public function testValidateRational( $value, $expected ) {
		$validate = new Validate( new NullLogger() );
		$validate->validateRational( [], $value, true );
		$this->assertEquals( $expected, $value );
	}

	public static function provideRational() {
		return [
			[ '12/10', '12/10' ],

			// Invalid
			[ 'lol', null ],
		];
	}

	/**
	 * @dataProvider provideRating
	 */
	public function testValidateRating( $value, $expected ) {
		$validate = new Validate( new NullLogger() );
		$validate->validateRating( [], $value, true );
		$this->assertEquals( $expected, $value );
	}

	public static function provideRating() {
		return [
			[ -1, -1 ],
			[ 0, 0 ],
			[ 1, 1 ],
			[ 2, 2 ],
			[ 3, 3 ],
			[ 4, 4 ],
			[ 5, 5 ],
			// Too low, changed to -1
			[ -5, -1 ],
			// Too high, changed to 5
			[ 6, 5 ],

			// Invalid
			[ 'lol', null, ]
		];
	}

	/**
	 * @dataProvider provideBoolean
	 */
	public function testValidateInteger( $value, $expected ) {
		$validate = new Validate( new NullLogger() );
		$validate->validateInteger( [], $value, true );
		$this->assertEquals( $expected, $value );
	}

	public static function provideInteger() {
		return [
			[ '1', '1' ],

			// Invalid
			[ 'lol', null ],
		];
	}

	/**
	 * @dataProvider provideLangCode
	 */
	public function testValidateLangCode( $value, $expected ) {
		$validate = new Validate( new NullLogger() );
		$validate->validateLangCode( [], $value, true );
		$this->assertEquals( $expected, $value );
	}

	public static function provideLangCode() {
		return [
			[ 'en', 'en' ],
			[ 'en-gb', 'en-gb' ],
			// Not a language code, but the validator is very loose
			[ 'lol', 'lol' ],

			// Invalid
			[ 'a', null ],
		];
	}

	/**
	 * @dataProvider provideDates
	 */
	public function testValidateDate( $value, $expected ) {
		// The method should modify $value.
		$validate = new Validate( new NullLogger() );
		$validate->validateDate( [], $value, true );
		$this->assertEquals( $expected, $value );
	}

	public static function provideDates() {
		/* For reference valid date formats are:
		 * YYYY
		 * YYYY-MM
		 * YYYY-MM-DD
		 * YYYY-MM-DDThh:mmTZD
		 * YYYY-MM-DDThh:mm:ssTZD
		 * YYYY-MM-DDThh:mm:ss.sTZD
		 * (Time zone is optional)
		 */
		return [
			[ '1992', '1992' ],
			[ '1992-04', '1992:04' ],
			[ '1992-02-01', '1992:02:01' ],
			[ '2011-09-29', '2011:09:29' ],
			[ '1982-12-15T20:12', '1982:12:15 20:12' ],
			[ '1982-12-15T20:12Z', '1982:12:15 20:12' ],
			[ '1982-12-15T20:12+02:30', '1982:12:15 22:42' ],
			[ '1982-12-15T01:12-02:30', '1982:12:14 22:42' ],
			[ '1982-12-15T20:12:11', '1982:12:15 20:12:11' ],
			[ '1982-12-15T20:12:11Z', '1982:12:15 20:12:11' ],
			[ '1982-12-15T20:12:11+01:10', '1982:12:15 21:22:11' ],
			[ '2045-12-15T20:12:11', '2045:12:15 20:12:11' ],
			[ '1867-06-01T15:00:00', '1867:06:01 15:00:00' ],
			/* some invalid ones */
			[ '0000-01-01', null ],
			[ '2001--12', null ],
			[ '2001-5-12', null ],
			[ '2001-5-12TZ', null ],
			[ '2001-05-12T15', null ],
			[ '2001-12T15:13', null ],
		];
	}

	/**
	 * @dataProvider provideClosedOptions
	 */
	public function testValidateClosed( $info, $value, $expected ) {
		// The method should modify $value.
		$validate = new Validate( new NullLogger() );
		$validate->validateClosed( $info, $value, true );
		$this->assertEquals( $expected, $value );
	}

	public function provideClosedOptions() {
		return [
			[ [], '', null ],
			[ [], '6', null ],
			[ [ 'rangeLow' => -6, 'rangeHigh' => 8 ], '-6', '-6' ],
			[ [ 'rangeLow' => 6, 'rangeHigh' => 8 ], '6', '6' ],
			[ [ 'rangeLow' => 6, 'rangeHigh' => 8 ], '5', null ],
			[ [ 'rangeLow' => 6, 'rangeHigh' => 8 ], '8', '8' ],
			[ [ 'rangeLow' => 6, 'rangeHigh' => 8 ], '9', null ],
			[ [], 'test', null ],
			[ [ 'choices' => [] ], 'test', null ],
			[ [ 'choices' => [ 'test' => true ] ], 'test', 'test' ],
		];
	}

	/**
	 * @dataProvider provideRangesForReal
	 */
	public function testValidateReal( $info, $value, $expected ) {
		// The method should modify $value.
		$validate = new Validate( new NullLogger() );
		$validate->validateReal( $info, $value, true );
		$this->assertEquals( $expected, $value );
	}

	public function provideRangesForReal() {
		return [
			[ [], '', null ],
			[ [], 'null', null ],
			[ [], '6.01', '6.01' ],
			[ [], '6', '6' ],
			[ [ 'rangeLow' => -6, 'rangeHigh' => 8 ], '-5.99', '-5.99' ],
			[ [ 'rangeLow' => -6, 'rangeHigh' => 8 ], '-6.01', null ],
			[ [ 'rangeLow' => 6, 'rangeHigh' => 8 ], '6', '6' ],
			[ [ 'rangeLow' => 6, 'rangeHigh' => 8 ], '5.99', null ],
			[ [ 'rangeLow' => 6, 'rangeHigh' => 8 ], '8', '8' ],
			[ [ 'rangeLow' => 6, 'rangeHigh' => 8 ], '8.01', null ],
		];
	}

	/**
	 * @dataProvider provideFlash
	 */
	public function testValidateFlash( $value, $expected ) {
		$validate = new Validate( new NullLogger() );
		$validate->validateFlash( [], $value, false );
		$this->assertEquals( $expected, $value );
	}

	public static function provideFlash() {
		return [
			[
				[ 'Fired' => 'True', 'Function' => 'True', 'Mode' => 1, 'RedEyeMode' => 'True', 'Return' => 1 ],
				107
			],

			// invalid
			[
				[ 'Fired' => 'True', 'Function' => 'True', 'Mode' => 1, 'RedEyeMode' => 'True' ],
				null
			],
			[ [], null ],
		];
	}

	/**
	 * @dataProvider provideGPS
	 */
	public function testValidateGPS( $value, $expected ) {
		$validate = new Validate( new NullLogger() );
		$validate->validateGPS( [], $value, true );
		$this->assertEquals( $expected, $value );
	}

	public static function provideGPS() {
		return [
			[ '1,1,1N', 1.0169444444444444 ],
			[ '1,1,1S', -1.0169444444444444 ],
			[ '1,1,1E', 1.0169444444444444 ],
			[ '1,1,1W', -1.0169444444444444 ],

			[ '1,1.1N', 1.0183333333333333 ],
			[ '1,1.1S', -1.0183333333333333 ],
			[ '1,1.1E', 1.0183333333333333 ],
			[ '1,1.1W', -1.0183333333333333 ],

			// Invalid
			[ 'lol', null ],
		];
	}
}
