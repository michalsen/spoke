<?php

namespace Drupal\decoupled_cookie_auth;

use Drupal\Core\Url;

/**
 * Defines an interface for the Decoupled Cookie Auth service.
 */
interface DecoupledCookieAuthServiceInterface {

  /**
   * Get the frontend url where the user may request a password reset email.
   *
   * @return \Drupal\Core\Url
   *   The frontend url object where the user may request a pass reset email.
   */
  public function requestPassReset(): Url;

  /**
   * Get the frontend url where the user will enter their new password.
   *
   * The pass-reset-token will be appended as a query parameter. This must
   * be sent back by the frontend when POSTing the new password in order to
   * skip the requirement for the user's current password.
   *
   * @param string $pass_reset_token
   *   The pass-reset-token that will be appended as a query parameter.
   *
   * @return \Drupal\Core\Url
   *   The frontend url object where the user will enter their new password
   */
  public function resetPass(string $pass_reset_token): Url;

  /**
   * Get the frontend url where the user will change their password.
   *
   * The user's current password will be required. User's get directed here if
   * they visit a password reset link while already logged in.
   *
   * @return \Drupal\Core\Url
   *   The frontend url object where the user will change their password
   */
  public function changePassword(): Url;

  /**
   * Get the frontend log in url.
   *
   * @return \Drupal\Core\Url
   *   The frontend url object where the user will log in.
   */
  public function login(): Url;

}
