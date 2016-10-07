<?php
/**
 * @file
 * Contains \Drupal\related_block\Plugin\Block\CustomBlock.
 */

namespace Drupal\related_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Component\Plugin\ContextAwarePluginInterface;


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
    // Retrieve the node context.
    $node = $this->getContextValue('node');

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
    $nodes = \Drupal::entityManager()->getStorage('node')->loadMultiple($ids);

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
}
