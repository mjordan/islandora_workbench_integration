<?php

namespace Drupal\islandora_workbench_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleExtensionList;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller.
 */
class IslandoraWorkbenchIntegrationVersionController extends ControllerBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * {@inheritdoc}
   */
  public function __construct(ModuleExtensionList $module_extention_list) {
    $this->moduleExtensionList = $module_extention_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('extension.list.module')
    );
  }

  /**
   * Return a JSON object specifying this module's version.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   object
   */
  public function main() {
    $version = $this->moduleExtensionList->getExtensionInfo('islandora_workbench_integration')['version'] ?? 0;

    return new JsonResponse(['integration_module_version' => $version]);
  }

}
