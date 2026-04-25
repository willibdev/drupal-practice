<?php

namespace Drupal\performance_lab;

/**
 * Interface for product storage.
 */
interface ProductStorageInterface {

  /**
   * Get active products.
   *
   * @return ProductInterface[]
   *   Collection of active products.
   */
  public function getActiveProducts(): array;

}
