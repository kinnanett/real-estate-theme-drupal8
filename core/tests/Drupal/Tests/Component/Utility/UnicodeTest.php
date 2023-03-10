<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\UnicodeTest.
 */

namespace Drupal\Tests\Component\Utility;

use Drupal\Tests\UnitTestCase;
use Drupal\Component\Utility\Unicode;

/**
 * Test unicode handling features implemented in Unicode component.
 *
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\Unicode
 */
class UnicodeTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   *
   * @covers ::check
   */
  protected function setUp() {
    // Initialize unicode component.
    Unicode::check();
  }

  /**
   * Getting and settings the multibyte environment status.
   *
   * @dataProvider providerTestStatus
   * @covers ::getStatus
   * @covers ::setStatus
   */
  public function testStatus($value, $expected, $invalid = FALSE) {
    if ($invalid) {
      $this->setExpectedException('InvalidArgumentException');
    }
    Unicode::setStatus($value);
    $this->assertEquals($expected, Unicode::getStatus());
  }

  /**
   * Data provider for testStatus().
   *
   * @see testStatus()
   *
   * @return array
   *   An array containing:
   *     - The status value to set.
   *     - The status value to expect after setting the new value.
   *     - (optional) Boolean indicating invalid status. Defaults to FALSE.
   */
  public function providerTestStatus() {
    return array(
      array(Unicode::STATUS_SINGLEBYTE, Unicode::STATUS_SINGLEBYTE),
      array(rand(10, 100), Unicode::STATUS_SINGLEBYTE, TRUE),
      array(rand(10, 100), Unicode::STATUS_SINGLEBYTE, TRUE),
      array(Unicode::STATUS_MULTIBYTE, Unicode::STATUS_MULTIBYTE),
      array(rand(10, 100), Unicode::STATUS_MULTIBYTE, TRUE),
      array(Unicode::STATUS_ERROR, Unicode::STATUS_ERROR),
      array(Unicode::STATUS_MULTIBYTE, Unicode::STATUS_MULTIBYTE),
    );
  }

  /**
   * Tests multibyte encoding and decoding.
   *
   * @dataProvider providerTestMimeHeader
   * @covers ::mimeHeaderEncode
   * @covers ::mimeHeaderDecode
   */
  public function testMimeHeader($value, $encoded) {
    $this->assertEquals($encoded, Unicode::mimeHeaderEncode($value));
    $this->assertEquals($value, Unicode::mimeHeaderDecode($encoded));
  }

  /**
   * Data provider for testMimeHeader().
   *
   * @see testMimeHeader()
   *
   * @return array
   *   An array containing a string and its encoded value.
   */
  public function providerTestMimeHeader() {
    return array(
      array('t??st.txt', '=?UTF-8?B?dMOpc3QudHh0?='),
      // Simple ASCII characters.
      array('ASCII', 'ASCII'),
    );
  }

  /**
   * Tests multibyte strtolower.
   *
   * @dataProvider providerStrtolower
   * @covers ::strtolower
   * @covers ::caseFlip
   */
  public function testStrtolower($text, $expected, $multibyte = FALSE) {
    $status = $multibyte ? Unicode::STATUS_MULTIBYTE : Unicode::STATUS_SINGLEBYTE;
    Unicode::setStatus($status);
    $this->assertEquals($expected, Unicode::strtolower($text));
  }

  /**
   * Data provider for testStrtolower().
   *
   * @see testStrtolower()
   *
   * @return array
   *   An array containing a string, its lowercase version and whether it should
   *   be processed as multibyte.
   */
  public function providerStrtolower() {
    $cases = array(
      array('tHe QUIcK bRoWn', 'the quick brown'),
      array('Fran??AIS is ??BER-??wesome', 'fran??ais is ??ber-??wesome'),
    );
    foreach ($cases as $case) {
      // Test the same string both in multibyte and singlebyte conditions.
      array_push($case, TRUE);
      $cases[] = $case;
    }
    // Add a multibyte string.
    $cases[] = array('???????????????????????????????????', '???????????????????????????????????', TRUE);
    return $cases;
  }

  /**
   * Tests multibyte strtoupper.
   *
   * @dataProvider providerStrtoupper
   * @covers ::strtoupper
   * @covers ::caseFlip
   */
  public function testStrtoupper($text, $expected, $multibyte = FALSE) {
    $status = $multibyte ? Unicode::STATUS_MULTIBYTE : Unicode::STATUS_SINGLEBYTE;
    Unicode::setStatus($status);
    $this->assertEquals($expected, Unicode::strtoupper($text));
  }

  /**
   * Data provider for testStrtoupper().
   *
   * @see testStrtoupper()
   *
   * @return array
   *   An array containing a string, its uppercase version and whether it should
   *   be processed as multibyte.
   */
  public function providerStrtoupper() {
    $cases = array(
      array('tHe QUIcK bRoWn', 'THE QUICK BROWN'),
      array('Fran??AIS is ??BER-??wesome', 'FRAN??AIS IS ??BER-??WESOME'),
    );
    foreach ($cases as $case) {
      // Test the same string both in multibyte and singlebyte conditions.
      array_push($case, TRUE);
      $cases[] = $case;
    }
    // Add a multibyte string.
    $cases[] = array('???????????????????????????????????', '???????????????????????????????????', TRUE);
    return $cases;
  }

  /**
   * Tests multibyte ucfirst.
   *
   * @dataProvider providerUcfirst
   * @covers ::ucfirst
   */
  public function testUcfirst($text, $expected) {
    $this->assertEquals($expected, Unicode::ucfirst($text));
  }

  /**
   * Data provider for testUcfirst().
   *
   * @see testUcfirst()
   *
   * @return array
   *   An array containing a string and its uppercase first version.
   */
  public function providerUcfirst() {
    return array(
      array('tHe QUIcK bRoWn', 'THe QUIcK bRoWn'),
      array('fran??AIS', 'Fran??AIS'),
      array('??ber', '??ber'),
      array('??wesome', '??wesome'),
      // A multibyte string.
      array('??ion', '??ion'),
    );
  }

  /**
   * Tests multibyte lcfirst.
   *
   * @dataProvider providerLcfirst
   * @covers ::lcfirst
   */
  public function testLcfirst($text, $expected, $multibyte = FALSE) {
    $status = $multibyte ? Unicode::STATUS_MULTIBYTE : Unicode::STATUS_SINGLEBYTE;
    Unicode::setStatus($status);
    $this->assertEquals($expected, Unicode::lcfirst($text));
  }

  /**
   * Data provider for testLcfirst().
   *
   * @see testLcfirst()
   *
   * @return array
   *   An array containing a string, its lowercase version and whether it should
   *   be processed as multibyte.
   */
  public function providerLcfirst() {
    return array(
      array('tHe QUIcK bRoWn', 'tHe QUIcK bRoWn'),
      array('Fran??AIS is ??BER-??wesome', 'fran??AIS is ??BER-??wesome'),
      array('??ber', '??ber'),
      array('??wesome', '??wesome'),
      // Add a multibyte string.
      array('???????????????????????????????????', '???????????????????????????????????', TRUE),
    );
  }

  /**
   * Tests multibyte ucwords.
   *
   * @dataProvider providerUcwords
   * @covers ::ucwords
   */
  public function testUcwords($text, $expected, $multibyte = FALSE) {
    $status = $multibyte ? Unicode::STATUS_MULTIBYTE : Unicode::STATUS_SINGLEBYTE;
    Unicode::setStatus($status);
    $this->assertEquals($expected, Unicode::ucwords($text));
  }

  /**
   * Data provider for testUcwords().
   *
   * @see testUcwords()
   *
   * @return array
   *   An array containing a string, its capitalized version and whether it should
   *   be processed as multibyte.
   */
  public function providerUcwords() {
    return array(
      array('tHe QUIcK bRoWn', 'THe QUIcK BRoWn'),
      array('fran??AIS', 'Fran??AIS'),
      array('??ber', '??ber'),
      array('??wesome', '??wesome'),
      // Make sure we don't mangle extra spaces.
      array('fr??n??AIS is  ??ber-??wesome', 'Fr??n??AIS Is  ??ber-??wesome'),
      // Add a multibyte string.
      array('??ion', '??ion', TRUE),
    );
  }

  /**
   * Tests multibyte strlen.
   *
   * @dataProvider providerStrlen
   * @covers ::strlen
   */
  public function testStrlen($text, $expected) {
    // Run through multibyte code path.
    Unicode::setStatus(Unicode::STATUS_MULTIBYTE);
    $this->assertEquals($expected, Unicode::strlen($text));
    // Run through singlebyte code path.
    Unicode::setStatus(Unicode::STATUS_SINGLEBYTE);
    $this->assertEquals($expected, Unicode::strlen($text));
  }

  /**
   * Data provider for testStrlen().
   *
   * @see testStrlen()
   *
   * @return array
   *   An array containing a string and its length.
   */
  public function providerStrlen() {
    return array(
      array('tHe QUIcK bRoWn', 15),
      array('??BER-??wesome', 12),
      array('?????????????????????????????????????????????', 15),
    );
  }

  /**
   * Tests multibyte substr.
   *
   * @dataProvider providerSubstr
   * @covers ::substr
   */
  public function testSubstr($text, $start, $length, $expected) {
    // Run through multibyte code path.
    Unicode::setStatus(Unicode::STATUS_MULTIBYTE);
    $this->assertEquals($expected, Unicode::substr($text, $start, $length));
    // Run through singlebyte code path.
    Unicode::setStatus(Unicode::STATUS_SINGLEBYTE);
    $this->assertEquals($expected, Unicode::substr($text, $start, $length));
  }

  /**
   * Data provider for testSubstr().
   *
   * @see testSubstr()
   *
   * @return array
   *   An array containing:
   *     - The string to test.
   *     - The start number to be processed by substr.
   *     - The length number to be processed by substr.
   *     - The expected string result.
   */
  public function providerSubstr() {
    return array(
      array('fr??n??AIS is ??ber-??wesome', 0, NULL, 'fr??n??AIS is ??ber-??wesome'),
      array('fr??n??AIS is ??ber-??wesome', 0, 0, ''),
      array('fr??n??AIS is ??ber-??wesome', 0, 1, 'f'),
      array('fr??n??AIS is ??ber-??wesome', 0, 8, 'fr??n??AIS'),
      array('fr??n??AIS is ??ber-??wesome', 0, 23, 'fr??n??AIS is ??ber-??wesom'),
      array('fr??n??AIS is ??ber-??wesome', 0, 24, 'fr??n??AIS is ??ber-??wesome'),
      array('fr??n??AIS is ??ber-??wesome', 0, 25, 'fr??n??AIS is ??ber-??wesome'),
      array('fr??n??AIS is ??ber-??wesome', 0, 100, 'fr??n??AIS is ??ber-??wesome'),
      array('fr??n??AIS is ??ber-??wesome', 4, 4, '??AIS'),
      array('fr??n??AIS is ??ber-??wesome', 1, 0, ''),
      array('fr??n??AIS is ??ber-??wesome', 100, 0, ''),
      array('fr??n??AIS is ??ber-??wesome', -4, 2, 'so'),
      array('fr??n??AIS is ??ber-??wesome', -4, 3, 'som'),
      array('fr??n??AIS is ??ber-??wesome', -4, 4, 'some'),
      array('fr??n??AIS is ??ber-??wesome', -4, 5, 'some'),
      array('fr??n??AIS is ??ber-??wesome', -7, 10, '??wesome'),
      array('fr??n??AIS is ??ber-??wesome', 5, -10, 'AIS is ??b'),
      array('fr??n??AIS is ??ber-??wesome', 0, -10, 'fr??n??AIS is ??b'),
      array('fr??n??AIS is ??ber-??wesome', 0, -1, 'fr??n??AIS is ??ber-??wesom'),
      array('fr??n??AIS is ??ber-??wesome', -7, -2, '??weso'),
      array('fr??n??AIS is ??ber-??wesome', -7, -6, '??'),
      array('fr??n??AIS is ??ber-??wesome', -7, -7, ''),
      array('fr??n??AIS is ??ber-??wesome', -7, -8, ''),
      array('...', 0, 2, '..'),
      array('?????????????????????????????????????????????', 1, 3, '?????????'),
    );
  }

  /**
   * Tests multibyte truncate.
   *
   * @dataProvider providerTruncate
   * @covers ::truncate
   */
  public function testTruncate($text, $max_length, $expected, $wordsafe = FALSE, $add_ellipsis = FALSE) {
    $this->assertEquals($expected, Unicode::truncate($text, $max_length, $wordsafe, $add_ellipsis));
  }

  /**
   * Data provider for testTruncate().
   *
   * @see testTruncate()
   *
   * @return array
   *   An array containing:
   *     - The string to test.
   *     - The max length to truncate this string to.
   *     - The expected string result.
   *     - (optional) Boolean for the $wordsafe flag. Defaults to FALSE.
   *     - (optional) Boolean for the $add_ellipsis flag. Defaults to FALSE.
   */
  public function providerTruncate() {
    return array(
      array('fr??n??AIS is ??ber-??wesome', 24, 'fr??n??AIS is ??ber-??wesome'),
      array('fr??n??AIS is ??ber-??wesome', 23, 'fr??n??AIS is ??ber-??wesom'),
      array('fr??n??AIS is ??ber-??wesome', 17, 'fr??n??AIS is ??ber-'),
      array('?????????????????????????????????????????????', 6, '??????????????????'),
      array('fr??n??AIS is ??ber-??wesome', 24, 'fr??n??AIS is ??ber-??wesome', FALSE, TRUE),
      array('fr??n??AIS is ??ber-??wesome', 23, 'fr??n??AIS is ??ber-??weso???', FALSE, TRUE),
      array('fr??n??AIS is ??ber-??wesome', 17, 'fr??n??AIS is ??ber???', FALSE, TRUE),
      array('123', 1, '???', TRUE, TRUE),
      array('123', 2, '1???', TRUE, TRUE),
      array('123', 3, '123', TRUE, TRUE),
      array('1234', 3, '12???', TRUE, TRUE),
      array('1234567890', 10, '1234567890', TRUE, TRUE),
      array('12345678901', 10, '123456789???', TRUE, TRUE),
      array('12345678901', 11, '12345678901', TRUE, TRUE),
      array('123456789012', 11, '1234567890???', TRUE, TRUE),
      array('12345 7890', 10, '12345 7890', TRUE, TRUE),
      array('12345 7890', 9, '12345???', TRUE, TRUE),
      array('123 567 90', 10, '123 567 90', TRUE, TRUE),
      array('123 567 901', 10, '123 567???', TRUE, TRUE),
      array('Stop. Hammertime.', 17, 'Stop. Hammertime.', TRUE, TRUE),
      array('Stop. Hammertime.', 16, 'Stop???', TRUE, TRUE),
      array('fr??n??AIS is ??ber-??wesome', 24, 'fr??n??AIS is ??ber-??wesome', TRUE, TRUE),
      array('fr??n??AIS is ??ber-??wesome', 23, 'fr??n??AIS is ??ber???', TRUE, TRUE),
      array('fr??n??AIS is ??ber-??wesome', 17, 'fr??n??AIS is ??ber???', TRUE, TRUE),
      array('??D??nde est?? el ni??o?', 20, '??D??nde est?? el ni??o?', TRUE, TRUE),
      array('??D??nde est?? el ni??o?', 19, '??D??nde est?? el???', TRUE, TRUE),
      array('??D??nde est?? el ni??o?', 13, '??D??nde est?????', TRUE, TRUE),
      array('??D??nde est?? el ni??o?', 10, '??D??nde???', TRUE, TRUE),
      array('Help! Help! Help!', 17, 'Help! Help! Help!', TRUE, TRUE),
      array('Help! Help! Help!', 16, 'Help! Help!???', TRUE, TRUE),
      array('Help! Help! Help!', 15, 'Help! Help!???', TRUE, TRUE),
      array('Help! Help! Help!', 14, 'Help! Help!???', TRUE, TRUE),
      array('Help! Help! Help!', 13, 'Help! Help!???', TRUE, TRUE),
      array('Help! Help! Help!', 12, 'Help! Help!???', TRUE, TRUE),
      array('Help! Help! Help!', 11, 'Help! Help???', TRUE, TRUE),
      array('Help! Help! Help!', 10, 'Help!???', TRUE, TRUE),
      array('Help! Help! Help!', 9, 'Help!???', TRUE, TRUE),
      array('Help! Help! Help!', 8, 'Help!???', TRUE, TRUE),
      array('Help! Help! Help!', 7, 'Help!???', TRUE, TRUE),
      array('Help! Help! Help!', 6, 'Help!???', TRUE, TRUE),
      array('Help! Help! Help!', 5, 'Help???', TRUE, TRUE),
      array('Help! Help! Help!', 4, 'Hel???', TRUE, TRUE),
      array('Help! Help! Help!', 3, 'He???', TRUE, TRUE),
      array('Help! Help! Help!', 2, 'H???', TRUE, TRUE),
    );
  }

  /**
   * Tests multibyte truncate bytes.
   *
   * @dataProvider providerTestTruncateBytes
   * @covers ::truncateBytes
   *
   * @param string $text
   *   The string to truncate.
   * @param int $max_length
   *   The upper limit on the returned string length.
   * @param string $expected
   *   The expected return from Unicode::truncateBytes().
   */
  public function testTruncateBytes($text, $max_length, $expected) {
    $this->assertEquals($expected, Unicode::truncateBytes($text, $max_length), 'The string was not correctly truncated.');
  }

  /**
   * Provides data for self::testTruncateBytes().
   *
   * @return array
   *   An array of arrays, each containing the parameters to
   *   self::testTruncateBytes().
   */
  public function providerTestTruncateBytes() {
    return array(
      // String shorter than max length.
      array('Short string', 42, 'Short string'),
      // Simple string longer than max length.
      array('Longer string than previous.', 10, 'Longer str'),
      // Unicode.
      array('?????????????????????????????????????????????', 10, '?????????'),
    );
  }

  /**
   * Tests UTF-8 validation.
   *
   * @dataProvider providerTestValidateUtf8
   * @covers ::validateUtf8
   *
   * @param string $text
   *   The text to validate.
   * @param bool $expected
   *   The expected return value from Unicode::validateUtf8().
   * @param string $message
   *   The message to display on failure.
   */
  public function testValidateUtf8($text, $expected, $message) {
    $this->assertEquals($expected, Unicode::validateUtf8($text), $message);
  }

  /**
   * Provides data for self::testValidateUtf8().
   *
   * @return array
   *   An array of arrays, each containing the parameters for
   *   self::testValidateUtf8().
   *
   * Invalid UTF-8 examples sourced from http://stackoverflow.com/a/11709412/109119.
   */
  public function providerTestValidateUtf8() {
    return array(
      // Empty string.
      array('', TRUE, 'An empty string did not validate.'),
      // Simple text string.
      array('Simple text.', TRUE, 'A simple ASCII text string did not validate.'),
      // Invalid UTF-8, overlong 5 byte encoding.
      array(chr(0xF8) . chr(0x80) . chr(0x80) . chr(0x80) . chr(0x80), FALSE, 'Invalid UTF-8 was validated.'),
      // High code-point without trailing characters.
      array(chr(0xD0) . chr(0x01), FALSE, 'Invalid UTF-8 was validated.'),
    );
  }

  /**
   * Tests UTF-8 conversion.
   *
   * @dataProvider providerTestConvertToUtf8
   * @covers ::convertToUtf8
   *
   * @param string $data
   *   The data to be converted.
   * @param string $encoding
   *   The encoding the data is in.
   * @param string|bool $expected
   *   The expected result.
   */
  public function testConvertToUtf8($data, $encoding, $expected) {
    $this->assertEquals($expected, Unicode::convertToUtf8($data, $encoding));
  }

  /**
   * Provides data to self::testConvertToUtf8().
   *
   * @return array
   *   An array of arrays, each containing the parameters to
   *   self::testConvertUtf8().  }
   */
  public function providerTestConvertToUtf8() {
    return array(
      array(chr(0x97), 'Windows-1252', '???'),
      array(chr(0x99), 'Windows-1252', '???'),
      array(chr(0x80), 'Windows-1252', '???'),
    );
  }

  /**
   * Tests multibyte strpos.
   *
   * @dataProvider providerStrpos
   * @covers ::strpos
   */
  public function testStrpos($haystack, $needle, $offset, $expected) {
    // Run through multibyte code path.
    Unicode::setStatus(Unicode::STATUS_MULTIBYTE);
    $this->assertEquals($expected, Unicode::strpos($haystack, $needle, $offset));
    // Run through singlebyte code path.
    Unicode::setStatus(Unicode::STATUS_SINGLEBYTE);
    $this->assertEquals($expected, Unicode::strpos($haystack, $needle, $offset));
  }

  /**
   * Data provider for testStrpos().
   *
   * @see testStrpos()
   *
   * @return array
   *   An array containing:
   *     - The haystack string to be searched in.
   *     - The needle string to search for.
   *     - The offset integer to start at.
   *     - The expected integer/FALSE result.
   */
  public function providerStrpos() {
    return array(
      array('fr??n??AIS is ??ber-??wesome', 'fr??n??AIS is ??ber-??wesome', 0, 0),
      array('fr??n??AIS is ??ber-??wesome', 'r??n??AIS is ??ber-??wesome', 0, 1),
      array('fr??n??AIS is ??ber-??wesome', 'not in string', 0, FALSE),
      array('fr??n??AIS is ??ber-??wesome', 'r', 0, 1),
      array('fr??n??AIS is ??ber-??wesome', 'n??AIS', 0, 3),
      array('fr??n??AIS is ??ber-??wesome', 'n??AIS', 2, 3),
      array('fr??n??AIS is ??ber-??wesome', 'n??AIS', 3, 3),
      array('?????????????????????????????????????????????', '??????', 0, 2),
      array('?????????????????????????????????????????????', '??????', 1, 2),
      array('?????????????????????????????????????????????', '??????', 2, 2),
    );
  }

}
