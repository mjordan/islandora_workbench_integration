<?php

namespace Drupal\islandora_workbench_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller.
 */
class IslandoraWorkbenchIntegrationCoreVersionController extends ControllerBase {

  /**
   * Return a JSON object specifying this site's Drupal version.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   object
   */
  public function main() {
    return new JsonResponse(['core_version' => \Drupal::VERSION]);
  }

}
