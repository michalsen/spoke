<?php

namespace Drupal\decoupled_cookie_auth\EventSubscriber;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\decoupled_cookie_auth\DecoupledCookieAuthService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RedirectResponseSubscriber handles the kernel.response event.
 */
class RedirectResponseSubscriber implements EventSubscriberInterface {

  /**
   * The DecoupledCookieAuthService.
   *
   * @var \Drupal\decoupled_cookie_auth\DecoupledCookieAuthService
   */
  protected $service;

  /**
   * Constructs a new RedirectResponseSubscriber object.
   */
  public function __construct(DecoupledCookieAuthService $service) {
    $this->service = $service;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE] = ['handleResponse'];

    return $events;
  }

  /**
   * This method is called when the kernel.response is dispatched.
   *
   * Handle the redirect after the user clicks the password reset link in an
   * email. The destination is the frontend password reset form, including the
   * pass-reset-token query parameter.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The dispatched event.
   */
  public function handleResponse(ResponseEvent $event) {
    $response = $event->getResponse();
    if ($response instanceof RedirectResponse) {
      $query = [];
      $query_string = parse_url($response->getTargetUrl(), PHP_URL_QUERY);
      if (!empty($query_string)) {
        parse_str($query_string, $query);
        if (!empty($query['pass-reset-token'])) {
          $url = $this->service->resetPass($query['pass-reset-token']);
          $event->setResponse(new TrustedRedirectResponse($url->toString()));
          return;
        }
      }
      if (str_contains($response->getTargetUrl(), '/user/password')) {
        // The password reset link was invalid. Redirect the user to the
        // frontend path to request a new password reset link.
        $url = $this->service->requestPassReset();
        $event->setResponse(new TrustedRedirectResponse($url->toString()));
      }
    }
  }

}
