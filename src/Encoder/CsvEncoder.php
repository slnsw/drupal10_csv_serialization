<?php

/**
 * @file
 * Contains \Drupal\csv_serialization\Encoder\CsvEncoder.
 */

namespace Drupal\csv_serialization\Encoder;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use League\Csv\Writer;
use SplTempFileObject;
use Drupal\Component\Utility\Html;

/**
 * Adds CSV encoder support for the Serialization API.
 */
class CsvEncoder implements EncoderInterface, DecoderInterface {


  /**
   * Indicates the character used to delimit fields. Defaults to ",".
   *
   * @var string
   */
  protected $delimiter;

  /**
   * Indicates the character used for field enclosure. Defaults to '"'.
   *
   * @var string
   */
  protected $enclosure;

  /**
   * Indicates the character used for escaping.
   *
   * Defaults to "\".
   *
   * @var bool
   */
  protected $escapeChar;

  /**
   * The formats that this Encoder supports.
   *
   * @var array
   */
  protected static $format = 'csv';

  /**
   * Constructs the class.
   *
   * @param string $delimiter
   *   Indicates the character used to delimit fields. Defaults to ",".
   *
   * @param string $enclosure
   *   Indicates the character used for field enclosure. Defaults to '"'.
   * @param string $escape_char
   */
  public function __construct($delimiter = ",", $enclosure = '"', $escape_char = "\\") {
    $this->delimiter = $delimiter;
    $this->enclosure = $enclosure;
    $this->escapeChar = $escape_char;

    if (! ini_get("auto_detect_line_endings")) {
      ini_set("auto_detect_line_endings", '1');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return $format == static::$format;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDecoding($format) {
    return $format == static::$format;
  }

  /**
   * {@inheritdoc}
   *
   * Uses HTML-safe strings, with several characters escaped.
   */
  public function encode($data, $format, array $context = array()) {
    switch (gettype($data)) {
      case "array":
        break;

      case 'object':
        $data = (array) $data;
        break;

      // May be bool, integer, double, string, resource, NULL, or unknown.
      default:
        $data = array($data);
        break;
    }

    try {
      // Instantiate CSV writer with options.
      $csv = Writer::createFromFileObject(new SplTempFileObject());
      $csv->setDelimiter($this->delimiter);
      $csv->setEnclosure($this->enclosure);
      $csv->setEscape($this->escapeChar);

      // Set data.
      $headers = $this->extractHeaders($data);
      $csv->insertOne($headers);
      $csv->addFormatter(array($this, 'flattenArray'));
      foreach ($data as $row) {
        $csv->insertOne($row);
      }
      $output = $csv->__toString();

      return $output;
    }
    catch (\Exception $e) {
      throw new InvalidDataTypeException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * Extracts the headers using the first row of values.
   *
   * @param array $data
   *   The array of data to be converted to a CSV.
   *
   * We must make the assumption that each row shares the same set of headers
   * will all other rows. This is inherent in the structure of a CSV.
   *
   * @return array
   *   An array of CSV headesr.
   */
  protected function extractHeaders($data) {
    $first_row = $data[0];
    $headers = array_keys($first_row);

    return $headers;
  }

  /**
   * Reduces a multidimensional array to a depth of 2.
   *
   * All array of depth 3 are imploded into a single value. Arrays of depth
   * greater than 3 will be represented simply as "array".
   *
   * @param $row
   * @return array
   */
  protected function flattenArray($row) {
    $formatted_row = array();

    foreach ($row as $field_name => $field) {
      if (sizeof($field) > 0) {
        foreach ($field as $delta => $properties) {
          // Note that property keys are not preserved.
          // @todo Add validation for arrays of depth greater than 3.
          if (sizeof($properties) > 1) {
            $value = implode('|', $properties);
          }
          else {
            $value = reset($properties);
          }

          $formatted_row[] = $this->formatValue($value);
        }
      }
      else {
        $formatted_row[] = "";
      }
    }

    return $formatted_row;
  }

  /**
   * Formats a single value for a given CSV cell.
   *
   * @param string $value
   *   The raw value to be formatted.
   *
   * @return string
   *   The formatted value.
   *
   */
  protected function formatValue($value) {
    // @todo Make these filters configurable.
    $value = Html::decodeEntities($value);
    $value = strip_tags($value);
    $value = trim($value);
    $value = utf8_decode($value);

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function decode($data, $format, array $context = array()) {
    return str_getcsv($data, $this->delimiter, $this->enclosure, $this->escape_char);
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileExtension() {
    return static::$format;
  }

}
