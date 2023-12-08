<?php

namespace Drupal\decoupled_cookie_auth\EventSubscriber;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RequestSubscriber handles the kernel.request event.
 */
class RequestSubscriber implements EventSubscriberInterface {

  /**
   * Route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Constructs a new RequestSubscriber object.
   */
  public function __construct(CurrentRouteMatch $current_route_match) {
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST] = ['kernelRequest'];

    return $events;
  }

  /**
   * This method is called when the kernel.request is dispatched.
   *
   * Redirect the user.reset route to user.reset.login route to skip the form
   * presented when a user clicks a password reset link.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $requestEvent
   *   The dispatched event.
   */
  public function kernelRequest(RequestEvent $requestEvent) {
    if ($this->currentRouteMatch->getRouteName() === 'user.reset') {
      $redirect = new RedirectResponse(Url::fromRoute('user.reset.login', [
        'uid' => $this->currentRouteMatch->getParameter('uid'),
        'timestamp' => $this->currentRouteMatch->getParameter('timestamp'),
        'hash' => $this->currentRouteMatch->getParameter('hash'),
      ])->toString());
      $requestEvent->setResponse($redirect);
    }
  }

}
