<?php

namespace Wikimedia\XMPReader\Test;

use Psr\Log\NullLogger;
use Wikimedia\XMPReader\Validate;

/**
 * @group Media
 *
 * @covers \Wikimedia\XMPReader\Validate
 */
class ValidateTest extends \PHPUnit\Framework\TestCase {

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
}
