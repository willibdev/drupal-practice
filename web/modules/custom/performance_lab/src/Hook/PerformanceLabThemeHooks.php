<?php

declare(strict_types=1);

namespace Drupal\performance_lab\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;

/**
 * Theme Hook implementations for the Performance Lab module.
 */
class PerformanceLabThemeHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'performance_lab_product' => [
        'render element' => 'elements',
        'initial preprocess' => static::class . ':preprocessProduct',
      ],
      'active_products_list' => [
        'variables' => [
          'items' => NULL,
        ],
      ],
    ];
  }

  /**
   * Prepares variables for product templates.
   *
   * Default template: performance-lab-product.html.twig.
   *
   * @param array $variables
   *   An associative array containing:
   *   - elements: An associative array containing the product information
   *     and any fields attached to the entity.
   *   - attributes: HTML attributes for the containing element.
   */
  public function preprocessProduct(array &$variables): void {
    $variables['view_mode'] = $variables['elements']['#view_mode'];
    foreach (Element::children($variables['elements']) as $key) {
      $variables['content'][$key] = $variables['elements'][$key];
    }
  }

}
