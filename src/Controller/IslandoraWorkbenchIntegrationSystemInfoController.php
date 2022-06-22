<?php

namespace Drupal\islandora_workbench_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
* Controller.
*/
class IslandoraWorkbenchIntegrationSystemInfoController extends ControllerBase {
  /**
   * @return JsonResponse object
   */
   public function main() {
     $memory_limit = ini_get('memory_limit');
     $post_max_size = ini_get('post_max_size');
     $max_execution_time = ini_get('max_execution_time');
     $upload_max_filesize = ini_get('upload_max_filesize');
     return new JsonResponse([
       'memory_limit' => $memory_limit,
       'post_max_size' => $post_max_size,
       'max_execution_time' => $max_execution_time,
       'upload_max_filesize' => $upload_max_filesize,
     ]);
   }

}

