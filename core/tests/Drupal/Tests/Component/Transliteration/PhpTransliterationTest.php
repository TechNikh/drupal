<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Transliteration\PhpTransliterationTest.
 */

namespace Drupal\Tests\Component\Transliteration;

use Drupal\Component\Transliteration\PhpTransliteration;
use Drupal\Component\Utility\Random;
use Drupal\Tests\UnitTestCase;

/**
 * Tests Transliteration component functionality.
 *
 * @group Transliteration
 *
 * @coversClass \Drupal\Component\Transliteration\PhpTransliteration
 */
class PhpTransliterationTest extends UnitTestCase {

  /**
   * Tests the PhpTransliteration class.
   *
   * @param string $langcode
   *   The language code to test.
   * @param string $original
   *   The original string.
   * @param string $expected
   *   The expected return from PhpTransliteration::transliterate().
   * @param string $unknown_character
   *   (optional) The character to substitute for characters in $string without
   *   transliterated equivalents. Defaults to '?'.
   * @param int $max_length
   *   (optional) If provided, return at most this many characters, ensuring
   *   that the transliteration does not split in the middle of an input
   *   character's transliteration.
   *
   * @dataProvider providerTestPhpTransliteration
   */
  public function testPhpTransliteration($langcode, $original, $expected, $unknown_character = '?', $max_length = NULL) {
    $transliterator_class = new PhpTransliteration();
    $actual = $transliterator_class->transliterate($original, $langcode, $unknown_character, $max_length);
    $this->assertSame($expected, $actual);
  }

  /**
   * Provides data for self::testPhpTransliteration().
   *
   * @return array
   *   An array of arrays, each containing the parameters for
   *   self::testPhpTransliteration().
   */
  public function providerTestPhpTransliteration() {
    $random_generator = new Random();
    $random = $random_generator->string(10);
    // Make some strings with two, three, and four-byte characters for testing.
    // Note that the 3-byte character is overridden by the 'kg' language.
    $two_byte = 'Ä Ö Ü Å Ø äöüåøhello';
    // This is a Cyrrillic character that looks something like a u. See
    // http://www.unicode.org/charts/PDF/U0400.pdf
    $three_byte = html_entity_decode('&#x446;', ENT_NOQUOTES, 'UTF-8');
    // This is a Canadian Aboriginal character like a triangle. See
    // http://www.unicode.org/charts/PDF/U1400.pdf
    $four_byte = html_entity_decode('&#x1411;', ENT_NOQUOTES, 'UTF-8');
    // These are two Gothic alphabet letters. See
    // http://en.wikipedia.org/wiki/Gothic_alphabet
    // They are not in our tables, but should at least give us '?' (unknown).
    $five_byte = html_entity_decode('&#x10330;&#x10338;', ENT_NOQUOTES, 'UTF-8');

    return array(
      // Each test case is (language code, input, output).
      // Test ASCII in English.
      array('en', $random, $random),
      // Test ASCII in some other language with no overrides.
      array('fr', $random, $random),
      // Test 3 and 4-byte characters in a language without overrides.
      // Note: if the data tables change, these will need to change too! They
      // are set up to test that data table loading works, so values come
      // directly from the data files.
      array('fr', $three_byte, 'c'),
      array('fr', $four_byte, 'wii'),
      // Test 5-byte characters.
      array('en', $five_byte, '??'),
      // Test a language with no overrides.
      array('en', $two_byte, 'A O U A O aouaohello'),
      // Test language overrides provided by core.
      array('de', $two_byte, 'Ae Oe Ue A O aeoeueaohello'),
      array('de', $random, $random),
      array('dk', $two_byte, 'A O U Aa Oe aouaaoehello'),
      array('dk', $random, $random),
      array('kg', $three_byte, 'ts'),
      // Test strings in some other languages.
      // Turkish, provided by drupal.org user Kartagis.
      array('tr', 'Abayı serdiler bize. Söyleyeceğim yüzlerine. Sanırım hepimiz aynı şeyi düşünüyoruz.', 'Abayi serdiler bize. Soyleyecegim yuzlerine. Sanirim hepimiz ayni seyi dusunuyoruz.'),
      // Illegal/unknown unicode.
      array('en', chr(0xF8) . chr(0x80) . chr(0x80) . chr(0x80) . chr(0x80), '?'),
      // Max length.
      array('de', $two_byte, 'Ae Oe', '?', 5),
    );
  }

  /**
   * Tests the transliteration with max length.
   */
  public function testTransliterationWithMaxLength() {
    $transliteration = new PhpTransliteration();

    // Test with max length, using German. It should never split up the
    // transliteration of a single character.
    $input = 'Ä Ö Ü Å Ø äöüåøhello';
    $trunc_output = 'Ae Oe Ue A O aeoe';

    $this->assertSame($trunc_output, $transliteration->transliterate($input, 'de', '?', 17), 'Truncating to 17 characters works');
    $this->assertSame($trunc_output, $transliteration->transliterate($input, 'de', '?', 18), 'Truncating to 18 characters works');
  }

}
