<?php

namespace Drupal\Tests\islandora_workbench_integration\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Functional tests for the "Members of node" REST View.
 *
 * Covers config/optional/views.view.members_of_node.yml: the rest_export_1
 * display at islandora_workbench_integration/members-of-node/{nid}, which
 * returns a node's direct field_member_of children (of bundle
 * islandora_object, per the filter inherited from the View's default
 * display), ordered by field_weight ascending.
 *
 * @group islandora_workbench_integration
 */
class MembersOfNodeViewTest extends BrowserTestBase {

  use EntityReferenceTestTrait;

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
    'views',
    'rest',
    'serialization',
    'user',
    'islandora_workbench_integration',
  ];

  /**
   * Test user with only this module's own permission, no core permission.
   *
   * The View's default display access plugin is 'multiple_permissions',
   * granting access to a user with EITHER 'use islandora workbench' OR
   * 'administer nodes' — matching the pattern already used by this
   * module's other two Views (term_from_uri, term_from_term_name), which
   * OR 'use islandora workbench' against 'administer taxonomy'. This user
   * covers that first arm: a plain Workbench service account with no
   * other admin permissions.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $testUser;

  /**
   * Test user with only the core 'administer nodes' permission.
   *
   * Covers the second arm of the OR, independent of $testUser, so a
   * regression in either permission string is caught on its own.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $nodeAdminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // This module's own dependency is only on islandora:islandora, which
    // does not itself provision the islandora_object bundle or its
    // field_member_of / field_weight fields — those normally come from
    // islandora_defaults on a real site. Create them here so this test
    // does not depend on that module being present, but skip creation if
    // a fuller Islandora install already provisioned them.
    if (!NodeType::load('islandora_object')) {
      NodeType::create([
        'type' => 'islandora_object',
        'name' => 'Islandora Object',
      ])->save();
    }

    if (!FieldStorageConfig::loadByName('node', 'field_member_of')) {
      $this->createEntityReferenceField(
        'node',
        'islandora_object',
        'field_member_of',
        'Member Of',
        'node'
      );
    }

    if (!FieldStorageConfig::loadByName('node', 'field_weight')) {
      FieldStorageConfig::create([
        'field_name' => 'field_weight',
        'entity_type' => 'node',
        'type' => 'integer',
      ])->save();
      FieldConfig::create([
        'field_name' => 'field_weight',
        'entity_type' => 'node',
        'bundle' => 'islandora_object',
        'label' => 'Weight',
      ])->save();
    }

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

    Role::create([
      'id' => 'node_admin',
      'label' => 'Node Admin',
    ])->grantPermission('administer nodes')->save();

    $this->nodeAdminUser = User::create([
      'name' => 'node_admin_user',
      'mail' => 'nodeadminuser@example.com',
      'password' => 'test_password',
      'status' => 1,
    ]);
    $this->nodeAdminUser->addRole('node_admin')->save();
  }

  /**
   * Data provider that returns an array of arguments for testing.
   *
   * Covers all three ways in to this View given its multiple_permissions
   * access plugin: uid 1 (bypasses permission checks entirely), a user
   * with only 'use islandora workbench', and a user with only
   * 'administer nodes' — proving the OR works via either permission on
   * its own, not just in combination.
   *
   * @return array
   *   An array of arrays, each containing arguments for the tests.
   */
  public function userProvider() {
    return [
      ['root'],
      ['test_user'],
      ['node_admin_user'],
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
    elseif ($username === 'node_admin_user') {
      $this->drupalLogin($this->nodeAdminUser);
    }
    else {
      $this->drupalLogin($this->rootUser);
    }
  }

  /**
   * Creates a published islandora_object node.
   *
   * @param string $title
   *   The node title.
   * @param \Drupal\node\Entity\Node|null $parent
   *   The parent node to reference via field_member_of, or NULL for none.
   * @param int $weight
   *   The value for field_weight.
   *
   * @return \Drupal\node\Entity\Node
   *   The created node.
   */
  protected function createMemberNode(string $title, ?Node $parent, int $weight): Node {
    $values = [
      'type' => 'islandora_object',
      'title' => $title,
      'status' => 1,
      'field_weight' => $weight,
    ];
    if ($parent) {
      $values['field_member_of'] = ['target_id' => $parent->id()];
    }
    $node = Node::create($values);
    $node->save();
    return $node;
  }

  /**
   * Decodes the JSON body of the current Mink session response.
   *
   * @return array
   *   The decoded response body.
   */
  protected function getJsonResponse(): array {
    $content = $this->getSession()->getPage()->getContent();
    $decoded = json_decode($content, TRUE);
    $this->assertIsArray($decoded, 'Response body should decode as JSON. Raw body: ' . $content);
    return $decoded;
  }

  /**
   * Tests that direct members are returned, ordered by field_weight ASC.
   *
   * @dataProvider userProvider
   */
  public function testMembersOfNodeReturnsChildrenOrderedByWeight(string $user): void {
    $parent = $this->createMemberNode('Parent object', NULL, 0);

    // Deliberately create children out of weight order, to confirm the
    // response is sorted by the View, not by creation/insertion order.
    $childC = $this->createMemberNode('Child C', $parent, 30);
    $childA = $this->createMemberNode('Child A', $parent, 10);
    $childB = $this->createMemberNode('Child B', $parent, 20);

    $this->customLogin($user);
    $this->drupalGet('islandora_workbench_integration/members-of-node/' . $parent->id(), [
      'query' => ['_format' => 'json'],
    ]);
    $this->assertSession()->statusCodeEquals(200);

    $body = $this->getJsonResponse();
    $this->assertCount(3, $body, 'Expected exactly the three direct children.');

    $returnedNids = array_column($body, 'nid');
    $this->assertSame(
      [$childA->id(), $childB->id(), $childC->id()],
      array_map('intval', $returnedNids),
      'Members should be returned ordered by field_weight ascending, not by creation order.'
    );

    $first = $body[0];
    $this->assertArrayHasKey('nid', $first);
    $this->assertArrayHasKey('field_weight_value', $first);
  }

  /**
   * Tests that members sharing the same field_weight are then ordered by
   * nid ascending, per the secondary sort on the rest_export_1 display.
   *
   * @dataProvider userProvider
   */
  public function testMembersOfNodeBreaksWeightTiesByNid(string $user): void {
    $parent = $this->createMemberNode('Parent object', NULL, 0);

    // childTieFirst and childTieSecond share the same weight. Since nids
    // are assigned in creation order, childTieFirst will have the lower
    // nid and should sort first among the tied pair.
    $childTieFirst = $this->createMemberNode('Tied child, lower nid', $parent, 5);
    $childTieSecond = $this->createMemberNode('Tied child, higher nid', $parent, 5);
    $childHeaviest = $this->createMemberNode('Untied heaviest child', $parent, 100);

    $this->customLogin($user);
    $this->drupalGet('islandora_workbench_integration/members-of-node/' . $parent->id(), [
      'query' => ['_format' => 'json'],
    ]);
    $this->assertSession()->statusCodeEquals(200);

    $body = $this->getJsonResponse();
    $returnedNids = array_map('intval', array_column($body, 'nid'));
    $this->assertSame(
      [$childTieFirst->id(), $childTieSecond->id(), $childHeaviest->id()],
      $returnedNids,
      'Members with equal field_weight should be ordered by nid ascending as the tie-break, before members with a strictly greater weight.'
    );
  }

  /**
   * Tests that a node with no members returns an empty array, not an error.
   *
   * @dataProvider userProvider
   */
  public function testMembersOfNodeWithNoChildrenReturnsEmptyArray(string $user): void {
    $lonelyParent = $this->createMemberNode('Childless object', NULL, 0);

    $this->customLogin($user);
    $this->drupalGet('islandora_workbench_integration/members-of-node/' . $lonelyParent->id(), [
      'query' => ['_format' => 'json'],
    ]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSame([], $this->getJsonResponse());
  }

  /**
   * Tests that only islandora_object children are returned.
   *
   * The rest_export_1 display has no filters of its own, so it inherits the
   * default display's bundle filter (type = islandora_object). A child of
   * a different bundle should not appear in the results even if it
   * correctly references the parent via field_member_of.
   *
   * @dataProvider userProvider
   */
  public function testMembersOfNodeExcludesOtherBundles(string $user): void {
    if (!NodeType::load('page')) {
      NodeType::create(['type' => 'page', 'name' => 'Basic page'])->save();
    }

    $parent = $this->createMemberNode('Parent object', NULL, 0);
    $matchingChild = $this->createMemberNode('Matching child', $parent, 10);

    $otherBundleChild = Node::create([
      'type' => 'page',
      'title' => 'Wrong bundle child',
      'status' => 1,
    ]);
    $otherBundleChild->save();

    $this->customLogin($user);
    $this->drupalGet('islandora_workbench_integration/members-of-node/' . $parent->id(), [
      'query' => ['_format' => 'json'],
    ]);
    $this->assertSession()->statusCodeEquals(200);

    $body = $this->getJsonResponse();
    $this->assertCount(1, $body, 'Only the islandora_object-bundle child should be returned.');
    $this->assertSame($matchingChild->id(), (int) $body[0]['nid']);
  }

  /**
   * Tests behavior when the contextual filter argument is not a valid nid.
   *
   * The argument's "validate" plugin is set to "none" in the shipped View,
   * so a non-numeric value is not rejected at the argument-validation
   * layer. With default_action: empty, this should fall through to an
   * empty result set rather than a fatal error or an unhandled exception.
   *
   * @dataProvider userProvider
   */
  public function testMembersOfNodeWithNonNumericArgumentDoesNotError(string $user): void {
    $this->customLogin($user);
    $this->drupalGet('islandora_workbench_integration/members-of-node/not-a-number', [
      'query' => ['_format' => 'json'],
    ]);
    $status = $this->getSession()->getStatusCode();
    $this->assertNotEquals(500, $status, 'A non-numeric nid should not cause a fatal error.');

    if ($status === 200) {
      $this->assertSame([], $this->getJsonResponse());
    }
  }

  /**
   * Tests that an anonymous user with neither permission is denied.
   *
   * Anonymous has neither 'use islandora workbench' nor 'administer
   * nodes', so both arms of the multiple_permissions OR should fail.
   */
  public function testMembersOfNodeRequiresPermission(): void {
    $parent = $this->createMemberNode('Parent object', NULL, 0);
    $this->drupalGet('islandora_workbench_integration/members-of-node/' . $parent->id(), [
      'query' => ['_format' => 'json'],
    ]);
    $this->assertSession()->statusCodeEquals(403);
  }

}