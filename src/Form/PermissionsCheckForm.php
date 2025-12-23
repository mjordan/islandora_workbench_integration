<?php

namespace Drupal\islandora_workbench_integration\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\Renderer;
use Drupal\user\RoleInterface;
use Drupal\user\RoleStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to check permissions for the Islandora Workbench Integration module.
 *
 * This form allows administrators to check if a user role has the necessary
 * permissions for using the Islandora Workbench Integration module.
 */
class PermissionsCheckForm extends FormBase {

  /**
   * The role storage service.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * Permissions required for 'use islandora workbench' roles.
   *
   * This array contains permissions that are checked when the
   * 'use islandora workbench' permission is not granted.
   *
   * @var array<string, string>
   */
  private array $permissionsForNonWorkbenchRoles = [
    'administer taxonomy' => 'Administer vocabularies and terms',
    'administer site configuration' => 'Administer site configuration',
    'administer node form display' => 'Content: Administer form display',
    'administer node fields' => 'Content: Administer fields',
    'administer taxonomy_term form display' => 'Taxonomy term: Administer form display',
    'administer taxonomy_term fields' => 'Taxonomy term: Administer fields',
  ];

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected Renderer $renderer;

  /**
   * Basic constructor.
   *
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   */
  public function __construct(
    RoleStorageInterface $role_storage,
    LoggerInterface $logger,
    Renderer $renderer,
  ) {
    $this->roleStorage = $role_storage;
    $this->logger = $logger;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('user_role'),
      $container->get('logger.channel.islandora_workbench_integration'),
      $container->get('renderer'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'islandora_workbench_integration_permissions_check_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->t('Islandora Workbench Permissions Check');

    $form['message'] = [
      '#type' => 'item',
      '#markup' => $this->t('This form is used to check permissions for the Islandora Workbench Integration module.'),
    ];
    $roles = array_merge(
      ["" => '- Select a role -'],
      array_map(fn(RoleInterface $role) => Html::escape($role->label()), $this->roleStorage->loadMultiple())
    );
    $form['role'] = [
      '#type' => 'select',
      '#title' => $this->t('Role'),
      '#default_value' => '',
      '#options' => $roles,
      '#description' => $this->t('Select a role to check permissions.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Check Permissions'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_role = $form_state->getValue('role');
    if ($selected_role) {
      $role = $this->roleStorage->load($selected_role);
      if ($role instanceof RoleInterface) {
        $missing_permissions = [];
        if (!$role->hasPermission('use islandora workbench')) {
          foreach ($this->permissionsForNonWorkbenchRoles as $permission => $perm_label) {
            if (!$role->hasPermission($permission)) {
              $missing_permissions[] = $perm_label;
            }
          }
        }
        $missing_manage_members = !$role->hasPermission('manage members');
        $message = [];
        if (!empty($missing_permissions)) {
          $message[] = $this->t("The role '%role' is missing either the 'Use Islandora Workbench' permission or the following permissions:",
            ['%role' => $role->label()]);
          $list_render = [
            '#theme' => 'item_list',
            '#items' => $missing_permissions,
            '#list_type' => 'ul',
          ];
          $message[] = $this->renderer->renderRoot($list_render);
        }
        if ($missing_manage_members) {
          $message[] = $this->t("The role '%role' is also missing the 'manage members' permission.", ['%role' => $role->label()]);
        }
        if (!empty($message)) {
          $this->messenger()->addWarning(Markup::create(implode(' ', $message)));
        }
        else {
          $this->messenger()->addStatus($this->t("The role '%role' has all required permissions.", ['%role' => $role->label()]));
        }
      }
      else {
        $this->messenger()->addError($this->t('The selected role does not exist.'));
        $this->logger->error('The selected role does not exist or could not be loaded: @role', ['@role' => $selected_role]);
      }
    }
    else {
      $this->messenger()->addError($this->t('No role selected.'));
    }
  }

}
