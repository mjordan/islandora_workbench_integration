<?php

namespace Drupal\Tests\islandora_workbench_integration\Functional;

use Drupal\media\Entity\MediaType;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Functional tests for MediaActionsController.
 *
 * @group islandora_workbench_integration
 */
class MediaActionsControllerTest extends BrowserTestBase {
  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media',
    'file',
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
   * Tests the media bundle route for success.
   *
   * @dataProvider userProvider
   */
  public function testMediaBundleRouteSuccess(string $user): void {
    MediaType::create([
      'id' => 'test_bundle',
      'label' => 'Test Bundle',
      'source' => 'file',
    ])->save();

    $this->customLogin($user);
    $this->drupalGet('/islandora_workbench_integration/media_actions/entity_type/test_bundle');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the media bundle route for failure.
   *
   * @dataProvider userProvider
   */
  public function testMediaBundleRouteFail(string $user): void {
    $this->customLogin($user);
    $this->drupalGet('/islandora_workbench_integration/media_actions/entity_type/fake_bundle');
    $this->assertSession()->statusCodeEquals(404);
  }

}
