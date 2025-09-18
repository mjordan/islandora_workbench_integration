<?php

namespace Drupal\Tests\islandora_workbench_integration\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\file\Entity\File;

/**
 * Tests the file rename controller.
 *
 * @group islandora_workbench_integration
 */
class IslandoraWorkbenchIntegrationFileRenameControllerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['islandora_workbench_integration', 'file'];

  /**
   * Test file entity.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $testFile;

  /**
   * User with rename files permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $privilegedUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a user with 'rename files' permission.
    $this->privilegedUser = $this->drupalCreateUser(['rename files']);

    // Create a test file.
    $file_system = \Drupal::service('file_system');
    $temp_file = $file_system->tempnam('temporary://', 'test');
    file_put_contents($temp_file, 'Test file content');

    $this->testFile = File::create([
      'uri' => $temp_file,
      'filename' => 'test_file.txt',
      'status' => 1,
    ]);
    $this->testFile->save();
  }

  /**
   * Test that the route exists and responds.
   */
  public function testRouteExists() {
    $this->drupalLogin($this->privilegedUser);

    // Just test that the route responds (even if with an error).
    $this->drupalGet('/islandora_workbench_integration/rename/' . $this->testFile->id());

    // Accept any response that's not 404 (route not found).
    $status_code = $this->getSession()->getStatusCode();
    $this->assertNotEquals(404, $status_code, 'Route should exist and not return 404');
  }

  /**
   * Test unauthenticated access.
   */
  public function testUnauthenticatedAccess() {
    // Don't login - test unauthenticated access.
    $this->drupalGet('/islandora_workbench_integration/rename/' . $this->testFile->id());

    // Should be denied access without authentication.
    $status_code = $this->getSession()->getStatusCode();
    $this->assertContains($status_code, [401, 403], 'Unauthenticated access should be denied');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Clean up test files.
    if ($this->testFile) {
      $this->testFile->delete();
    }
    parent::tearDown();
  }

}
