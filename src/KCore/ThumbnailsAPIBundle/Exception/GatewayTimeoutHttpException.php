<?php

namespace KCore\ThumbnailsAPIBundle\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GatewayTimeoutHttpException extends HttpException
{
    /**
     * Constructor.
     *
     * {@inheritdoc}
     */
    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct(Response::HTTP_GATEWAY_TIMEOUT, $message, $previous, [], $code);
    }
}
