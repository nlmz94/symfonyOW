<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Answers logout with 204 instead of the firewall's default redirect.
 * Must run before Symfony's DefaultLogoutListener (priority 64), which only
 * redirects when no response has been set yet.
 */
#[AsEventListener(event: LogoutEvent::class, priority: 128)]
final class LogoutResponseListener
{
    public function __invoke(LogoutEvent $event): void
    {
        $event->setResponse(new Response(status: Response::HTTP_NO_CONTENT));
    }
}
