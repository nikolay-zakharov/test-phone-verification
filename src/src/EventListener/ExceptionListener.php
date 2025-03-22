<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

final class ExceptionListener
{
    #[AsEventListener(event: KernelEvents::EXCEPTION)]
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if (str_starts_with($request->getRequestUri(), '/api/')) {
            $response = new JsonResponse();
            
            if ($exception instanceof UnprocessableEntityHttpException) {
                /** @var ValidationFailedException $validationException */
                $validationException = $exception->getPrevious();
                $errors = [];
                foreach ($validationException->getViolations() as $violation) {
                    $errors[$violation->getPropertyPath()][] = $violation->getMessage();
                }
                $response->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
                $response->setData(['errors' => $errors]);
                $response->headers->replace($exception->getHeaders());
            } elseif ($exception instanceof HttpExceptionInterface) {
                $response->setStatusCode($exception->getStatusCode());
                $response->headers->replace($exception->getHeaders());
                $response->setData(['errors' => $exception->getMessage()]);
            } else {
                $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
                $response->setData(['errors' => 'Internal error']);
            }

            $event->setResponse($response);
        }
    }
}
