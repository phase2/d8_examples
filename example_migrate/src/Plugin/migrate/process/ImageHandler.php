<?php

namespace Drupal\example_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

/**
 * Taking inline referenced images and making them into media entities.
 *
 * Available config keys:
 * - source: The source string.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "image_handler"
 * )
 *
 * This handler requires a text field, field_original_ref, be added to the image
 * media entity on the destination drupal instance. This makes it possible to
 * look up and see if that inline image has already been parsed and made into an
 * entity, based only on the filepath from the source content <img> tag.
 */
class ImageHandler extends ProcessPluginBase {

  // Define source file location. Put your image files here!
  const FILE_SOURCE = 'private://legacy';

  // Define target file location. /legacy centralizes all of the old images.
  const FILE_DEST = 'public://legacy';

  // Define file owner. Setting to admin user by default.
  const FILE_OWNER = 1;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_string($value)) {
      throw new MigrateException(sprintf('%s is not a string', var_export($value, TRUE)));
    }
    if (!empty($value)) {
      // Load HTML items into DOM parser and get images.
      $doc = new \DOMDocument();
      $doc->loadHTML($value);
      $images = $doc->getElementsByTagName('img');
      /* We're reloading the content value because all this DOM handling cleans
      up the end of image tags (/>) and the str replace doesn't work otherwise.
      DOMDoc wraps a body tag around it, so we're removing it. */
      $body = $doc->getElementsByTagName('body');
      $body_val = str_ireplace([
        "<body>",
        "</body>",
      ], "", $doc->saveXML($body->item(0)));

      // Loop through images to make into file/media entities and replace.
      foreach ($images as $img) {
        $src = $img->getAttribute('src');
        $alt = $img->getAttribute('alt');
        $title = $img->getAttribute('title');
        $mid = '';
        if (!stripos($src, 'image/png')) {
          // Lookup the media entity by old path in field_original_ref.
          $query = \Drupal::entityQuery('media')
            ->condition('status', 1)
            ->condition('field_original_ref', $src, '=');
          $mids = $query->execute();
          // If found, `mid` is the reference for the content.
          if (!empty($mids)) {
            $mid = array_pop($mids);
            $media = Media::load($mid);
            $muuid = $media->uuid();
          }
          // Otherwise, create image and media entities.
          else {
            $source = self::FILE_SOURCE . $src;
            $destination = self::FILE_DEST . $src;
            $filename = pathinfo($src, PATHINFO_BASENAME);

            if (file_exists($source)) {
              // Create file entity.
              $image = File::create();
              $image->setFileUri($destination);
              $image->setFilename($filename);
              $image->setMimeType(mime_content_type($source));
              $image->setSize(filesize($source));
              $image->setOwnerId(self::FILE_OWNER);
              $image->setPermanent();
              $image->save();

              // Copy file to permanent destination.
              $dir = dirname($destination);
              if (!file_exists($dir)) {
                mkdir($dir, 0770, TRUE);
              }
              file_put_contents($destination, file_get_contents($source));
              $image->save();

              // Create media entity with saved file.
              $media = Media::create([
                'bundle' => 'image',
                'field_original_ref' => $src,
                'image' => [
                  'target_id' => $image->id(),
                  'alt' => $alt,
                  'title' => $title,
                ],
                'langcode' => 'en',
              ]);

              $media->setOwnerId(self::FILE_OWNER);
              $media->setName($filename);
              $media->save();
              $muuid = $media->uuid();
            }
          }

          if (!empty($muuid)) {
            // @TODO: You may have to tweak this replace statement to suit.
            $replace = '<drupal-entity data-align="" data-embed-button="media_browser" data-entity-embed-display="view_mode:media.wysiwyg_original" data-entity-type="media" data-entity-uuid="' . $muuid . '"></drupal-entity>';
            $image_source = $doc->saveXML($img);
            $body_val = str_ireplace($image_source, $replace, $body_val);
          }
        }
      }

      return urldecode($body_val);
    }
  }

}
