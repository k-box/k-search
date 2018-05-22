<?php

namespace App\EventListener;

use App\Exception\BadRequestException;
use App\Exception\DataDownloadErrorException;
use App\Exception\InternalSearchException;
use App\Exception\KRegistryException;
use App\Exception\KSearchException;
use App\Exception\SolrEntityNotFoundException;
use App\Model\Error\Error;
use App\Model\Error\ErrorResponse;
use App\Model\RPCRequest;
use JMS\Serializer\Exception\RuntimeException as JMSRuntimeException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ExceptionListener implements EventSubscriberInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var bool */
    private $debug;

    public function __construct(bool $appDebug, LoggerInterface $logger)
    {
        $this->debug = $appDebug;
        $this->logger = $logger;
    }

    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        $exception = $event->getException();

        if (!($exception instanceof JMSRuntimeException ||
            $exception instanceof KSearchException ||
            $exception instanceof AccessDeniedException ||
            $exception instanceof \InvalidArgumentException
        )) {
            return;
        }

        $this->logger->debug('Handling exception: {message}', [
            'message' => $exception->getMessage(),
            'exception' => $exception,
            'request' => $event->getRequest(),
        ]);

        // Get the request-id, if any.
        $requestId = (string) $event->getRequest()->headers->get(RPCRequest::REQUEST_ID_HEADER, null);

        switch (\get_class($exception)) {
            case JMSRuntimeException::class:
                $error = new Error(400, 'Wrong data provided!', [$exception->getMessage()]);
                break;
            case BadRequestException::class:
                /** @var BadRequestException $exception */
                $error = new Error(400, 'Wrong data provided!', $exception->getErrorsForJsonProperties());
                break;
            case DataDownloadErrorException::class:
                $previous = $exception->getPrevious();
                $error = new Error(400, $exception->getMessage(), $previous ? $previous->getMessage() : null);
                break;
            case SolrEntityNotFoundException::class:
                $error = new Error(404, $exception->getMessage());
                break;
            case InternalSearchException::class:
                $error = new Error(500, 'Error communicating with the Indexing service!', [
                    $exception->getMessage(),
                ]);
                break;
            case AccessDeniedException::class:
                $error = new Error(403, $exception->getMessage());
                break;
            case KRegistryException::class:
                $error = new Error(500, $exception->getMessage(), $exception->getPrevious()->getMessage());
                break;
            default:
                $data = [];
                if ($this->debug) {
                    $data = [
                        'type' => \get_class($exception),
                        'message' => $exception->getMessage(),
                    ];
                }
                $error = new Error(500, 'Internal Server Error. Please contact the system administrator.', $data);
        }

        if (500 >= $error->code) {
            $this->logger->error('Internal exception while handling {url} request', [
                'url' => $event->getRequest()->getUri(),
                'exception' => $exception,
                'message' => $exception->getMessage(),
                'event' => $event,
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
