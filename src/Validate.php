<?php
/**
 * Methods for validating XMP properties.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Media
 */

namespace Wikimedia\XMPReader;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * This contains some static methods for
 * validating XMP properties. See XMPInfo and Reader classes.
 *
 * Each of these functions take the same parameters
 * * an info array which is a subset of the XMPInfo::items array
 * * A value (passed as reference) to validate. This can be either a
 *    simple value or an array
 * * A boolean to determine if this is validating a simple or complex values
 *
 * It should be noted that when an array is being validated, typically the validation
 * function is called once for each value, and then once at the end for the entire array.
 *
 * These validation functions can also be used to modify the data. See the gps and flash one's
 * for example.
 *
 * @see https://www.adobe.com/devnet/xmp.html
 * @see https://wwwimages2.adobe.com/content/dam/acom/en/devnet/xmp/pdfs/
 *      XMP%20SDK%20Release%20cc-2016-08/XMPSpecificationPart1.pdf starting at pg 28
 * @see https://wwwimages2.adobe.com/content/dam/acom/en/devnet/xmp/pdfs/
 *      XMP%20SDK%20Release%20cc-2016-08/XMPSpecificationPart2.pdf starting at pg 11
 */
class Validate implements LoggerAwareInterface {
	use LoggerAwareTrait;

	/**
	 * Creates a new instance, with a logger
	 *
	 * @param LoggerInterface $logger
	 */
	public function __construct( LoggerInterface $logger ) {
		$this->setLogger( $logger );
	}

	/**
	 * Function to validate boolean properties ( True or False )
	 *
	 * @param array $info Information about current property
	 * @param mixed &$val Current value to validate
	 * @param bool $standalone If this is a simple property or array
	 */
	public function validateBoolean( $info, &$val, $standalone ): void {
		if ( !$standalone ) {
			// this only validates standalone properties, not arrays, etc
			// @codeCoverageIgnoreStart
			return;
			// @codeCoverageIgnoreEnd
		}
		if ( $val !== 'True' && $val !== 'False' ) {
			$this->logger->info( __METHOD__ . " Expected True or False but got $val" );
			$val = null;
		}
	}

	/**
	 * function to validate rational properties ( 12/10 )
	 *
	 * @param array $info Information about current property
	 * @param mixed &$val Current value to validate
	 * @param bool $standalone If this is a simple property or array
	 */
	public function validateRational( $info, &$val, $standalone ): void {
		if ( !$standalone ) {
			// this only validates standalone properties, not arrays, etc
			// @codeCoverageIgnoreStart
			return;
			// @codeCoverageIgnoreEnd
		}
		if ( !preg_match( '/^(?:-?\d+)\/(?:\d+[1-9]|[1-9]\d*)$/D', $val ) ) {
			$this->logger->info( __METHOD__ . " Expected rational but got $val" );
			$val = null;
		}
	}

	/**
	 * function to validate rating properties -1, 0-5
	 *
	 * if its outside of range, put it into range.
	 *
	 * @see MWG spec
	 * @param array $info Information about current property
	 * @param mixed &$val Current value to validate
	 * @param bool $standalone If this is a simple property or array
	 */
	public function validateRating( $info, &$val, $standalone ): void {
		if ( !$standalone ) {
			// this only validates standalone properties, not arrays, etc
			// @codeCoverageIgnoreStart
			return;
			// @codeCoverageIgnoreEnd
		}
		if ( !preg_match( '/^[-+]?\d*(?:\.?\d*)$/D', $val )
			|| !is_numeric( $val )
		) {
			$this->logger->info( __METHOD__ . " Expected rating but got $val" );
			$val = null;

			return;
		}

		$nVal = (float)$val;
		if ( $nVal < 0 ) {
			// We do < 0 here instead of < -1 here, since
			// the values between 0 and -1 are also illegal
			// as -1 is meant as a special reject rating.
			$this->logger->info( __METHOD__ . " Rating too low, setting to -1 (Rejected)" );
			$val = '-1';

			return;
		}
		if ( $nVal > 5 ) {
			$this->logger->info( __METHOD__ . " Rating too high, setting to 5" );
			$val = '5';
		}
	}

	/**
	 * function to validate integers
	 *
	 * @param array $info Information about current property
	 * @param mixed &$val Current value to validate
	 * @param bool $standalone If this is a simple property or array
	 */
	public function validateInteger( $info, &$val, $standalone ): void {
		if ( !$standalone ) {
			// this only validates standalone properties, not arrays, etc
			// @codeCoverageIgnoreStart
			return;
			// @codeCoverageIgnoreEnd
		}
		if ( !preg_match( '/^[-+]?\d+$/D', $val ) ) {
			$this->logger->info( __METHOD__ . " Expected integer but got $val" );
			$val = null;
		}
	}

	/**
	 * function to validate properties with a fixed number of allowed
	 * choices. (closed choice)
	 *
	 * @param array $info Information about current property
	 * @param mixed &$val Current value to validate
	 * @param bool $standalone If this is a simple property or array
	 */
	public function validateClosed( $info, &$val, $standalone ): void {
		if ( !$standalone ) {
			// this only validates standalone properties, not arrays, etc
			// @codeCoverageIgnoreStart
			return;
			// @codeCoverageIgnoreEnd
		}

		// check if it's in a numeric range
		$inRange = false;
		if ( is_numeric( $val )
			&& isset( $info['rangeLow'] ) && isset( $info['rangeHigh'] )
			&& ( (int)$val <= $info['rangeHigh'] ) && ( (int)$val >= $info['rangeLow'] )
		) {
			$inRange = true;
		}

		if ( !isset( $info['choices'][$val] ) && !$inRange ) {
			$this->logger->info( __METHOD__ . " Expected closed choice, but got $val" );
			$val = null;
		}
	}

	/**
	 * function to validate and modify real numbers, with ranges
	 *
	 * @param array $info Information about current property
	 * @param mixed &$val Current value to validate
	 * @param bool $standalone If this is a simple property or array
	 */
	public function validateReal( $info, &$val, $standalone ): void {
		if ( !$standalone ) {
			// this only validates standalone properties, not arrays, etc
			// @codeCoverageIgnoreStart
			return;
			// @codeCoverageIgnoreEnd
		}

		$isReal = is_numeric( $val ) && (float)$val;
		if ( !$isReal ) {
			$this->logger->info( __METHOD__ . " Expected real, but got $val" );
			$val = null;
			return;
		}

		// check if it's in a numeric range
		if ( isset( $info['rangeLow'] ) && isset( $info['rangeHigh'] )
			&& ( (float)$val > $info['rangeHigh'] || (float)$val < $info['rangeLow'] )
		) {
			$this->logger->info(
				__METHOD__
				. " Expected value within range of {$info['rangeLow']}-{$info['rangeHigh']}, but got $val"
			);
			$val = null;
		}
	}

	/**
	 * function to validate and modify flash structure
	 *
	 * @param array $info Information about current property
	 * @param mixed &$val Current value to validate
	 * @param bool $standalone If this is a simple property or array
	 */
	public function validateFlash( $info, &$val, $standalone ): void {
		if ( $standalone ) {
			// this only validates flash structs, not individual properties
			// @codeCoverageIgnoreStart
			return;
			// @codeCoverageIgnoreEnd
		}
		if ( !isset( $val['Fired'] ) ||
			!isset( $val['Function'] ) ||
			!isset( $val['Mode'] ) ||
			!isset( $val['RedEyeMode'] ) ||
			!isset( $val['Return'] )
		) {
			$this->logger->info( __METHOD__ . ' Flash structure did not have all the required components' );
			$val = null;
		} else {
			// @phan-suppress-next-line PhanTypeInvalidRightOperandOfBitwiseOp
			$val = ( 0 | ( $val['Fired'] === 'True' )
				| ( (int)$val['Return'] << 1 )
				| ( (int)$val['Mode'] << 3 )
				// @phan-suppress-next-line PhanTypeInvalidLeftOperandOfIntegerOp
				| ( ( $val['Function'] === 'True' ) << 5 )
				// @phan-suppress-next-line PhanTypeInvalidLeftOperandOfIntegerOp
				| ( ( $val['RedEyeMode'] === 'True' ) << 6 ) );
		}
	}

	/**
	 * function to validate LangCode properties ( en-GB, etc. )
	 *
	 * This is just a naive check to make sure it somewhat looks like a lang code.
	 *
	 * @see BCP 47
	 * @see https://wwwimages2.adobe.com/content/dam/Adobe/en/devnet/xmp/pdfs/
	 *      XMP%20SDK%20Release%20cc-2014-12/XMPSpecificationPart1.pdf page 22 (section 8.2.2.4)
	 *
	 * @param array $info Information about current property
	 * @param mixed &$val Current value to validate
	 * @param bool $standalone If this is a simple property or array
	 */
	public function validateLangCode( $info, &$val, $standalone ): void {
		if ( !$standalone ) {
			// this only validates standalone properties, not arrays, etc
			// @codeCoverageIgnoreStart
			return;
			// @codeCoverageIgnoreEnd
		}
		if ( !preg_match( '/^[-A-Za-z0-9]{2,}$/D', $val ) ) {
			// this is a rather naive check.
			$this->logger->info( __METHOD__ . " Expected Lang code but got $val" );
			$val = null;
		}
	}

	/**
	 * function to validate date properties, and convert to (partial) Exif format.
	 *
	 * Dates can be one of the following formats:
	 * YYYY
	 * YYYY-MM
	 * YYYY-MM-DD
	 * YYYY-MM-DDThh:mmTZD
	 * YYYY-MM-DDThh:mm:ssTZD
	 * YYYY-MM-DDThh:mm:ss.sTZD
	 *
	 * @param array $info Information about current property
	 * @param mixed &$val Current value to validate. Converts to TS_EXIF as a side effect.
	 *    in cases where there's only a partial date, it will give things like
	 *    2011:04.
	 * @param bool $standalone If this is a simple property or array
	 */
	public function validateDate( $info, &$val, $standalone ): void {
		if ( !$standalone ) {
			// this only validates standalone properties, not arrays, etc
			// @codeCoverageIgnoreStart
			return;
			// @codeCoverageIgnoreEnd
		}
		$res = [];
		if ( !preg_match(
			/* ahh! scary regex... */
			// phpcs:ignore Generic.Files.LineLength
			'/^([0-3]\d{3})(?:-([01]\d)(?:-([0-3]\d)(?:T([0-2]\d):([0-6]\d)(?::([0-6]\d)(?:\.\d+)?)?([-+]\d{2}:\d{2}|Z)?)?)?)?$/D',
			$val, $res )
		) {
			$this->logger->info( __METHOD__ . " Expected date but got $val" );
			$val = null;
			return;
		}

		/*
		 * $res is formatted as follows:
		 * 0 -> full date.
		 * 1 -> year, 2-> month, 3-> day, 4-> hour, 5-> minute, 6->second
		 * 7-> Timezone specifier (Z or something like +12:30 )
		 * many parts are optional, some aren't. For example if you specify
		 * minute, you must specify hour, day, month, and year but not second or TZ.
		 */

		/*
		 * First of all, if year = 0000, Something is wrong-ish,
		 * so don't extract. This seems to happen when
		 * some programs convert between metadata formats.
		 */
		if ( $res[1] === '0000' ) {
			$this->logger->info( __METHOD__ . " Invalid date (year 0): $val" );
			$val = null;

			return;
		}

		// hour
		if ( !isset( $res[4] ) ) {
			// just have the year month day (if that)
			$val = $res[1];
			if ( isset( $res[2] ) ) {
				$val .= ':' . $res[2];
			}
			if ( isset( $res[3] ) ) {
				$val .= ':' . $res[3];
			}

			return;
		}

		if ( !isset( $res[7] ) || $res[7] === 'Z' ) {
			// if hour is set, then minute must also be or regex above will fail.
			$val = $res[1] . ':' . $res[2] . ':' . $res[3]
				. ' ' . $res[4] . ':' . $res[5];
			if ( isset( $res[6] ) && $res[6] !== '' ) {
				$val .= ':' . $res[6];
			}

			return;
		}

		// Extra check for empty string necessary due to TZ but no second case.
		$stripSeconds = false;
		if ( !isset( $res[6] ) || $res[6] === '' ) {
			$res[6] = '00';
			$stripSeconds = true;
		}

		// Do timezone processing. We've already done the case that tz = Z.

		// We know that if we got to this step, year, month day hour and min must be set
		// by virtue of regex not failing.

		$unix = ConvertibleTimestamp::convert( TS_UNIX,
			$res[1] . $res[2] . $res[3] . $res[4] . $res[5] . $res[6]
		);
		$offset = (int)substr( $res[7], 1, 2 ) * 60 * 60;
		$offset += (int)substr( $res[7], 4, 2 ) * 60;
		if ( substr( $res[7], 0, 1 ) === '-' ) {
			$offset = -$offset;
		}
		$val = ConvertibleTimestamp::convert( TS_EXIF, (int)$unix + $offset );

		if ( $stripSeconds ) {
			// If seconds weren't specified, remove the trailing ':00'.
			$val = substr( $val, 0, -3 );
		}
	}

	/** function to validate, and more importantly
	 * translate the XMP DMS form of gps coords to
	 * the decimal form we use.
	 *
	 * @see http://www.adobe.com/devnet/xmp/pdfs/XMPSpecificationPart2.pdf
	 *        section 1.2.7.4 on page 23
	 *
	 * @param array $info Unused (info about prop)
	 * @param string &$val GPS string in either DDD,MM,SSk or
	 *   or DDD,MM.mmk form
	 * @param bool $standalone If it's a simple prop (should always be true)
	 */
	public function validateGPS( $info, &$val, $standalone ): void {
		if ( !$standalone ) {
			// this only validates standalone properties, not arrays, etc
			// @codeCoverageIgnoreStart
			return;
			// @codeCoverageIgnoreEnd
		}

		$m = [];
		if ( preg_match(
			'/(\d{1,3}),(\d{1,2}),(\d{1,2})([NWSE])/D',
			$val, $m )
		) {
			$coord = (int)$m[1];
			$coord += (int)$m[2] * ( 1 / 60 );
			$coord += (int)$m[3] * ( 1 / 3600 );
			if ( $m[4] === 'S' || $m[4] === 'W' ) {
				$coord = -$coord;
			}
			$val = $coord;

			return;
		}

		if ( preg_match(
			'/(\d{1,3}),(\d{1,2}(?:.\d*)?)([NWSE])/D',
			$val, $m )
		) {
			$coord = (int)$m[1];
			$coord += (float)$m[2] * ( 1 / 60 );
			if ( $m[3] === 'S' || $m[3] === 'W' ) {
				$coord = -$coord;
			}
			$val = $coord;

			return;
		}

		$this->logger->info( __METHOD__
			. " Expected GPSCoordinate, but got $val." );
		$val = null;
	}
}
