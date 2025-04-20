<?php

namespace Drupal\islandora_workbench_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller.
 */
class IslandoraWorkbenchIntegrationGetHashController extends ControllerBase {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a GetHash controller.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager) {
    $this->request = $request_stack->getCurrentRequest();
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('request_stack'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Gets the checksum of the file identified by the UUID.
   *
   * This endpoint uses two query parameters, 'file_uuid' and
   * 'algorithm', to get a file entity's checksum. 'algorithm'
   * is one of 'md5', 'sha1', or 'sha256'.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response with the keys below.
   */
  public function main(): JsonResponse {
    $file_uuid = $this->request->query->get('file_uuid');
    $algorithm = $this->request->query->get('algorithm');

    if ($file_uuid === NULL || $algorithm === NULL) {
      return new JsonResponse([
        'error' => 'Request is missing either the "file_uuid" or "algorithm" parameter.',
      ]);
    }

    if (!in_array($algorithm, ['md5', 'sha1', 'sha256'], TRUE)) {
      return new JsonResponse([
        'error' => '"algorithm" parameter must be one of "md5", "sha1", or "sha256".',
      ]);
    }

    $files = $this->entityTypeManager
      ->getStorage('file')
      ->loadByProperties(['uuid' => $file_uuid]);

    $file = reset($files);
    if (!$file) {
      return new JsonResponse([
        'error' => sprintf('No file found with UUID %s.', $file_uuid),
      ]);
    }

    $checksum = hash_file($algorithm, $file->getFileUri());
    $response = [
      [
        'checksum' => $checksum,
        'file_uuid' => $file->uuid(),
        'algorithm' => $algorithm,
      ],
    ];

    return new JsonResponse($response);
  }

}
