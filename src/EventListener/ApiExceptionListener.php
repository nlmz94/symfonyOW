<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Every error leaves the API as an RFC 7807-style JSON body:
 *   {"title": "Not Found", "status": 404, "detail": "..."}
 * plus "violations" for validation failures.
 *
 * Priority -64: after the security firewall has turned auth failures into
 * 401/403 (priority 1) and after the kernel has logged the exception
 * (priority 0), but before the default HTML error controller (-128).
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: -64)]
final readonly class ApiExceptionListener
{
    public function __construct(
        #[Autowire('%kernel.debug%')]
        private bool $debug,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $status = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;
        $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];

        $body = [
            'title' => Response::$statusTexts[$status] ?? 'Error',
            'status' => $status,
        ];

        // Client errors carry messages written for the client; server errors
        // may leak internals, so only show them while debugging.
        if ($status < 500 || $this->debug) {
            $body['detail'] = $exception->getMessage();
        }

        $validation = $exception->getPrevious();
        if ($validation instanceof ValidationFailedException) {
            $body['title'] = 'Validation Failed';
            $body['violations'] = [];
            foreach ($validation->getViolations() as $violation) {
                /** @var ConstraintViolationInterface $violation */
                $body['violations'][] = [
                    'propertyPath' => $violation->getPropertyPath(),
                    'title' => (string) $violation->getMessage(),
                ];
            }
            $body['detail'] = implode("\n", array_column($body['violations'], 'title'));
        }

        if ($this->debug) {
            $body['debug'] = [
                'class' => $exception::class,
                'file' => $exception->getFile() . ':' . $exception->getLine(),
            ];
        }

        $response = new JsonResponse($body, $status, $headers);
        $response->headers->set('Content-Type', 'application/problem+json');

        $event->setResponse($response);
    }
}
