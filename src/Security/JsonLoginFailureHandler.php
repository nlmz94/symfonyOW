<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/**
 * Same error shape as ApiExceptionListener, instead of json_login's default
 * {"error": "..."} body.
 */
final class JsonLoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $status = $exception instanceof TooManyLoginAttemptsAuthenticationException
            ? Response::HTTP_TOO_MANY_REQUESTS
            : Response::HTTP_UNAUTHORIZED;

        $response = new JsonResponse([
            'title' => Response::$statusTexts[$status],
            'status' => $status,
            'detail' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ], $status);
        $response->headers->set('Content-Type', 'application/problem+json');

        return $response;
    }
}
