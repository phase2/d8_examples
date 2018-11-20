<?php

namespace Drupal\Tests\related_block\Kernel\Plugin\Block;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Kernel tests for the related block.
 *
 * @group related_block
 *
 * @coversDefaultClass \Drupal\related_block\Plugin\Block\RelatedBlock
 */
class RelatedBlockTest extends KernelTestBase {

  use BlockCreationTrait;
  use NodeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var string[]
   *
   * In Kernel tests, all dependencies must be manually enabled. This keeps the
   * tests fast as modules unnecessary to the tests aren't enabled.
   */
  public static $modules = [
    'related_block',
    'block',
    'node',
    'field',
    'filter',
    'system',
    'user',
  ];

  /**
   * The block.
   *
   * @var \Drupal\block\Entity\Block
   */
  protected $block;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->block = $this->placeBlock('related_block');

    // In Kernel tests, configs aren't automatically installed, and must be
    // done on a per-module basis.
    $this->installConfig(['filter']);

    // Similarly, only needed database tables are created.
    // In this case, the user entity relies on the system module's 'sequences'
    // table.
    $this->installSchema('system', ['sequences']);

    // Entity schemas aren't automatically enabled. They are only needed if
    // entities will be saved during the test.
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
  }

  /**
   * @covers ::build
   */
  public function testBuild() {
    // Fake the node context.
    $node = $this->createNode();
    $context = EntityContext::fromEntity($node);
    $plugin = $this->block->getPlugin();
    $plugin->setContext('node', $context);

    // Since the required fields are not in place, this build will thrown an
    // assertion error.
    $this->setExpectedException(\AssertionError::class);
    $plugin->build();
  }

  /**
   * Demonstrates testing for an exception.
   *
   * @covers ::build
   */
  public function testRequiredContext() {
    // Since the node context is required, the plugin will throw an exception
    // if it isn't present.
    $this->setExpectedException(ContextException::class);
    $this->block->getPlugin()->build();
  }

}
