<?php

namespace Drupal\Tests\islandora_workbench_integration\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the version controller.
 *
 * @group islandora_workbench_integration
 */
class IslandoraWorkbenchIntegrationVersionControllerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['islandora_workbench_integration'];

  /**
   * Test the version controller.
   */
  public function testModuleVersionJson() {
    $this->drupalLogin($this->rootUser);

    $this->drupalGet('/islandora_workbench_integration/version');
    $this->assertSession()->statusCodeEquals(200);
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertArrayHasKey('integration_module_version', $data);

    /** @var \Drupal\Core\Extension\Extension $extension */
    $version = \Drupal::service('extension.list.module')->getExtensionInfo('islandora_workbench_integration')['version'] ?? "unknown";
    $this->assertEquals($version, $data['integration_module_version']);
  }

}
