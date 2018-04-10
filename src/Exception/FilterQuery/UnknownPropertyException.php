<?php

namespace App\Exception\FilterQuery;

use QueryTranslator\Values\Token;

class UnknownPropertyException extends FilterQueryException
{
    public static function fromDomainAndToken(string $domain, Token $token): self
    {
        return new self(sprintf('Unknown property "%s" near "%s" at position %d',
            $domain,
            $token->lexeme,
            $token->position
        ));
    }
}
