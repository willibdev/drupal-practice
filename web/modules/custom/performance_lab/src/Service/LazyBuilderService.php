<?php

declare(strict_types=1);

namespace Drupal\performance_lab\Service;

use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Service for lazy builder content.
 */
class LazyBuilderService {

  /**
   * Defines an account interface which represents the current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Constructor for the Lazy Builder Service.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Defines an account interface which represents the current user.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * Create the Lazy Builder block markup.
   */
  #[TrustedCallback]
  public function buildUserData(): array {
    $userName = $this->currentUser->getDisplayName();
    $currentTime = date('H:i:s');

    return [
      '#markup' => "Hello $userName, the current time is $currentTime",
      '#cache' => [
        'contexts' => ['user'],
        'max-age' => 60,
      ],
    ];
  }

}
