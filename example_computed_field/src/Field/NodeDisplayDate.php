<?php

namespace Drupal\example_computed_field\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\user\Entity\Node;

/**
 * Represents a user full name field.
 */
class NodeDisplayDate extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    // Calculate the value field here.
    $timestamp = NULL;

    // Fetch the node the computed field is defined on.
    $entity = $this->getEntity();
    // Check if the node has an additional "field_display_date" field where
    // the content author can override the display date.
    if ($entity instanceof Node && $entity->hasField('field_display_date')) {
      $display_date = $entity->get('field_display_date')->value;
      // If the field has a value, convert it to a timestamp.
      if (isset($display_date)) {
        $timestamp = strtotime($display_date);
      }
      else {
        // If no display date field was given, use the changed time of the node.
        $timestamp = $entity->getChangedTime();
      }
    }

    // This computed field returns a singular item value.
    $this->list[0] = $this->createItem(0, $timestamp);
  }

}
