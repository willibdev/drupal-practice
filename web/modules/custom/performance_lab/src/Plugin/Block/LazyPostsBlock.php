<?php

namespace Drupal\performance_lab\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Lazy builder block for bigpipe testing.
 */
#[Block(
  id: 'lazy_posts_block',
  admin_label: new TranslatableMarkup('Lazy Posts Block')
)]
class LazyPostsBlock extends BlockBase {

  /**
   * {@inheritDoc}
   */
  public function build(): array {
    return [
      '#lazy_builder' => [
        'performance_lab.jsonplaceholder:buildPosts',
        [],
      ],
      '#create_placeholder' => TRUE,
    ];
  }

}
