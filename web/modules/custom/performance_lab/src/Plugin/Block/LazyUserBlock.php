<?php

namespace Drupal\performance_lab\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Custom block for testing lazy builder.
 */
#[Block(
  id: 'lazy_user_block',
  admin_label: new TranslatableMarkup('Lazy User Block')
)]
class LazyUserBlock extends BlockBase {

  /**
   * {@inheritDoc}
   */
  public function build(): array {
    return [
      '#lazy_builder' => [
        'performance_lab.lazy_builder:buildUserData',
        [],
      ],
      '#create_placeholder' => TRUE,
    ];
  }

}
