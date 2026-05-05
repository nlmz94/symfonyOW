<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Twig\Environment;

class ExceptionController extends AbstractController
{
    public function __construct(private Environment $twig)
    {
    }

    public function show(FlattenException $exception, DebugLoggerInterface $logger = null): Response
    {
        $statusCode = $exception->getStatusCode();
        $template = sprintf('error/%d.html.twig', $statusCode);

        if ($this->getParameter('kernel.debug') && $logger) {
            return new Response($this->renderView('@Twig/Exception/exception.html.twig', [
                'exception' => $exception,
                'logger' => $logger,
            ]));
        }

        if ($this->twig->getLoader()->exists($template)) {
            return $this->render($template, [
                'status_code' => $statusCode,
                'status_text' => Response::$statusTexts[$statusCode] ?? 'Error',
                'exception' => $exception,
            ]);
        }

        // Fallback to default Symfony error page
        return new Response($this->renderView('@Twig/Exception/error.html.twig', [
            'status_code' => $statusCode,
            'status_text' => Response::$statusTexts[$statusCode] ?? 'Error',
        ]));
    }
}