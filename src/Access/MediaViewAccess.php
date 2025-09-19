<?php

namespace Drupal\islandora_workbench_integration\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\media\MediaInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Check access to media types.
 *
 * Allows access with either 'administer media types' or
 * 'use islandora workbench' permissions.
 */
class MediaViewAccess implements AccessInterface {
  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * Basic constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * Checks access to media types.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check access for.
   * @param \Symfony\Component\HttpFoundation\Request|null $request
   *   The current request if available.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access result.
   */
  public function access(AccountInterface $account, ?Request $request = NULL) {
    if ($request === NULL) {
      // If no request is provided, we cannot determine access.
      return AccessResult::neutral();
    }
    $media = $request->attributes->get('media');

    if (!$media) {
      // If no media attribute is provided, we cannot determine access.
      return AccessResult::neutral();
    }

    if (!$media instanceof MediaInterface) {
      // Not a valid media object, so we cannot determine access.
      return AccessResult::neutral();
    }

    if ($account->hasPermission('administer media types') ||
        $account->hasPermission('use islandora workbench')) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
