<?php

/**
 * @file
 *
 * Contains \Drupal\Tests\csv_serialization\Unit\CsvEncoderTest.
 */

namespace Drupal\Tests\csv_serialization\Unit;

use Drupal\csv_serialization\Encoder\CsvEncoder;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the encoding and decoding functionality of CsvEncoder.
 *
 * @group test_example
 */
class CsvEncoderTest extends UnitTestCase {

  /**
   * @var \Drupal\csv_serialization\Encoder\CsvEncoder
   */
  public $conversionService;

  public function setUp() {
    $this->encoder = new CsvEncoder();
  }

  /**
   * @return array
   *   Am array of multi-dimentional arrays, to be converted to CSVs.
   */
  public function provideEncodeData() {
    $csv1_data = [
      // Row 1.
      [
      'title' => 'This is title 1',
      'body' => 'This is, body 1',
      'images' => ['img1.jpg'],
      'alias' => '',
      'status' => 1,
      ],
      // Row 2.
      [
      'title' => 'This is title 2',
      'body' => '<p>This is, body 2</p>',
      'images' => ['img1.jpg', 'img2.jpg'],
      'alias' => '',
      'status' => 0,
      ],
      // Row 3.
      [
      'title' => 'This is title 3',
      'body' => ['<p>This is, body 3</p>'],
      'images' => [
        [
          'src' => 'img1.jpg',
          'alt' => 'Image 1',
        ],
        [
          'src' => 'img2.jpg',
          'alt' => 'Image, 2',
        ],
      ],
      'alias' => '',
      'status' => 0,
      ],
    ];

    $csv1_encoded = trim(file_get_contents(__DIR__ . '/CsvEncoderTest.csv'));

    return [
      [$csv1_data, $csv1_encoded],
    ];
  }

  /**
   *
   */
  public function provideDecodeData() {
    $csv1_encoded = file_get_contents(__DIR__ . '/CsvEncoderTest.csv');
    $csv1_data = [
      // Row 1.
      [
        'title' => 'This is title 1',
        'body' => 'This is, body 1',
        'images' => 'img1.jpg',
        'alias' => '',
        'status' => 1,
      ],
      // Row 2.
      [
        'title' => 'This is title 2',
        'body' => 'This is, body 2',
        'images' => ['img1.jpg', 'img2.jpg'],
        'alias' => '',
        'status' => 0,
      ],
      // Row 3.
      [
        'title' => 'This is title 3',
        'body' => 'This is, body 3',
        // Note that due to the flattening of multi-dimensional arrays
        // during encoding, this does not match Row 3 in provideCsvData().
        'images' => [
          'img1.jpg',
          'Image 1',
          'img2.jpg',
          'Image, 2',
        ],
        'alias' => '',
        'status' => 0,
      ],
    ];

    return [
      [$csv1_encoded, $csv1_data],
    ];
  }

  /**
   * @dataProvider provideEncodeData
   */
  public function testEncodeCsv($csv_data, $csv_encoded) {
    // @todo Test passing in arguments to the constructor. E.g., $separator, $enclosure, strip_tags, etc.
    // Note that what we encode does not exactly represent the hierarchy of
    // the data passed in. This is because cells containing multidimensional
    // arrays are flattened. Thus, encode($input) != decode($output).
    $this->assertEquals($csv_encoded, $this->encoder->encode($csv_data, 'csv'));
  }

  /**
   * @dataProvider provideDecodeData
   */
  public function testDecodeCsv($csv_encoded, $csv_data) {
    // Note that what we encode does not exactly represent the hierarchy of
    // the data passed in. This is because cells containing multidimensional
    // arrays are flattened. Thus, encode($input) != decode($output).
    $this->assertEquals($csv_data, $this->encoder->decode($csv_encoded, 'csv'));
  }
}
