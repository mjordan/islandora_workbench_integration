<?php

namespace Drupal\islandora_workbench_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
* Controller.
*/
class IslandoraWorkbenchIntegrationCoreVersionController extends ControllerBase {
  /**
   * @return JsonResponse object
   */
   public function main() {
     return new JsonResponse(['core_version' => \Drupal::VERSION]);
   }

}

