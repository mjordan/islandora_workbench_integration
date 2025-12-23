<?php

namespace Drupal\islandora_workbench_integration\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alter routes for the Islandora Workbench Integration module.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritDoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('rest.entity.node.DELETE')) {
      // Remove default access check for node deletion and add our check.
      $route->setRequirements([
        '_access_node_own_delete' => 'TRUE',
        '_access' => 'TRUE',
        '_csrf_request_header_token' => 'TRUE',
        '_format' => 'json',
      ]);
    }
    if ($route = $collection->get('rest.entity.media.GET')) {
      // Remove default access check for media GET and add our check.
      $route->setRequirements([
        '_access_media_view' => 'TRUE',
        '_access' => 'TRUE',
        '_csrf_request_header_token' => 'TRUE',
        '_format' => 'jsonld|json',
      ]);
    }
  }

}
