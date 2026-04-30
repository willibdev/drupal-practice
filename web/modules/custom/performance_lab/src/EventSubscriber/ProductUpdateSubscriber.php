<?php

namespace Drupal\performance_lab\EventSubscriber;

use Drupal\Core\Entity\EntityTypeEvent;
use Drupal\Core\Entity\EntityTypeEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Eventsubscriber for product entity updates.
 */
class ProductUpdateSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      EntityTypeEvents::UPDATE => 'onEntityTypeUpdate',
    ];
  }

  /**
   * Do mething when a product entity has been updated.
   */
  public function onEntityTypeUpdate(EntityTypeEvent $event) {
    // Do something after updating an entity.
  }

}
