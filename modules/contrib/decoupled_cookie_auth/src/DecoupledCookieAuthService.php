<?php

namespace Drupal\decoupled_cookie_auth;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;

/**
 * Defines a class of helper methods for the decoupled_cookie_auth module.
 */
class DecoupledCookieAuthService implements DecoupledCookieAuthServiceInterface {

  /**
   * The immutable decoupled_cookie_auth configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a new DecoupledCookieAuthService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('decoupled_cookie_auth.configuration');
  }

  /**
   * {@inheritdoc}
   */
  public function requestPassReset(): Url {
    return Url::fromUri($this->config->get('frontend_url') . $this->config->get('frontend_request_password_reset_path'))
      ->setOption('query', ['link_invalid' => 1]);
  }

  /**
   * {@inheritdoc}
   */
  public function resetPass(string $pass_reset_token): Url {
    return Url::fromUri($this->config->get('frontend_url') . $this->config->get('frontend_reset_password_path'))
      ->setOption('query', ['pass-reset-token' => $pass_reset_token]);
  }

  /**
   * {@inheritdoc}
   */
  public function changePassword(): Url {
    return Url::fromUri($this->config->get('frontend_url') . $this->config->get('frontend_change_password_path'))
      ->setOption('query', ['already_logged_in' => '1']);
  }

  /**
   * {@inheritdoc}
   */
  public function login(): Url {
    return Url::fromUri($this->config->get('frontend_url') . $this->config->get('frontend_login'));
  }

}
