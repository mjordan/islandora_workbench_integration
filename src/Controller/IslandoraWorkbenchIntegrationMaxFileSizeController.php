<?php

namespace Drupal\islandora_workbench_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
* Controller.
*/
class IslandoraWorkbenchIntegrationMaxFileSizeController extends ControllerBase {
  /**
   * @return JsonResponse object
   */
   public function main() {
     $upload_max_filesize = ini_get('upload_max_filesize');
     return new JsonResponse(['upload_max_filesize' => $upload_max_filesize]);
   }

}

