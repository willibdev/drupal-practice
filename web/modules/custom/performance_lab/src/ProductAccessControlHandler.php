<?php

declare(strict_types=1);

namespace Drupal\performance_lab;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the product entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class ProductAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view performance_lab_product'),
      'update' => AccessResult::allowedIfHasPermission($account, 'edit performance_lab_product'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'delete performance_lab_product'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create performance_lab_product', 'administer performance_lab_product'], 'OR');
  }

}
