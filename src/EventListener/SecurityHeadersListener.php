<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE)]
final class SecurityHeadersListener
{
    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $headers = $event->getResponse()->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // The API only ever returns JSON and images: nothing it serves should
        // run scripts or be framed. The web profiler is left alone in dev.
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/_')) {
            $headers->set('X-Frame-Options', 'DENY');
            $headers->set('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");
        }
    }
}
