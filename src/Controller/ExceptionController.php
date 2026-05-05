<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class ExceptionController extends AbstractController
{
    public function __construct(private Environment $twig)
    {
    }

    public function show(FlattenException $exception): Response
    {
        $statusCode = $exception->getStatusCode();
        $statusText = Response::$statusTexts[$statusCode] ?? 'Error';
        $template = sprintf('error/%d.html.twig', $statusCode);

        if ($this->twig->getLoader()->exists($template)) {
            return $this->render($template, [
                'status_code' => $statusCode,
                'status_text' => $statusText,
                'exception'   => $exception,
            ], new Response('', $statusCode));
        }

        // No custom template for this code — return a minimal HTML page
        $body = sprintf(
            '<!DOCTYPE html><html><head><title>%1$d %2$s</title></head>'
            . '<body><h1>%1$d %2$s</h1></body></html>',
            $statusCode,
            htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8'),
        );

        return new Response($body, $statusCode);
    }
}