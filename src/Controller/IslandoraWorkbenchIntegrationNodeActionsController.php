<?php

namespace Drupal\islandora_workbench_integration\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for proxying entity form display and field config requests.
 *
 * This controller provides an endpoint to retrieve the entity form display
 * for a given entity type and bundle, primarily used in the context of
 * Islandora Workbench Integration.
 */
class IslandoraWorkbenchIntegrationNodeActionsController extends ControllerBase
{
  /**
   * Log channel.
   *
   * @var LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  private EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * Constructs the controller.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Psr\Log\LoggerInterface $logger
   *  The logger service.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info, LoggerInterface $logger) {
    $this->logger = $logger;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * Creates an instance of the controller.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return static
   *   A new instance of the controller.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('logger.channel.islandora_workbench_integration')
    );
  }

  /**
   * Request handler for entity form display requests.
   * @param string $entity_type The entity type to load.
   * @param string $bundle The bundle of the entity type to load.
   * @return Response The response object containing the entity form display or an error message.
   */
  public function entity_form_display(string $entity_type, string $bundle): Response
  {
    $this->logger->debug("Received request on node actions controller method entity_form_display with entity type: @type, bundle: @bundle", [
      '@type' => $entity_type,
      '@bundle' => $bundle,
    ]);
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
    if (!isset($bundle_info[$bundle])) {
      $this->logger->warning("Bundle @bundle does not exist for entity type @type", [
        '@bundle' => $bundle,
        '@type' => $entity_type,
      ]);
      return new JsonResponse(['error' => 'Bundle does not exist for the given entity type.']);
    }
    try {
      $display = $this->entityTypeManager()->getStorage('entity_form_display')->load("{$entity_type}.{$bundle}.default");
      if (!$display) {
        $this->logger->warning("Entity form display for @type bundle @bundle does not exist", [
          '@type' => $entity_type,
          '@bundle' => $bundle,
        ]);
        return new JsonResponse(['error' => 'Entity form display for the given type and bundle does not exist.']);
      }
      $response = $display->toArray();
      return new JsonResponse($response);
    } catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $this->logger->warning("Error loading entity form display for @type bundle @bundle: @message", [
        '@type' => $entity_type,
        '@bundle' => $bundle,
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse(['error' => 'Invalid entity type or bundle specified.']);
    }
  }
}
