<?php

namespace App\Exception\FilterQuery;

use QueryTranslator\Values\Token;

class ParsingException extends FilterQueryException
{
    public static function fromToken(Token $token): self
    {
        return new self(sprintf('Parsing error near "%s" at position %d',
            $token->lexeme,
            $token->position
        ));
    }
}
