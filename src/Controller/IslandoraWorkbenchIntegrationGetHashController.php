<?php

namespace Drupal\islandora_workbench_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller.
 */
class IslandoraWorkbenchIntegrationGetHashController extends ControllerBase {

  /**
   * Gets the checksum of the file identified by the UUID.
   *
   * This endpoint uses two query parameters, 'file_uuid' and
   * 'algorithm', to get a file entity's checksum. 'algorithm'
   * is one of 'md5', 'sha1', or 'sha256'.
   *
   * @return JsonResponse
   *   A JSON response with the keys below. 
   */
  public function main() {
    $file_uuid = \Drupal::request()->query->get('file_uuid');
    $algorithm = \Drupal::request()->query->get('algorithm');

    if (is_null($file_uuid) || is_null($algorithm)) {
      return new JsonResponse(['error' => 'Request is missing either the "file_uuid" or "algorithm" parameter.']);
    }
    if (!in_array($algorithm, ['md5', 'sha1', 'sha256'])) {
      return new JsonResponse(['error' => '"algorithm" parameter must be one of "md5", "sha1", or "sha256".']);
    }

    $file = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uuid' => $file_uuid]);
    $file = reset($file);
    $checksum = hash_file($algorithm, $file->getFileUri());
    $response[] = [
      'checksum' => $checksum,
      'file_uuid' => $file->uuid(),
      'algorithm' => $algorithm,
    ];

    return new JsonResponse($response);
  }

}
