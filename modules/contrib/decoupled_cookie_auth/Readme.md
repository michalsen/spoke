# Decoupled Cookie Auth

## Introduction

This module improves the user experience when using cookie authentication
with a decoupled frontend. Drupal should be hosted on a subdomain
of the frontend in order for the web browser to exchange cookies provided
by Drupal. For example, given a frontend at https://www.myfrontend.com
then Drupal would be hosted at a subdomain such as
https://app.myfrontend.com.

## CONFIGURATION

* Visit /admin/config/decoupled_cookie_auth/configuration to configure the
domain and paths of the frontend.

* Edit services.[your-environment].yml to set the cookie_domain to the
shared base domain. For example:

```yaml
# services.development.yml
parameters:
  session.storage.options:
    cookie_domain: '.myfrontend.com'
    cookie_domain_bc_mode: true
```

## What this module will do

* Automatically log in a user created via the 'rest.user_registration.POST'
route provided that email validation is not required.

* After a user authenticates with a one-time-login-url they are redirected
  to the frontend password reset page with the pass-reset-token query
  parameter appended. This token will need to be sent back to Drupal
  in order for the user to enter a new password without knowing their
  old one.

* If access is denied while using a password reset link, despite the link
being valid, then
  * if the user is denied since they're already logged in, redirect to the
  frontend password change form with a query parameter already_logged_in=1
  appended. Their existing password will be required.
  * if the account doesn't exist or is blocked then redirect to the
  frontend home page with a query parameter account_blocked=1 appended.

* Redirect visits to request a password reset email to the frontend
counterpart. This is necessary since if a password reset link is invalid
then Drupal redirects to request a new one.

* Redirect requests for the 'user.reset' route to 'user.reset.login'
so that '/login' does not need to be appended to password reset links.

* Alter the [site:login-url] token to point to the frontend login page.

## Tips
* Get a route to retrieve the logout token by applying the core patch at
https://drupal.org/node/3004421

* Install the 8.x-2.x branch of the mail_login module
(https://www.drupal.org/project/mail_login) to enable login using only
an email and password.

* To enable decoupled user registration, enable the User registration
REST resource at /admin/config/services/rest and give the anonymous
role permission to access it.
