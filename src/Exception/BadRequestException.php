<?php

namespace App\Exception;

class BadRequestException extends KSearchException
{
    /**
     * @var string[]
     */
    private $errors;

    public function __construct(array $errors = [])
    {
        parent::__construct();
        $this->errors = $errors;
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getErrorsForJsonProperties(): array
    {
        $jsonKeys = [];
        foreach ($this->errors as $errorKey => $errorMessage) {
            $key = strtolower(preg_replace('/[A-Z]/', '_\\0', lcfirst($errorKey)));
            $jsonKeys[$key] = $errorMessage;
        }

        return $jsonKeys;
    }

    public function getErrorsAsString(): string
    {
        $message = '';
        foreach ($this->errors as $errorKey => $errorMessage) {
            $message .= sprintf('%s: %s', $errorKey, $errorMessage).PHP_EOL;
        }

        return substr($message, 0, -strlen(PHP_EOL));
    }
}
