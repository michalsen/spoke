<?php

namespace Drupal\Tests\decoupled_cookie_auth\Functional;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;
use GuzzleHttp\Cookie\CookieJar;

/**
 * Tests Decoupled Cookie Auth functionality.
 *
 * @group decoupled_cookie_auth
 */
class DecoupledCookieAuthTest extends BrowserTestBase {

  use AssertMailTrait {
    getMails as drupalGetMails;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'decoupled_cookie_auth',
    'rest',
    'serialization',
    'user',
  ];

  /**
   * The cookie jar.
   *
   * @var \GuzzleHttp\Cookie\CookieJar
   */
  protected $cookies;

  /**
   * Theme to enable.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * An unblocked user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * The Decouple Cookie Auth service.
   *
   * @var \Drupal\decoupled_cookie_auth\DecoupledCookieAuthService
   */
  protected $decoupledCookieAuthService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->cookies = new CookieJar();
    $this->config('decoupled_cookie_auth.configuration')
      ->set('frontend_url', 'https://example.com')
      ->set('frontend_request_password_reset_path', '/request-password-reset')
      ->set('frontend_reset_password_path', '/user/reset-password')
      ->set('frontend_change_password_path', '/user/change-password')
      ->set('frontend_login', '/login')
      ->save();
    // Create a user.
    $account = $this->drupalCreateUser();
    $this->account = User::load($account->id());
    $this->account->passRaw = $account->passRaw;
    $this->decoupledCookieAuthService = $this->container->get('decoupled_cookie_auth.service');
  }

  /**
   * Test redirect to request password reset when visiting expired reset link.
   */
  public function testRedirectInvalidResetLink() {
    $this->drupalGet('/user/reset/' . $this->account->id() . '/123/123');
    $this->assertEquals($this->getSession()
      ->getCurrentUrl(), $this->decoupledCookieAuthService->requestPassReset()
      ->toString());
  }

  /**
   * Test redirect when visiting a valid password reset link.
   */
  public function testRedirectValidResetLink() {
    // Trigger password reset email using decoupled route.
    $request_reset_url = Url::fromRoute('user.pass.http')
      ->setRouteParameter('_format', 'json')
      ->setAbsolute();
    $result = $this->getHttpClient()->post($request_reset_url->toString(), [
      'body' => json_encode(['mail' => $this->account->getEmail()]),
      'headers' => [
        'Accept' => 'application/json',
      ],
      'http_errors' => FALSE,
    ]);
    $this->assertEquals(200, $result->getStatusCode());
    // Get password reset url from the reset email.
    $reset_url = $this->getResetUrl();

    // Check redirect to front end reset password page.
    $this->drupalGet($reset_url);
    $query = [];
    $query_string = parse_url($this->getSession()
      ->getCurrentUrl(), PHP_URL_QUERY);
    parse_str($query_string, $query);
    $frontend_reset_url = $this->decoupledCookieAuthService->resetPass($query['pass-reset-token']);
    $query_params = $frontend_reset_url->getOption('query');
    $query_params[] = ['check_logged_in' => 1];
    $frontend_reset_url->setOption('query', $query_params);
    $this->assertEquals($frontend_reset_url->toString(), $this->getSession()
      ->getCurrentUrl());

    // Check that the user is logged in.
    $this->drupalGet('/user');
    $this->assertSession()
      ->elementContains('css', 'h1', $this->account->getDisplayName());
  }

  /**
   * Test redirect when a blocked account visits a valid password reset link.
   */
  public function testRedirectResetLinkBlockedAccount() {
    $accountBlocked = $this->drupalCreateUser();

    // Get valid password reset link.
    // Trigger password reset email using decoupled route.
    $request_reset_url = Url::fromRoute('user.pass.http')
      ->setRouteParameter('_format', 'json')
      ->setAbsolute();

    $result = $this->getHttpClient()->post($request_reset_url->toString(), [
      'body' => json_encode(['mail' => $accountBlocked->getEmail()]),
      'headers' => [
        'Accept' => 'application/json',
      ],
      'http_errors' => FALSE,
    ]);
    $this->assertEquals($result->getStatusCode(), 200);
    // Get password reset url from the reset email.
    $reset_url = $this->getResetUrl();

    // Block the user account.
    $accountBlocked->block();
    $accountBlocked->save();

    $this->drupalGet($reset_url);
    $frontend_destination = $this->config('decoupled_cookie_auth.configuration')->get('frontend_url') . '?account_blocked=1';
    $this->assertEquals($frontend_destination, $this->getSession()->getCurrentUrl());
  }

  /**
   * Test redirect when a logged in user visits a password reset link.
   */
  public function testRedirectLoggedInReset() {
    // Trigger password reset email using decoupled route.
    $request_reset_url = Url::fromRoute('user.pass.http')
      ->setRouteParameter('_format', 'json')
      ->setAbsolute();
    $result = $this->getHttpClient()->post($request_reset_url->toString(), [
      'body' => json_encode(['mail' => $this->account->getEmail()]),
      'headers' => [
        'Accept' => 'applicaiton/json',
      ],
      'http_errors' => FALSE,
    ]);
    $this->assertEquals($result->getStatusCode(), 200);
    // Get password reset url from the reset email.
    $reset_url = $this->getResetUrl();

    $this->drupalLogin($this->account);

    $this->drupalGet($reset_url);
    $this->assertEquals($this->decoupledCookieAuthService->changePassword()
      ->toString(), $this->getSession()->getCurrentUrl());
  }

  /**
   * Test that [site:login-url] token points to the frontend login page.
   */
  public function testLoginUrlToken() {
    /** @var \Drupal\Core\Utility\Token $token_service */
    $token_service = $this->container->get('token');
    $login_url = $token_service->replace('[site:login-url]');
    $this->assertEquals($this->decoupledCookieAuthService->login()
      ->toString(), $login_url);
  }

  /**
   * Test create user via REST with only email and password.
   */
  public function testRegisterUserOnlyEmail() {
    $this->config('user.settings')->set('verify_mail', FALSE)->save();
    $test_email = 'testemail@example.com';

    // Enable the rest.user_registration.POST endpoint.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $resourceConfigStorage */
    $resourceConfigStorage = $this->container->get('entity_type.manager')
      ->getStorage('rest_resource_config');
    $config = $resourceConfigStorage->create([
      'id' => 'user_registration',
      'granularity' => 'resource',
      'configuration' => [
        'methods' => ['POST'],
        'formats' => ['json'],
        'authentication' => ['cookie'],
      ],
    ]);
    $config->enable();
    $config->save();
    \Drupal::service('router.builder')->rebuild();
    $this->grantPermissions(Role::load(RoleInterface::ANONYMOUS_ID), ['restful post user_registration']);

    // Assert that the new account doesn't exist yet.
    $account_created = user_load_by_mail($test_email);
    $this->assertFalse($account_created, 'New account with email ' . $test_email . 'has not yet been created.');

    // Create the new account with only an email and password.
    $register_url = Url::fromRoute('rest.user_registration.POST')
      ->setOption('query', ['_format' => 'json'])
      ->setAbsolute();
    $response = $this->getHttpClient()->post($register_url->toString(), [
      'body' => json_encode([
        'mail' => [
          'value' => $test_email,
        ],
        'pass' => [
          'value' => 'testpass',
        ],
      ]),
      'headers' => [
        'Content-Type' => 'application/json',
      ],
    ]);
    $this->assertEquals(200, $response->getStatusCode());
    /** @var \Drupal\user\UserInterface $account_created */
    $account_created = user_load_by_mail($test_email);
    $this->assertEquals($test_email, $account_created->getEmail());
  }

  /**
   * Retrieves password reset email and extracts the login link.
   *
   * @see \Drupal\Tests\user\Functional\UserPasswordResetTest::getResetURL
   */
  public function getResetUrl() {
    // Assume the most recent email.
    $_emails = $this->drupalGetMails();
    $email = end($_emails);
    $urls = [];
    preg_match('#.+user/reset/.+#', $email['body'], $urls);

    return $urls[0];
  }

}
