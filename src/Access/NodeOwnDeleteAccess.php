<?php

namespace Drupal\islandora_workbench_integration\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\NodeInterface;

/**
 * Allows delete with 'delete any' or 'delete own' permissions for bundles.
 */
class NodeOwnDeleteAccess implements AccessInterface {

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

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
   * Checks access for node deletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Request $request, AccountInterface $account) {
    $this->logger->info('Checking access for node deletion by user: @user', [
      '@user' => $account->getAccountName(),
    ]);
    $node = $request->attributes->get('node');

    if (!$node instanceof NodeInterface) {
      return AccessResult::neutral();
    }

    // Check for 'delete any' permission first.
    if ($account->hasPermission("delete any {$node->bundle()} content")) {
      return AccessResult::allowed();
    }

    // Check for 'delete own' permission and ownership.
    if (
      $account->hasPermission("delete own {$node->bundle()} content") &&
      $account->id() == $node->getOwnerId()
    ) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden("Permission delete any|own {$node->bundle()} content is required to delete this node.");
  }

}
