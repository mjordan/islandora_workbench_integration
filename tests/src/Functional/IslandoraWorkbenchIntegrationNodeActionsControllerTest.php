<?php

namespace Drupal\Tests\islandora_workbench_integration\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Functional tests for IslandoraWorkbenchIntegrationNodeActionsController.
 *
 * @group islandora_workbench_integration
 */
class IslandoraWorkbenchIntegrationNodeActionsControllerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field',
    'text',
    'system',
    'user',
    'islandora_workbench_integration',
  ];

  /**
   * Test user with "use islandora workbench" permission.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $testUser;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    Role::create([
      'id' => 'workbench_user',
      'label' => 'Workbench User',
    ])->grantPermission('use islandora workbench')->save();

    $this->testUser = User::create([
      'name' => 'test_user',
      'mail' => 'testuser@example.com',
      'password' => 'test_password',
      'status' => 1,
    ]);
    $this->testUser->addRole('workbench_user')->save();
  }

  /**
   * Data provider that returns an array of arguments for testing.
   *
   * @return array
   *   An array of arrays, each containing arguments for the tests.
   */
  public function userProvider() {
    return [
      ['root'],
      ['test_user'],
    ];
  }

  /**
   * Method to log in as a specific user.
   *
   * Dataprovider is checked statically, but we need Drupal up to create the
   * user. So we defer resolving the user to a method that can be called
   * after Drupal is set up.
   *
   * @param string $username
   *   The username to log in with.
   */
  private function customLogin(string $username): void {
    if ($username === 'test_user') {
      $this->drupalLogin($this->testUser);
    }
    else {
      $this->drupalLogin($this->rootUser);
    }
  }

  /**
   * Tests the entity form display route for success.
   *
   * @dataProvider userProvider
   */
  public function testEntityFormDisplayRouteSuccess(string $user): void {
    // Create test types.
    NodeType::create([
      'type' => 'test_bundle',
      'name' => 'Test Bundle',
    ])->save();

    // Create field storage config.
    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();

    // Create field config instance on the bundle.
    FieldConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'node',
      'bundle' => 'test_bundle',
      'label' => 'Test Field',
    ])->save();

    // Create an entity form display with a component for the field.
    EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'test_bundle',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('field_test', [
      'type' => 'string_textfield',
      'weight' => 0,
    ])->save();

    $this->customLogin($user);
    $this->drupalGet('/islandora_workbench_integration/node_actions/entity_display/node/test_bundle');
    $this->assertSession()->statusCodeEquals(200);

    $content = json_decode($this->getSession()->getPage()->getContent(), TRUE);

    $this->assertArrayNotHasKey('content', $content);
    $this->assertArrayNotHasKey('_core', $content);
    $this->assertArrayNotHasKey('uuid', $content);
    $this->assertArrayNotHasKey('third_party_settings', $content);
    $this->assertArrayHasKey('dependencies', $content);
    $this->assertArrayHasKey('config', $content['dependencies']);
    $this->assertContains('field.field.node.test_bundle.field_test', $content['dependencies']['config']);
    $this->assertArrayHasKey('id', $content);
    $this->assertEquals('node.test_bundle.default', $content['id']);
  }

  /**
   * Tests the entity form display route for an invalid bundle.
   *
   * @dataProvider userProvider
   */
  public function testEntityFormDisplayRouteInvalidBundle(string $user): void {

    $this->customLogin($user);
    $this->drupalGet('/islandora_workbench_integration/node_actions/entity_display/node/invalid_bundle');
    $this->assertSession()->statusCodeEquals(404);

    $content = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertArrayHasKey('error', $content);
    $this->assertEquals('Bundle does not exist for the given entity type.', $content['error']);
  }

  /**
   * Tests the entity form display route for no form display.
   *
   * @dataProvider userProvider
   */
  public function testEntityFormDisplayRouteNoFieldConfig(string $user): void {
    NodeType::create([
      'type' => 'test_other_bundle',
      'name' => 'Test Other Bundle',
    ])->save();

    $this->customLogin($user);
    $this->drupalGet('/islandora_workbench_integration/node_actions/entity_display/node/test_other_bundle');
    $this->assertSession()->statusCodeEquals(404);

    $content = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertArrayHasKey('error', $content);
    $this->assertEquals('Entity form display for the given type and bundle does not exist.', $content['error']);
  }

  /**
   * Tests the field config route for success.
   *
   * @dataProvider userProvider
   */
  public function testFieldConfigRouteSuccess(string $user): void {
    // Create test types.
    NodeType::create([
      'type' => 'test_bundle',
      'name' => 'Test Bundle',
    ])->save();
    // Create field storage config.
    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();
    // Create field config instance on the bundle.
    FieldConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'node',
      'bundle' => 'test_bundle',
      'label' => 'Test Field',
    ])->save();

    $this->customLogin($user);
    $this->drupalGet('/islandora_workbench_integration/node_actions/field_config/node/test_bundle/field_test');
    $this->assertSession()->statusCodeEquals(200);

    $content = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertArrayHasKey('label', $content);
    $this->assertEquals('Test Field', $content['label']);
  }

  /**
   * Tests the field config route for an invalid bundle.
   *
   * @dataProvider userProvider
   */
  public function testFieldConfigRouteInvalidBundle(string $user): void {
    // Create test types.
    NodeType::create([
      'type' => 'test_bundle',
      'name' => 'Test Bundle',
    ])->save();
    // Create field storage config.
    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();
    // Create field config instance on the bundle.
    FieldConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'node',
      'bundle' => 'test_bundle',
      'label' => 'Test Field',
    ])->save();

    $this->customLogin($user);
    $this->drupalGet('/islandora_workbench_integration/node_actions/field_config/node/invalid_bundle/field_test');
    $this->assertSession()->statusCodeEquals(404);

    $content = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertArrayHasKey('error', $content);
    $this->assertEquals('Bundle does not exist for the given entity type.', $content['error']);
  }

  /**
   * Tests the field config route for an invalid field.
   *
   * @dataProvider userProvider
   */
  public function testFieldConfigRouteInvalidField(string $user): void {
    // Create test types.
    NodeType::create([
      'type' => 'test_bundle',
      'name' => 'Test Bundle',
    ])->save();
    $this->customLogin($user);
    $this->drupalGet('/islandora_workbench_integration/node_actions/field_config/node/test_bundle/invalid_field');
    $this->assertSession()->statusCodeEquals(404);

    $content = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertArrayHasKey('error', $content);
    $this->assertEquals('Field configuration not found.', $content['error']);
  }

  /**
   * Tests the field storage config route for success.
   *
   * @dataProvider userProvider
   */
  public function testFieldStorageConfigRouteSuccess(string $user): void {
    // Create field storage config.
    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();

    $this->customLogin($user);
    $this->drupalGet('/islandora_workbench_integration/node_actions/field_storage_config/node/field_test');
    $this->assertSession()->statusCodeEquals(200);

    $content = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertArrayHasKey('type', $content);
    $this->assertEquals('string', $content['type']);
  }

  /**
   * Tests the field storage config route for an invalid field.
   *
   * @dataProvider userProvider
   */
  public function testFieldStorageConfigRouteInvalidField(string $user): void {
    $this->customLogin($user);
    $this->drupalGet('/islandora_workbench_integration/node_actions/field_storage_config/node/invalid_field');
    $this->assertSession()->statusCodeEquals(404);

    $content = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertArrayHasKey('error', $content);
    $this->assertEquals('Field storage configuration not found.', $content['error']);
  }

}
