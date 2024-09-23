<?php

namespace Drupal\players\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

class FederationRedirectSubscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['checkForRedirection']
    ];
  }

  public function checkForRedirection(RequestEvent $event) {
    $account = \Drupal::currentUser();
    if ($account->hasRole('federation_user') && $event->getRequest()->getPathInfo() == '/user') {
        $event->setResponse(new RedirectResponse(Url::fromRoute('players.federation_competitions')->toString()));
    }

    if ($account->hasRole('competition_admin') && $event->getRequest()->getPathInfo() == '/user') {
      $event->setResponse(new RedirectResponse(Url::fromRoute('players.published_federations')->toString()));
    }
  }
}
