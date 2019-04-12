<?php

namespace Drupal\example_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\SkipOnValue;

/**
 * A specialized call of SkipOnValue, for filetype handling.
 *
 * @MigrateProcessPlugin(
 *   id = "skip_by_file_type"
 * )
 *
 * Available configuration keys:
 * - value: A file type or array of file types, which will be compared to the
 *   row value.
 * - not_equals: (optional) If set, skipping occurs when values are not equal.
 * - method: What to do if the input value equals to value given in
 *   configuration key value. Possible values:
 *   - row: Skips the entire row.
 *   - process: Prevents further processing of the input property
 *
 * Examples:
 * @code
 *   type:
 *     plugin: skip_by_file_type
 *     not_equals: true
 *     source: filename
 *     method: row
 *     value:
 *       - 'image/gif'
 *       - 'image/jpg'
 * @endcode
 *
 * The above example will skip processing any row for which the source row's
 * file extension is *not* "gif" or "jpg".
 */
class SkipByFileType extends SkipOnValue {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $type = mime_content_type($value);
    return parent::transform($type, $migrate_executable, $row, $destination_property);

  }

}
