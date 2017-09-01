<?php

namespace App\EventListener;

use App\Exception\BadRequestException;
use App\Exception\InternalSearchException;
use App\Exception\KSearchException;
use App\Exception\ResourceNotFoundException;
use App\Model\Error\Error;
use App\Model\Error\ErrorResponse;
use App\Model\RPCRequest;
use JMS\Serializer\Exception\RuntimeException as JMSRuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionListener implements EventSubscriberInterface
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ((!$exception instanceof JMSRuntimeException) && (!$exception instanceof KSearchException)) {
            return;
        }

        // Get the request-id, if any.
        $requestId = $event->getRequest()->headers->get(RPCRequest::REQUEST_ID_HEADER, null);

        switch (get_class($exception)) {
            case JMSRuntimeException::class:
                $error = new Error(400, 'Wrong data provided!', [$exception->getMessage()]);
                break;
            case BadRequestException::class:
                /** @var BadRequestException $exception */
                $error = new Error(400, 'Wrong data provided!', $exception->getErrors());
                break;
            case ResourceNotFoundException::class:
                $error = new Error(404, $exception->getMessage());
                break;
            case InternalSearchException::class:
                /** @var InternalSearchException $exception */
                $error = new Error(500, 'Error while communicating with the Indexing service!', [
                    $exception->getMessage(),
                ]);
                break;
            default:
                $error = new Error(500, 'Unknown error!', [
                    'type' => get_class($exception),
                    'message' => $exception->getMessage(),
                ]);
        }

        $response = new JsonResponse(new ErrorResponse($error, $requestId));

        // Send the modified response object to the event
        $event->allowCustomResponseCode();
        $event->setResponse($response);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }
}
