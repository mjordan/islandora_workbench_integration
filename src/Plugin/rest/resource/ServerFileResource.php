<?php

namespace Drupal\islandora_workbench_integration\Plugin\rest\resource;

use Drupal\file\Entity\File;
use Drupal\file\FileRepositoryInterface;
use Drupal\rest\Annotation\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rest\ResourceResponse;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Provides a REST endpoint to register a server-side file as a managed file.
 *
 * Example usage:
 * POST /api/server-file
 * Payload: { "path": "/full/path/to/file.txt", "retval": "contents" }
 *
 * Supported retval options:
 *   - "contents": Return text contents of a .txt file.
 *   - "fid": Return the file entity ID.
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
   * Constructs a ServerFileResource object.
   *
   * @param array $configuration
   *   A configuration array containing plugin instance information.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator service.
   * @param \Drupal\file\FileRepositoryInterface $file_repository
   *   The file repository service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    $logger,
    FileUrlGeneratorInterface $file_url_generator,
    FileRepositoryInterface $file_repository
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->fileUrlGenerator = $file_url_generator;
    $this->fileRepository = $file_repository;
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
      $container->get('file.repository')
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

    if (empty($data['path'])) {
      throw new BadRequestHttpException('Missing file path');
    }

    $path = $data['path'];

    if (!file_exists($path)) {
      throw new BadRequestHttpException("File does not exist at: $path");
    }

    // Ensure the file is registered as a managed file entity.
    $file = $this->fileRepository->loadByUri($path);
    if (!$file) {
      $file = File::create([
        'uri' => $path,
        'status' => 1,
      ]);
      $file->save();
    }

    $retval = $data['retval'] ?? '';

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
      $payload['fid'] = $file->id();
    }

    // File URL is generated but not currently returned.
    $url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());

    return new ResourceResponse($payload);
  }

}
