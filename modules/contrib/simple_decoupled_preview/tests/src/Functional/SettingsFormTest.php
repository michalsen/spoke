<?php

namespace Drupal\Tests\simple_decoupled_preview\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Defines a test for Simple Decoupled Preview SettingsForm.
 *
 * @group simple_decoupled_preview
 */
class SettingsFormTest extends BrowserTestBase {

  use ContentTypeCreationTrait;

  /**
   * Modules installed for all tests.
   *
   * @var array
   */
  protected static $modules = [
    'simple_decoupled_preview',
    'node',
    'serialization',
    'jsonapi',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $formUrl;

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ]);

  }

  /**
   * Tests settings form validation.
   */
  public function testAdminFormValidation() {
    $this->formUrl = Url::fromRoute('simple_decoupled_preview.settings_form');
    $this->drupalGet($this->formUrl);
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->createUser([
      'administer simple decoupled preview',
    ]));
    $this->drupalGet($this->formUrl);
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $this->submitForm([
      'includes[article]' => 'field_test',
    ], 'Save configuration');
    $assert->pageTextContains('The include path at field_test is invalid for Article includes.');
    $this->submitForm([
      'includes[page]' => 'field_test',
    ], 'Save configuration');
    $assert->pageTextContains('The include path at field_test is invalid for Basic page includes.');
    // Submit valid values.
    $server_url = sprintf('https://%s.com/preview', $this->randomMachineName());
    $this->submitForm([
      'preview_callback_url' => $server_url,
      'bundles[article]' => 'article',
      'includes[article]' => 'uid',
      'includes[page]' => 'uid',
      'delete_log_entities' => TRUE,
    ], 'Save configuration');
    $config = \Drupal::config('simple_decoupled_preview.settings');
    $this->assertEquals($server_url, $config->get('preview_callback_url'));
    $this->assertEquals(['article' => 'article'], $config->get('bundles'));
    $this->assertEquals(['article' => 'uid', 'page' => 'uid'], $config->get('includes'));
    $this->assertTrue($config->get('delete_log_entities'));
  }

}
