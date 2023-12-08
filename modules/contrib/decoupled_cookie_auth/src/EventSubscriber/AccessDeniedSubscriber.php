<?php

namespace Drupal\decoupled_cookie_auth\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\decoupled_cookie_auth\DecoupledCookieAuthService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class AccessDeniedSubscriber redirects after access denied.
 */
class AccessDeniedSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * The DecoupledCookieAuthService.
   *
   * @var \Drupal\decoupled_cookie_auth\DecoupledCookieAuthService
   */
  protected $serviceDecoupleCookieAuth;

  /**
   * Constructs a new AccessDeniedSubscriber object.
   */
  public function __construct(AccountInterface $account, ConfigFactoryInterface $config_factory, CurrentRouteMatch $current_route_match, DecoupledCookieAuthService $service) {
    $this->account = $account;
    $this->config = $config_factory->get('decoupled_cookie_auth.configuration');
    $this->routeMatch = $current_route_match;
    $this->serviceDecoupleCookieAuth = $service;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION] = ['onException'];
    return $events;
  }

  /**
   * Redirects user when access is denied on the user.reset.login route.
   *
   * Access will be denied if user is already logged in when using the
   * password reset link.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The dispatched event.
   */
  public function onException(ExceptionEvent $event) {
    $exception = $event->getThrowable();
    if ($exception instanceof AccessDeniedHttpException) {
      if ($this->routeMatch->getRouteName() == 'user.reset.login') {
        if ($this->account->isAuthenticated()) {
          // Redirect to the frontend change password URL. The user must have
          // already been logged in when they clicked the password reset link.
          $url = $this->serviceDecoupleCookieAuth->changePassword();
        }
        else {
          // Account doesn't exist or is blocked. Redirect to the home page
          // with a query parameter to indicate the reason.
          $url = Url::fromUri($this->config->get('frontend_url'))
            ->setOption('query', ['account_blocked' => 1]);
        }
        $event->setResponse(new TrustedRedirectResponse($url->toString()));
      }
    }
  }

}
