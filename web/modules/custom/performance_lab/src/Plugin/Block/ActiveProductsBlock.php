<?php

namespace Drupal\performance_lab\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create block to show up the active products by category.
 */
#[Block(
  id: 'active_products_block',
  admin_label: new TranslatableMarkup('Active Products Block'),
  category: new TranslatableMarkup('Performance Lab')
)]
class ActiveProductsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor for the ActiveProductsBlock Class.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function build(): array {
    /** @var \Drupal\performance_lab\ProductStorageInterface $product_storage */
    $product_storage = $this->entityTypeManager->getStorage('performance_lab_product');
    /** @var \Drupal\performance_lab\Entity\Product[] $active_products */
    $active_products = $product_storage->getActiveProducts();

    $items = [];
    foreach ($active_products as $product) {
      /** @var \Drupal\taxonomy\TermInterface */
      $category = $product->get('field_category')->entity;

      $items[] = [
        'title' => $product->label(),
        'price' => $product->get('price')->value,
        'description' => $product->get('description')->value,
        'status' => $product->get('status')->value,
        'category' => $category?->getName() ?? NULL,
        'uuid' => $product->uuid(),
      ];
    }

    return [
      '#theme' => 'active_products_list',
      '#title' => $this->t('Active Products'),
      '#items' => $items,
      '#cache' => [
        'tags' => ['product_list'],
        'max-age' => CacheBackendInterface::CACHE_PERMANENT,
      ],
    ];
  }

}
