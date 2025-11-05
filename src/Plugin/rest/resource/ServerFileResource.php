<?php

namespace Drupal\islandora_workbench_integration\Plugin\rest\resource;

use Drupal\file\Entity\File;
use Drupal\file\FileRepositoryInterface;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rest\ResourceResponse;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a REST endpoint to register a server-side file as a managed file.
 *
 * Example usage:
 * POST /api/server-file
 * Payload: { "path": "/path/to/file.txt", "retval": "contents", "checkfile }
 *
 * Supported retval options:
 *   - "contents": Return text contents of a .txt file.
 *   - "fid": Return the file entity ID.
 *   - "checkfile": Returns whether file is found,
 *
 * This resource ensures the file is tracked by Drupal as a managed file,
 * and can optionally return file contents for .txt files.
 *
 * @RestResource(
 *   id = "server_file_resource",
 *   label = @Translation("Server File Resource"),
 *   uri_paths = {
 *     "create" = "/api/server-file"
 *   }
 * )
 */
class ServerFileResource extends ResourceBase {

  /**
   * The file URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * The file repository service.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected FileRepositoryInterface $fileRepository;

  /**
   * The account user interface.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a ServerFileResource object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    $logger,
    FileUrlGeneratorInterface $file_url_generator,
    FileRepositoryInterface $file_repository,
    AccountInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->fileUrlGenerator = $file_url_generator;
    $this->fileRepository = $file_repository;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('server_file_rest'),
      $container->get('file_url_generator'),
      $container->get('file.repository'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function permissions() {
    // Define custom permissions if needed.
    return [];
  }

  /**
   * Handles POST requests to register or access a file.
   *
   * @param array $data
   *   An associative array containing:
   *   - path: Absolute file system path to the file.
   *   - retval: Optional string, either "fid" or "contents".
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing requested file metadata or contents.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   If the path is missing or file does not exist.
   * @throws \RuntimeException
   *   If file contents cannot be read.
   */
  public function post($data) {
    $payload = [];
    $retval = $data['retval'] ?? '';
    if (empty($data['path'])) {
      throw new BadRequestHttpException('Missing file path');
    }
    $path = $data['path'];
    $file_exists = file_exists($path);
    if (!$file_exists) {
      throw new BadRequestHttpException("File does not exist at: $path");
    }
    $managed_file = $this->fileRepository->loadByUri($path);
    $media_exists = $managed_file && $this->mediaExists($managed_file->id());

    if ($media_exists) {
      throw new BadRequestHttpException("Media with this file already exists.");
    }

    // Returns file validity.
    if ($retval === 'checkfile') {
      $payload['existence'] = 'True';
      return new ResourceResponse($payload);
    }

    if (!$managed_file) {
      $managed_file = File::create([
        'uri' => $path,
        'status' => 1,
        'uid' => $this->currentUser->id(),
      ]);
      $managed_file->save();
    }

    // Return contents of a .txt file if requested.
    if ($retval === 'contents') {
      $extension = pathinfo($path, PATHINFO_EXTENSION);
      if (strtolower($extension) === 'txt') {
        $contents = file_get_contents($path);
        if ($contents === FALSE) {
          throw new \RuntimeException("Unable to read file contents.");
        }
        $payload['contents'] = $contents;
      }
    }

    // Return file ID if requested.
    if ($retval === 'fid') {
      $payload['fid'] = $managed_file->id();
    }

    return new ResourceResponse($payload);
  }

  /**
   * Checks whether any media entities reference the given file ID.
   *
   * @param int $fid
   *   The file ID to check.
   *
   * @return int[]
   *   An array of media entity IDs referencing the file.
   */
  public function mediaExists($fid): array {
    $referencing_media_ids = [];
    $media_bundles = $this->entityTypeManager
      ->getStorage('media_type')
      ->loadMultiple();

    foreach ($media_bundles as $bundle_id => $bundle) {
      $field_definitions = $this->entityFieldManager
        ->getFieldDefinitions('media', $bundle_id);

      foreach ($field_definitions as $field_name => $field_definition) {
        if ($field_definition->getSetting('target_type') === 'file') {
          $query = $this->entityTypeManager
            ->getStorage('media')
            ->getQuery()
            ->accessCheck(TRUE)
            ->condition('bundle', $bundle_id)
            ->condition("$field_name.target_id", $fid);

          $media_ids = $query->execute();

          if (!empty($media_ids)) {
            $referencing_media_ids = array_merge($referencing_media_ids, $media_ids);
          }
        }
      }
    }

    return array_unique($referencing_media_ids);
  }

}
