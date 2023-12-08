<?php

namespace Drupal\decoupled_cookie_auth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfigurationForm configures the decoupled_cookie_auth module.
 */
class ConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'decoupled_cookie_auth.configuration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'decoupled_cookie_auth_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('decoupled_cookie_auth.configuration');
    $form['allow_registration_only_email'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow registration with only email and password.'),
      '#description' => $this->t('Allow registration with only an email and password when POSTing to <code>/user/register?_format=json</code>. The <a href="@user_reg_rest">User registration REST resource</a> must be enabled. A unique username will be generated from the name part of the email address with a numerical suffix if necessary to ensure uniqueness. This should be used with the <a href="@link">Mail Login</a> module which will enable log in using only the email and password.',
        [
          '@link' => 'https://www.drupal.org/project/mail_login',
          '@user_reg_rest' => '/admin/config/services/rest/resource/user_registration/edit',
        ]),
      '#default_value' => $config->get('allow_registration_only_email'),
    ];

    $form['frontend_reset_password_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Frontend reset password path'),
      '#description' => $this->t('Root relative path on the frontend where the user will enter their new password after clicking the password reset link in their email. Begins with a <strong>/</strong>. The user will not be asked for their existing password provided that the frontend is configured to pass the <code>pass-reset-token</code>
query parameter back to Drupal when the frontend password reset form is submitted.'),
      '#default_value' => $config->get('frontend_reset_password_path'),
      '#attributes' => [
        'placeholder' => '/user/reset-password',
      ],
      '#pattern' => '/.+',
    ];

    $form['frontend_change_password_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Frontend change password path'),
      '#description' => $this->t('Root relative path on the frontend where the user may change their password. Begins with a <strong>/</strong>. The user will be sent here if they try to use a password reset link when they are already logged in. Their existing password will be required in order to change their password.'),
      '#default_value' => $config->get('frontend_change_password_path'),
      '#attributes' => [
        'placeholder' => '/user/change-password',
      ],
      '#pattern' => '/.+',
    ];

    $form['frontend_request_password_reset_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Frontend request password reset path'),
      '#description' => $this->t('Root relative path on the frontend where the user may enter their email address to request a password reset email. Begins with a <strong>/</strong>. They will be directed here if they use a password reset link that has expired or is invalid.'),
      '#default_value' => $config->get('frontend_request_password_reset_path'),
      '#attributes' => [
        'placeholder' => '/request-password-reset',
      ],
      '#pattern' => '/.+',
    ];

    $form['frontend_login'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Frontend log in path'),
      '#description' => $this->t('Root relative path on the frontend to the log in page.'),
      '#default_value' => $config->get('frontend_login'),
      '#attributes' => [
        'placeholder' => '/login',
      ],
      '#pattern' => '/.+',
    ];

    $form['frontend_url'] = [
      '#type' => 'url',
      '#title' => $this->t('URL to front end'),
      '#description' => $this->t('The absolute URL to the frontend, such as <em>https://myfrontend.com</em>, without the trailing slash. This is the URL to the frontend homepage. This Drupal backend should be hosted on a subdomain such as
<em>https://app.myfrontend.com</em> so that session cookies will be sent from the frontend.',
      [
        '@link' => 'https://docs.netlify.com/routing/redirects/rewrites-proxies/#proxy-to-another-service',
        '@account_config_link' => '/admin/config/people/accounts',
      ]),
      '#attributes' => [
        'placeholder' => 'https://example.com',
      ],
      '#default_value' => $config->get('frontend_url'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $keys = [
      'allow_registration_only_email',
      'frontend_reset_password_path',
      'frontend_change_password_path',
      'frontend_request_password_reset_path',
      'frontend_url',
      'frontend_login',
    ];

    foreach ($keys as $key) {
      $this->config('decoupled_cookie_auth.configuration')
        ->set($key, $form_state->getValue($key));
    }

    $this->config('decoupled_cookie_auth.configuration')->save();
  }

}
