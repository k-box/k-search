<?php

namespace KCore\ThumbnailsAPIBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class GatewayMaxRedirectsHttpException extends HttpException
{
    /**
     * {@inheritdoc}
     */
    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct(509, $message, $previous, [], $code);
    }
}
