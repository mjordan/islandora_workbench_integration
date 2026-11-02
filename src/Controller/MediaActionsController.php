<?php

namespace Drupal\islandora_workbench_integration\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller providing REST endpoints for media type actions.
 */
class MediaActionsController extends ControllerBase {

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
    );
  }

  /**
   * Returns cacheable media type response.
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
