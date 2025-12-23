<?php

namespace Drupal\islandora_workbench_integration\Plugin\views\access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\PermissionHandlerInterface;
use Drupal\views\Attribute\ViewsAccess;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that checks if user has any of the selected permissions.
 *
 * @ingroup views_access_plugins
 */
#[ViewsAccess(
  id: 'multiple_permissions',
  title: new TranslatableMarkup('Multiple permissions (OR)'),
  help: new TranslatableMarkup('Access will be granted to users with any of the specified permission strings.'),
)]
class MultiplePermissions extends AccessPluginBase implements
  CacheableDependencyInterface {

  use StringTranslationTrait;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * The permission handler service.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected PermissionHandlerInterface $permissionHandler;

  /**
   * Module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * Basic constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler service.
   * @param \Drupal\Core\Extension\ModuleExtensionList|\Drupal\Core\Extension\ModuleHandlerInterface $module_extension_list
   *   The module extension list.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PermissionHandlerInterface $permission_handler,
    ModuleExtensionList|ModuleHandlerInterface $module_extension_list,
    LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->permissionHandler = $permission_handler;
    if ($module_extension_list instanceof ModuleHandlerInterface) {
      @trigger_error('Calling ' . __METHOD__ . '() with the $module_extension_list argument as ModuleHandlerInterface is deprecated in drupal:10.3.0 and will be required in drupal:12.0.0. See https://www.drupal.org/node/3310017', E_USER_DEPRECATED);
      $module_extension_list = \Drupal::service('extension.list.module');
    }
    $this->moduleExtensionList = $module_extension_list;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('user.permissions'),
      $container->get('extension.list.module'),
      $container->get('logger.channel.islandora_workbench_integration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle(): string {
    $permissions = $this->permissionHandler->getPermissions();
    $permission_titles = [];
    foreach ($this->options['permissions'] as $perm) {
      if (isset($permissions[$perm])) {
        $permission_titles[] = $permissions[$perm]['title'];
      }
    }
    return implode(', ', $permission_titles ?? "No permissions selected");
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    foreach ($this->options['permissions'] as $permission) {
      if ($account->hasPermission($permission)) {
        return AccessResult::allowed()->addCacheContexts(['user.permissions']);
      }
    }
    return AccessResult::forbidden()->addCacheContexts(['user.permissions']);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['permissions'] = ['default' => []];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): array {
    parent::buildOptionsForm($form, $form_state);

    $all_permissions = $this->permissionHandler->getPermissions();
    $options = [];
    foreach ($all_permissions as $key => $definition) {
      $provider = $definition['provider'];
      $display_name = $this->moduleExtensionList->getName($provider);
      $options[$display_name][$key] = strip_tags($definition['title']);
    }

    asort($options);

    $form['permissions'] = [
      '#type' => 'select',
      '#title' => $this->t('Permissions'),
      '#description' => $this->t('Select one or more permissions. Access is granted if the user has at least one.'),
      '#multiple' => TRUE,
      '#options' => $options,
      '#size' => 10,
      '#default_value' => $this->options['permissions'],
      '#empty_option' => $this->t('Select permissions'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    // Append route-level permission check for each permission.
    if (!empty($this->options['permissions'])) {
      $route->setRequirement('_permission', implode('+', $this->options['permissions']));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user.permissions'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
