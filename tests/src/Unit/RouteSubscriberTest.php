<?php

namespace Drupal\Tests\islandora_workbench_integration\Unit;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\islandora_workbench_integration\Routing\RouteSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests of the route subscriber for Islandora Workbench Integration.
 *
 * @group islandora_workbench_integration
 */
class RouteSubscriberTest extends UnitTestCase {

  /**
   * The route subscriber instance.
   *
   * @var \Drupal\islandora_workbench_integration\Routing\RouteSubscriber
   */
  protected RouteSubscriberBase $routeSubscriber;

  /**
   * Sets up the test environment.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->routeSubscriber = new RouteSubscriber();
  }

  /**
   * Tests the route subscriber for Islandora Workbench Integration.
   */
  public function testRouteSubscriberNoChange() {
    $collection = new RouteCollection();
    $route = new Route("/some/route", [], ['_permission' => 'access route']);
    $collection->add('rest.some.route', $route);
    $routeBuildEvent = new RouteBuildEvent($collection);

    $this->routeSubscriber->onAlterRoutes($routeBuildEvent);

    $alteredRoute = $collection->get('rest.some.route');
    $this->assertNotNull($alteredRoute, 'The route should still exist after alteration.');
    $this->assertEquals('/some/route', $alteredRoute->getPath(),
      'The route path should remain unchanged after alteration.');
    $this->assertEquals(['_permission' => 'access route'], $alteredRoute->getRequirements(),
      'The route requirements should remain unchanged after alteration.');
  }

  /**
   * Tests the alteration of the node delete route.
   */
  public function testRouteAlterNodeDelete() {
    $collection = new RouteCollection();
    $route = new Route("/node/{node}/delete", [], ['_permission' => 'access content']);
    $collection->add('rest.entity.node.DELETE', $route);
    $routeBuildEvent = new RouteBuildEvent($collection);

    $this->routeSubscriber->onAlterRoutes($routeBuildEvent);

    $alteredRoute = $collection->get('rest.entity.node.DELETE');
    $this->assertNotNull($alteredRoute, 'The node delete route should exist after alteration.');
    $this->assertEquals([
      '_access_node_own_delete' => 'TRUE',
      '_access' => 'TRUE',
      '_csrf_request_header_token' => 'TRUE',
      '_format' => 'json',
    ], $alteredRoute->getRequirements(), 'The node delete route requirements should be altered correctly.');
  }

  /**
   * Tests the alteration of the media GET route.
   */
  public function testRouteAlterMediaGet() {
    $collection = new RouteCollection();
    $route = new Route("/media/{media}", [], ['_permission' => 'access content']);
    $collection->add('rest.entity.media.GET', $route);
    $routeBuildEvent = new RouteBuildEvent($collection);

    $this->routeSubscriber->onAlterRoutes($routeBuildEvent);

    $alteredRoute = $collection->get('rest.entity.media.GET');
    $this->assertNotNull($alteredRoute, 'The media GET route should exist after alteration.');
    $this->assertEquals([
      '_access_media_view' => 'TRUE',
      '_access' => 'TRUE',
      '_csrf_request_header_token' => 'TRUE',
      '_format' => 'jsonld|json',
    ], $alteredRoute->getRequirements(), 'The media GET route requirements should be altered correctly.');
  }

}
