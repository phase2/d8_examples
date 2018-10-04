<?php

namespace Drupal\example_computed_field\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\user\Entity\Node;

/**
 * A handler to provide proper displays for Display Date computed field.
 *
 * From https://www.drupal.org/docs/8/api/entity-api/dynamicvirtual-field-values-using-computed-field-property-classes.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_display_date")
 */
class NodeDisplayDate extends FieldPluginBase {

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.LongVariable)
   */
  public function render(ResultRow $values) {
    // Check if View has a relationship for the node.
    $relationship_entities = $values->_relationship_entities;
    $display_date = '';
    // First check the referenced entity.
    if (isset($relationship_entities['node'])) {
      $node = $relationship_entities['node'];
    }
    else {
      $node = $values->_entity;
    }

    // Check if node has the display date computed field.
    // If not, just return the node Changed time.
    if ($node instanceof Node) {
      $display_date = $node->hasField('node_display_date')
        ? $node->get('node_display_date')->value
        : $node->getChangedTime();
    }

    return $display_date;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // This function exists to override parent query function.
    // Do nothing.
  }

}
