<?php

namespace Drupal\islandora_workbench_integration\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\field\Entity\FieldConfig;
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
class IslandoraWorkbenchIntegrationNodeActionsController extends ControllerBase {
  /**
   * Log channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  private EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private EntityFieldManagerInterface $entityFieldManager;

  /**
   * The field type plugin manager service.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  private FieldTypePluginManagerInterface $pluginManager;

  /**
   * Constructs the controller.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $plugin_manager
   *   The field type plugin manager service.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info, LoggerInterface $logger, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $plugin_manager) {
    $this->logger = $logger;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
    $this->pluginManager = $plugin_manager;
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
      $container->get('logger.channel.islandora_workbench_integration'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * Request handler for entity form display requests.
   *
   * @param string $entity_type
   *   The entity type to load.
   * @param string $bundle
   *   The bundle of the entity type to load.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The json response of the entity form display or an error message.
   */
  public function entityFormDisplay(string $entity_type, string $bundle): Response {
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
    if (!isset($bundle_info[$bundle])) {
      $this->logger->warning("Bundle @bundle does not exist for entity type @type", [
        '@bundle' => $bundle,
        '@type' => $entity_type,
      ]);
      return new JsonResponse(['error' => 'Bundle does not exist for the given entity type.'], 404);
    }
    try {
      $display = $this->entityTypeManager()->getStorage('entity_form_display')->load("{$entity_type}.{$bundle}.default");
      if (!$display) {
        $this->logger->warning("Entity form display for @type bundle @bundle does not exist", [
          '@type' => $entity_type,
          '@bundle' => $bundle,
        ]);
        return new JsonResponse(['error' => 'Entity form display for the given type and bundle does not exist.'], 404);
      }
      $response = $display->toArray();
      // Remove unnecessary keys from the response.
      unset($response['uuid'], $response['_core'], $response['content'], $response['third_party_settings']);
      return new JsonResponse($response);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $this->logger->warning("Error loading entity form display for @type bundle @bundle: @message", [
        '@type' => $entity_type,
        '@bundle' => $bundle,
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse(['error' => 'Invalid entity type or bundle specified.'], 500);
    }
  }

  /**
   * Request handler for field config data.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $field_name
   *   The machine name of the field.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   JSON response with field config or error.
   */
  public function fieldConfig(string $entity_type, string $bundle, string $field_name): Response {
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
    if (!isset($bundle_info[$bundle])) {
      $this->logger->warning("Bundle @bundle does not exist for entity type @type", [
        '@bundle' => $bundle,
        '@type' => $entity_type,
      ]);
      return new JsonResponse(['error' => 'Bundle does not exist for the given entity type.'], 404);
    }

    try {
      $field_config_id = "{$entity_type}.{$bundle}.{$field_name}";
      $field_config = $this->entityTypeManager()->getStorage('field_config')->load($field_config_id);

      if (!$field_config) {
        return new JsonResponse(['error' => 'Field configuration not found.'], 404);
      }

      $data = $field_config->toArray();
      // Remove unnecessary keys from the response.
      unset($data['uuid'], $data['_core']);

      return new JsonResponse($data);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $this->logger->error("Error loading field config @id: @message", [
        '@id' => "{$entity_type}.{$bundle}.{$field_name}",
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse(['error' => 'Unexpected error loading field config.'], 500);
    }
  }

  /**
   * Request handler for field storage config data.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $field_name
   *   The field machine name.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with field storage config or error.
   */
  public function fieldStorageConfig(string $entity_type, string $field_name): JsonResponse {
    try {
      $field_storage_id = "{$entity_type}.{$field_name}";
      $storage_config = $this->entityTypeManager()
        ->getStorage('field_storage_config')
        ->load($field_storage_id);

      if (!$storage_config) {
        $this->logger->warning(
          "Field storage config not found for @id", ['@id' => $field_storage_id]
        );
        return new JsonResponse(['error' => 'Field storage configuration not found.'], 404);
      }

      $data = $storage_config->toArray();
      // Remove unnecessary keys from the response.
      unset($data['uuid'], $data['_core']);
      return new JsonResponse($data);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $this->logger->error("Error loading field storage config: @message", [
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse(['error' => 'Error loading field storage configuration.'], 500);
    }
  }

  /**
   * Return all field configs and storage configs for an entity type/bundle.
   *
   * Simulates the combined output of multiple calls to the above endpoints.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle name.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with field storage config and field config or error.
   */
  public function entityFieldBundle(string $entity_type, string $bundle): JsonResponse {
    try {
      if (!$this->entityTypeManager()->hasDefinition($entity_type)) {
        $this->logger->warning("Entity type @type does not exist", [
          '@type' => $entity_type,
        ]);
        return new JsonResponse(['error' => 'Entity type does not exist.'], 404);
      }
      if (!isset($this->entityTypeBundleInfo->getBundleInfo($entity_type)[$bundle])) {
        $this->logger->warning("Bundle @bundle does not exist for entity type @type", [
          '@bundle' => $bundle,
          '@type' => $entity_type,
        ]);
        return new JsonResponse(['error' => 'Bundle does not exist for the given entity type.'], 404);
      }
      $field_definitions = $this->entityFieldManager
        ->getFieldDefinitions($entity_type, $bundle);
      $cacheable_response = new CacheableJsonResponse();

      // Add cache tags to invalidate when fields are added or deleted.
      $cacheable_response->getCacheableMetadata()->addCacheTags([
        'config:field_config_list',
        'config:field_storage_config_list',
        'entity_field_info',
        'entity_bundles',
      ]);

      $field_config_ids = [];
      $field_storage_ids = [];
      $base_fields = [];

      foreach ($field_definitions as $field_name => $definition) {
        // Check if this is a configurable field.
        if ($definition instanceof FieldConfig) {
          $field_config_ids[] = "{$entity_type}.{$bundle}.{$field_name}";
          $field_storage_ids[] = "{$entity_type}.{$field_name}";
        }
        else {
          // It is a base field.
          $base_fields[$field_name] = $definition;
        }
      }

      // This will store all the field's information.
      $field_info = [];

      if (!empty($field_config_ids)) {
        $loaded_field_configs = $this->entityTypeManager()
          ->getStorage('field_config')
          ->loadMultiple($field_config_ids);

        foreach ($loaded_field_configs as $field_config) {
          $cacheable_response->addCacheableDependency($field_config);
          $field_name = $field_config->getName();
          if (!isset($field_info[$field_name])) {
            $field_info[$field_name] = [];
          }
          $field_info[$field_name]['config'] = $this->cleanConfigData($field_config);
        }
      }

      // Load all field storage configs in one query.
      if (!empty($field_storage_ids)) {
        $loaded_storage_configs = $this->entityTypeManager()
          ->getStorage('field_storage_config')
          ->loadMultiple($field_storage_ids);

        foreach ($loaded_storage_configs as $storage_config) {
          $cacheable_response->addCacheableDependency($storage_config);
          $field_name = $storage_config->getName();
          if (!isset($field_info[$field_name])) {
            $field_info[$field_name] = [];
          }
          $field_info[$field_name]['storage_config'] = $this->cleanConfigData($storage_config);
        }
      }

      // Add base field information.
      foreach ($base_fields as $field_name => $definition) {
        $field_info[$field_name]['config'] = [
          'field_name' => $field_name,
          'entity_type' => $entity_type,
          'bundle' => $bundle,
          'label' => (string) $definition->getLabel(),
          'description' => (string) $definition->getDescription(),
          'required' => $definition->isRequired(),
          'translatable' => $definition->isTranslatable(),
          'default_value' => $definition->getDefaultValueLiteral(),
          'default_value_callback' => $definition->getDefaultValueCallback(),
          'settings' => $definition->getSettings(),
          'field_type' => $definition->getType(),
          'is_base_field' => TRUE,
        ];

        // Get storage definition for base field.
        $field_storage = $definition->getFieldStorageDefinition();
        if ($field_storage) {
          $base_definition = $this->pluginManager->getDefinition($field_storage->getType());
          $field_info[$field_name]['storage_config'] = [
            'field_name' => $field_name,
            'entity_type' => $entity_type,
            'type' => $field_storage->getType(),
            'settings' => $field_storage->getSettings(),
            'module' => $base_definition['provider'] ?? 'core',
            'cardinality' => $field_storage->getCardinality(),
            'translatable' => $field_storage->isTranslatable(),
            'locked' => TRUE,
            'custom_storage' => $field_storage->hasCustomStorage(),
            'is_base_field' => TRUE,
          ];
        }
      }

      $cacheable_response->setData($field_info);
      return $cacheable_response;
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $this->logger->error("Error loading field bundle config: @message", [
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse(['error' => 'Error loading field bundle configuration.'], 500);
    }
    catch (\Exception $e) {
      $this->logger->error("Unexpected error in entityFieldBundle: @message", [
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse(['error' => 'Unexpected error loading field configuration.'], 500);
    }
  }

  /**
   * Filter some unnecessary keys from the config data.
   *
   * @param \Drupal\Core\Entity\EntityInterface $config_data
   *   The field config or field storage config entity to clean.
   *
   * @return array
   *   The cleaned config data as an array.
   */
  private function cleanConfigData(EntityInterface $config_data): array {
    $data = $config_data->toArray();
    unset($data['uuid'], $data['_core']);
    $data['is_base_field'] = FALSE;
    return $data;
  }

}
