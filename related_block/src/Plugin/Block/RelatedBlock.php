<?php
/**
 * @file
 * Contains \Drupal\related_block\Plugin\Block\CustomBlock.
 */

namespace Drupal\related_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Cache\Cache;


/**
 * Provides my custom block.
 *
 * @Block(
 *   id = "related_block",
 *   admin_label = @Translation("Related Content Block"),
 *   category = @Translation("My Blocks"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node")
 *   }
 * )
 */
class RelatedBlock extends BlockBase implements ContextAwarePluginInterface {

  // Define the number of related items to return.
  const LIMIT = 3;

  /**
   * {@inheritdoc}
   */
  public function build() {
    $nodes = $this->getRelatedNodes();

    $data = [];
    foreach ($nodes as $node) {
      $data[] = [
        'title' => $node->getTitle(),
        'id' => $node->id(),
        'node' => node_view($node, 'teaser'),
      ];
    }
    // Return the render array for the block.
    return [
      '#theme' => 'related_block',
      '#nodes' => $data,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // If this only dealt with the node provided by the context, the parent
    // class takes care of adding its cache tags. However, since this block
    // also deals related nodes, those cache contexts need to be added as well.
    $nodes = $this->getRelatedNodes();

    $tags = parent::getCacheTags();
    foreach ($nodes as $node) {
      Cache::mergeContexts($tags, $node->getCacheTags());
    }

    return $tags;
  }

  /**
   * Helper function to retrieve related nodes.
   *
   * @return \Drupal\node\NodeInterface[]
   *   An array of related nodes.
   */
  protected function getRelatedNodes() {
    // Retrieve the node context.
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->getContextValue('node');

    // Demonstrates usage of `assert()` to give better feedback during
    // development if implicit assumptions aren't valid. When `zend.assertions`
    // is set to `-1` (production setting), this code is never evaluated so
    // it doesn't impact production performance.
    assert($node->hasField('field_related_events'), 'Node of type ' . $node->getType() . ' is missing the field_related_events field.');
    assert($node->hasField('field_tags'), 'Node of type ' . $node->getType() . ' is missing the field_tags field.');

    // Fetch the node ids of the curated field list.
    $field_curated = $node->field_related_events->getValue();
    $curated_nids = array_map(function($item) {
      return $item['target_id'];
    }, $field_curated);

    // Fetch the value of the related tag field.
    $tagged_nids = [];
    $related_tag = $node->field_related_tag->getValue();
    if (isset($related_tag[0]['target_id'])) {
      $related_tag = $related_tag[0]['target_id'];

      // Fetch the ids of nodes that have a matching tag field.
      // Exclude current node from query.
      // Set "range" to desired number of matches.  Currently using 3.
      $tagged_nids = \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->condition('type', 'event')
        ->condition('field_tags.target_id', $related_tag)
        ->condition('nid', $node->id(), '<>')
        ->range(0, self::LIMIT)
        ->execute();
    }

    // Append the queried ids to the end of the curated and remove dups.
    $ids = array_unique(array_merge($curated_nids, $tagged_nids));
    // Limit to number of items desired.
    $ids = array_slice($ids, 0, self::LIMIT);

    // Now fetch the matching nodes.
    return \Drupal::entityManager()->getStorage('node')->loadMultiple($ids);
  }

}
