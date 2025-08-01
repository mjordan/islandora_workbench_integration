<?php

namespace Drupal\islandora_workbench_integration\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\media\MediaInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Gives access to media types without requiring 'administer media types' permission.
 */
class MediaViewAccess implements AccessInterface
{
  private $logger;

  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * Checks access to media types.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access result.
   */
  public function access(Request $request, AccountInterface $account) {
    $this->logger->info('Checking access for media types by user: @user', [
      '@user' => $account->getAccountName(),
    ]);
    $media = $request->attributes->get('media');

    if (!$media) {
      // If no media attribute is provided, we cannot determine access.
      return AccessResult::neutral();
    }

    if (!$media instanceof MediaInterface) {
      // Not a valid media object, so we cannot determine access.
      return AccessResult::neutral();
    }

    // If the user has the 'administer media types' or 'use islandora workbench' permission, they can access.
    if ($account->hasPermission('administer media types') ||
        $account->hasPermission('use islandora workbench')) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }
}
