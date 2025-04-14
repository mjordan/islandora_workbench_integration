<?php

namespace Drupal\islandora_workbench_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller.
 */
class IslandoraWorkbenchIntegrationVersionController extends ControllerBase {

  /**
   * Return a JSON object specifying this module's version.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   object
   */
  public function main() {
    return new JsonResponse(['integration_module_version' => '1.0.0']);
  }

}
