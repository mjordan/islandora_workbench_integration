<?php

namespace Drupal\Tests\islandora_workbench_integration\Functional;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\file\Entity\File;

/**
 * Tests the GetHash controller.
 *
 * @group islandora_workbench_integration
 */
class IslandoraWorkbenchIntegrationGetHashControllerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['islandora_workbench_integration', 'file'];

  /**
   * A file entity for testing.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $testFile;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->rootUser);

    // Create a small temporary file on disk.
    $file_system = \Drupal::service('file_system');
    $path = '/tmp/test.txt';
    $dir = dirname($path);
    $file_system->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
    $file_system->saveData('hello world', $path, FileSystemInterface::EXISTS_REPLACE);

    // Create a Drupal file entity pointing to it.
    $this->testFile = File::create([
      'uri' => 'public://test.txt',
      // Copy into public://.
      'status' => 0,
    ]);
    // Physically copy.
    $file_system->copy($path, 'public://test.txt', FileSystemInterface::EXISTS_REPLACE);
    $this->testFile->save();
  }

  /**
   * Tests missing parameters.
   */
  public function testMissingParameters() {
    // No query parameters at all.
    $this->drupalGet('/islandora_workbench_integration/file_hash');
    $this->assertSession()->statusCodeEquals(200);
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertEquals(
      'Request is missing either the "file_uuid" or "algorithm" parameter.',
      $data['error']
    );
  }

  /**
   * Tests invalid algorithm parameter.
   */
  public function testInvalidAlgorithm() {
    $uuid = $this->testFile->uuid();
    $this->drupalGet('/islandora_workbench_integration/file_hash', [
      'query' => [
        'file_uuid' => $uuid,
        'algorithm' => 'foo',
      ],
    ]);
    $this->assertSession()->statusCodeEquals(200);
    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertEquals(
      '"algorithm" parameter must be one of "md5", "sha1", or "sha256".',
      $data['error']
    );
  }

  /**
   * Tests valid request returns correct checksum.
   */
  public function testValidChecksumResponse() {
    $uuid = $this->testFile->uuid();

    // Compute expected checksum for our test.txt with "hello world\n"
    // or no newline
    // We saved exactly "hello world" (no newline).
    $public_path = \Drupal::service('file_system')->realpath($this->testFile->getFileUri());
    $expected = hash_file('md5', $public_path);

    $this->drupalGet('/islandora_workbench_integration/file_hash', [
      'query' => [
        'file_uuid' => $uuid,
        'algorithm' => 'md5',
      ],
    ]);
    $this->assertSession()->statusCodeEquals(200);

    $data = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    // The controller wraps result in a numeric array.
    $this->assertCount(1, $data);
    $item = reset($data);
    $this->assertEquals($expected, $item['checksum']);
    $this->assertEquals($uuid, $item['file_uuid']);
    $this->assertEquals('md5', $item['algorithm']);
  }

}
