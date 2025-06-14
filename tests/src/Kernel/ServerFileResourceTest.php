<?php

namespace Drupal\Tests\islandora_workbench_integration\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;

/**
 * Tests the server file REST resource.
 *
 * @group islandora_workbench_integration
 */
class ServerFileResourceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'file',
    'rest',
    'serialization',
    'islandora_workbench_integration',
  ];

  /**
   * A sample text file path.
   *
   * @var string
   */
  protected string $testFilePath;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a test file.
    $public_files_path = $this->container->get('file_system')->realpath('public://');
    $this->testFilePath = $public_files_path . '/test-file.txt';
    file_put_contents($this->testFilePath, "This is a test.");
  }

  /**
   * Tests registering a .txt file and retrieving its contents.
   */
  public function testTxtFileReturnsContents(): void {
    $resource = $this->container->get('plugin.manager.rest')
      ->createInstance('server_file_resource', []);

    $response = $resource->post([
      'path' => $this->testFilePath,
      'retval' => 'contents',
    ]);

    $data = $response->getResponseData();
    $this->assertEquals("This is a test.", $data['contents']);
  }

  /**
   * Tests registering a file and getting the file ID.
   */
  public function testFileReturnsFid(): void {
    $resource = $this->container->get('plugin.manager.rest')
      ->createInstance('server_file_resource', []);

    $response = $resource->post([
      'path' => $this->testFilePath,
      'retval' => 'fid',
    ]);

    $data = $response->getResponseData();
    $this->assertArrayHasKey('fid', $data);
    $file = File::load($data['fid']);
    $this->assertNotNull($file);
    $this->assertEquals(basename($this->testFilePath), $file->getFilename());
  }

  /**
   * Tests behavior when file path is missing.
   */
  public function testMissingPathThrowsException(): void {
    $this->expectException(\Symfony\Component\HttpKernel\Exception\BadRequestHttpException::class);
    $resource = $this->container->get('plugin.manager.rest')
      ->createInstance('server_file_resource', []);
    $resource->post([]);
  }

  /**
   * Tests behavior when file path is invalid.
   */
  public function testInvalidPathThrowsException(): void {
    $this->expectException(\Symfony\Component\HttpKernel\Exception\BadRequestHttpException::class);
    $resource = $this->container->get('plugin.manager.rest')
      ->createInstance('server_file_resource', []);
    $resource->post(['path' => '/nonexistent/file.txt']);
  }

}
