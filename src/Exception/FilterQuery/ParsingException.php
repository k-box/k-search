<?php

namespace App\Exception\FilterQuery;

use QueryTranslator\Values\Token;

class ParsingException extends FilterQueryException
{
    public static function fromToken(Token $token, string $message): self
    {
        return new self(sprintf('Parsing error near "%s" at position %d%s',
            $token->lexeme,
            $token->position,
            $message ? ': '.$message : ''
        ));
    }
}
