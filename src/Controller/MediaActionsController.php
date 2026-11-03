<?php

namespace Drupal\islandora_workbench_integration\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Performs a check for the existence of the media type.
 *
 * @package Drupal\islandora_workbench_integration\Controller
 */
class MediaActionsController extends ControllerBase {

  /**
   * Basic constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Creates a new instance of the controller.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Returns existence of a media type as a cacheable JSON response.
   *
   * @param string $media_type
   *   The media type to check.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   The cacheable response.
   */
  public function getMediaType(string $media_type): CacheableJsonResponse {
    $storage = $this->entityTypeManager->getStorage('media_type');
    $entity = $storage->load($media_type);

    $cache_metadata = new CacheableMetadata();
    $cache_metadata->addCacheTags([
      // Invalidated whenever this media_type config entity changes.
      'config:media.type.' . $media_type,
      // Invalidated when any media type is added or removed.
      'config:media_type_list',
    ]);
    $cache_metadata->addCacheContexts(['url.query_args']);

    if (!$entity) {
      $response = new CacheableJsonResponse(sprintf('Media type "%s" does not exist.', $media_type), 404);
      $response->addCacheableDependency($cache_metadata);
      return $response;
    }

    $response = new CacheableJsonResponse();
    $response->addCacheableDependency($cache_metadata);

    return $response;
  }

}
