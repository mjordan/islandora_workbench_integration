<?php

namespace Drupal\Tests\islandora_workbench_integration\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the core-version controller.
 *
 * @group islandora_workbench_integration
 */
class IslandoraWorkbenchIntegrationCoreVersionControllerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['islandora_workbench_integration'];

  /**
   * Tests that the core-version endpoint returns the Drupal version.
   */
  public function testCoreVersionJson() {
    $this->drupalLogin($this->rootUser);

    $this->drupalGet('/islandora_workbench_integration/core_version');
    $this->assertSession()->statusCodeEquals(200);
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertArrayHasKey('core_version', $data);
    $this->assertEquals(\Drupal::VERSION, $data['core_version']);
  }

}
