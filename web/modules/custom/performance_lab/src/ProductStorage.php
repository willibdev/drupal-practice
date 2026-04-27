<?php

declare(strict_types=1);

namespace Drupal\performance_lab;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannel;

/**
 * ProductStorage class to handle storage operations for Product entities.
 */
class ProductStorage extends SqlContentEntityStorage implements ProductStorageInterface {

  /**
   * The cache backend used for caching product data.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $defaultCache;

  /**
   * Defines required methods for classes wanting to handle cache tag changes.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Logger channel factory interface.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $loggerFactory;

  /**
   * Constructs a ProductStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   Provides an interface for an entity type and its metadata.
   * @param \Drupal\Core\Database\Connection $database
   *   Base Database API class.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Provides an interface for an entity field manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Defines an interface for cache implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $default_cache
   *   Defines an interface for cache implementations.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   Defines required methods for classes wanting to handle cache tag changes.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Common interface for the language manager service.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   Defines an interface for memory cache implementations.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   Provides an interface for an entity type bundle info.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Provides an interface for entity type managers.
   * @param \Drupal\Core\Logger\LoggerChannel $logger_factory
   *   Provides an interface for entity type managers.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    Connection $database,
    EntityFieldManagerInterface $entity_field_manager,
    CacheBackendInterface $cache,
    CacheBackendInterface $default_cache,
    CacheTagsInvalidatorInterface $cache_tags_invalidator,
    LanguageManagerInterface $language_manager,
    MemoryCacheInterface $memory_cache,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannel $logger_factory,
  ) {
    parent::__construct($entity_type, $database, $entity_field_manager, $cache, $language_manager, $memory_cache, $entity_type_bundle_info, $entity_type_manager);
    $this->defaultCache = $default_cache;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritDoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity_field.manager'),
      $container->get('cache.entity'),
      $container->get('cache.default'),
      $container->get('cache_tags.invalidator'),
      $container->get('language_manager'),
      $container->get('entity.memory_cache'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('logger.channel.performance_lab'),
    );
  }

  /**
   * Get a collection of active products.
   *
   * @return ProductInterface[]
   *   Collection of active products.
   */
  public function getActiveProducts(): array {
    if ($cache = $this->defaultCache->get('active_products')) {
      return $cache->data;
    }

    $queryIds = $this->database->select($this->getDataTable())
      ->fields($this->getDataTable(), ['id'])
      ->condition('status', 1)
      ->execute()->fetchAllAssoc('id');

    $products = $this->loadMultiple(array_keys($queryIds));

    $this->defaultCache->set('active_products', $products, CacheBackendInterface::CACHE_PERMANENT, ['product_list']);

    return $products;
  }

  /**
   * Get a collection of productos within a specified price range.
   *
   * @param float $min_price
   *   Minimun price of the products to retrieve.
   * @param float $max_price
   *   Maximum price of the products to retrieve.
   *
   * @return ProductInterface[]
   *   Collection of products within the specified price range.
   */
  public function getProductsByPriceRange(float $min_price, float $max_price): ProductInterface {
    /** @var ProductInterface */
    $query = $this->database->select($this->getBaseTable())
      ->condition('price', [$min_price, $max_price], 'BETWEEN')
      ->execute()->fetchAll();

    return $query;
  }

  /**
   * {@inheritDoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    $this->loggerFactory->info('Saving product with ID: @id', ['@id' => $id]);
    $this->cacheTagsInvalidator->invalidateTags(['product_list']);
    return parent::doSave($id, $entity);
  }

}
