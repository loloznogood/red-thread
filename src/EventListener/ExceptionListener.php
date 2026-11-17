<?php

namespace App\EventListener;

use App\Exception\ArticleAlreadyPublishedException;
use App\Exception\UserAlreadyExistsException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class ExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        // Retrieve the exception object from the received event
        $exception = $event->getThrowable();

        $status = match (true) {
            $exception instanceof ArticleAlreadyPublishedException,
            $exception instanceof UserAlreadyExistsException => Response::HTTP_CONFLICT,
            default => null,
        };

        if ($status === null) {
            return; // not one of our business exceptions: let Symfony handle it
        }

        // Build a custom response (e.g., JSON for an API)
        $responseData = [
            'title' => Response::$statusTexts[$status],
            'status' => $status,
            'detail' => $exception->getMessage(),
        ];

        $response = new JsonResponse(
            $responseData,
            $status,
            ['Content-Type' => 'application/problem+json'],
        );
        // Send the modified response object to the event
        $event->setResponse($response);
    }
}
