<?php

namespace Drupal\add_to_calendar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Component\Utility\Unicode;

/**
 * Builds the iCal ics file.
 *
 * Use this controller in a custom route, such as /node/123/ics.
 * It will download an ics file to the user that can be used to add the event
 * to the user's calendar without needing to generate the actual file on the
 * Drupal server.
 *
 * NOTE: Field names and details might vary.  This example comes from a site
 * with the following fields on an event node:
 *   field_event_date: a DateRange field for the event start/end date.
 *   field_all_day: a boolean checkbox for indicating an all-day event.
 *     This is optional and can usually be determined programatically from the
 *     start/end time but on the example site was used to improve UX by adding
 *     javascript to hide the date-range field when all-day was checked.
 *   field_location: a taxononmy term reference giving the conference room
 *     location of the event. This could be replaced with a text field for
 *     location, or geofield, etc.
 *   field_body: the long description of the event (normal body field).
 */
class IcsFile extends ControllerBase {

  /**
   * Creates an ics event file to add to Outlook.
   *
   * {@inheritdoc}
   */
  public function content(NodeInterface $node) {
    $response = NULL;
    $location = '';
    if ($node->hasField('field_event_date')) {
      $timeArray = self::getEventDate($node);
      $date_formatter = \Drupal::service('date.formatter');
      $format = 'Ymd\THis\Z';
      $start = $timeArray['start_date_object']->format($format);
      $end = $timeArray['end_date_object']->format($format);
      $dtstart = ':' . $start;
      $dtend = ':' . $end;
      // Format All Day Events.
      if ($timeArray['all_day'] == TRUE) {
        $start = $timeArray['start_date_object']->format('Ymd');
        // Add a day to the end day value, because it doesn't
        // extend to last day on calendar since it ends at 12am.
        $converted_end_day = strtotime('+1 day', strtotime($timeArray['end_date_object']));
        $end_date_object = DrupalDateTime::createFromTimestamp($converted_end_day);
        $end = $end_date_object->format('Ymd');
        $dtstart = ';VALUE=DATE:' . $start;
        $dtend = ';VALUE=DATE:' . $end;
      }
      // Get the event location and load the name.
      if (($node->hasField('field_location')) && (isset($node->field_location->target_id))) {
        // If Location is not a taxonomy term, replace the code below to get
        // the text value you want for the location.
        $location_term = Term::load($node->field_location->target_id);
        if ($location_term) {
          $location = $location_term->getName();
        }
      }
      $nid = $node->id();
      $title = Html::escape($node->getTitle());
      // Remove special characters for filename.
      $file_name = preg_replace('/[^A-Za-z0-9\-]/', ' ', $title);
      $description = self::getNodeSummary($node, FALSE, TRUE);
      $url = Url::fromRoute('entity.node.canonical', ['node' => $nid], ['absolute' => TRUE]);
      $url = $url->toString();
      $description .= ' ' . $url;
      $uid = 'calendar-' . $nid . $start . rand();
      $stamp = date($format);
      $created = $date_formatter->format($node->getCreatedTime(), 'custom', $format);
      $changed = $date_formatter->format($node->getChangedTime(), 'custom', $format);
      // Create the ics file array.
      $ical_array = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Date iCal v2.13//NONSGML Drupal iCalcreator 2.18//',
        'METHOD:PUBLISH',
        'BEGIN:VEVENT',
        'UID:' . $uid,
        'DTSTAMP:' . $stamp,
        'CREATED:' . $created,
        'DESCRIPTION:' . $description,
        'DTSTART' . $dtstart,
        'DTEND' . $dtend,
        'LAST-MODIFIED:' . $changed,
        'LOCATION:' . $location,
        'SUMMARY:' . $title,
        'URL;TYPE=URI:' . $url,
        'END:VEVENT',
        'END:VCALENDAR',
      ];
      $ical = implode("\r\n", $ical_array);
      if ($ical) {
        $response = new Response($ical);
        $response->headers->set('Content-Type', 'text/calendar charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $file_name . '.ics');
      }
    }

    return $response;
  }

  /**
   * Determine if Event is all_day, return the UTC DateTime objects.
   */
  public static function getEventDate($node) {
    if ($node->hasField('field_event_date') && $node->hasField('field_all_day')) {
      $field = $node->field_event_date;
      $all_day_val = $node->field_all_day->value;
      $timeArray = self::getEventDateField($field, $all_day_val);
      return $timeArray;
    }
    return $timeArray = [
      'all_day' => FALSE,
      'start_date_object' => NULL,
      'end_date_object' => NULL,
    ];
  }

  /**
   * Determine if Event is all_day, return the UTC DateTime objects.
   */
  public static function getEventDateField($field, $all_day_val = NULL) {
    if ($field) {
      // UTC node DateTime string values.
      $start_string = $field->value;
      $end_string = $field->end_value;
      if ($start_string && $end_string) {
        // Create DateTime objects.
        $start_date_object = new DrupalDateTime($start_string);
        $end_date_object = new DrupalDateTime($end_string);
        // Format to target the UTC start and end times.
        $start_time = $start_date_object->format('H:i:s');
        $end_time = $end_date_object->format('H:i:s');
        // Check if Event is all_day. True if all_day is checked, or if start =
        // end DateTime, or if start and end time are 12am UTC.
        $all_day = FALSE;
        if (($all_day_val == '1') || ($start_string == $end_string)
          || ($start_time == '00:00:00' && $end_time == '00:00:00')) {
          $all_day = TRUE;
        }
        $timeArray = [
          'all_day' => $all_day,
          'start_date_object' => $start_date_object,
          'end_date_object' => $end_date_object,
        ];
        return $timeArray;
      }
    }
    return $timeArray = [
      'all_day' => FALSE,
      'start_date_object' => NULL,
      'end_date_object' => NULL,
    ];
  }

  /**
   * Return the stripped body/summary of an entity.
   */
  public static function getNodeSummary($entity, $truncate = TRUE, $strip = FALSE, $size = NULL) {
    if (!$entity->hasField('field_body')) {
      return '';
    }

    $summary = $entity->get('field_body')->summary;
    if (empty($summary)) {
      $summary = $entity->get('field_body')->value;
    }

    // Strip tags and nbsp from body before truncating.
    if ($strip) {
      // Ensure there are spaces after each paragraph, such as in tables.
      $summary = str_replace('</p>', '</p> ', $summary);
      // Strip the HTML tags.
      $summary = strip_tags($summary);
      // Replace any non-breaking spaces with real spaces.
      $summary = trim(str_replace(["&nbsp;", "\xA0", "\xC2", "\n"], ' ', $summary));
      // Remove multiple spaces before truncating.
      $summary = preg_replace('!\s+!', ' ', $summary);;
    }
    // Need to decode html entities back to a plain string.
    // Twig will auto-escape these later.
    $summary = html_entity_decode($summary, ENT_QUOTES, 'UTF-8');

    // Truncate to desired length at a space boundary.
    if ($truncate) {
      $summary = Unicode::truncate($summary, $size, TRUE);
    }

    return $summary;
  }

}
