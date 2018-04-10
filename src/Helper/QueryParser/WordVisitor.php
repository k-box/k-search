<?php

namespace App\Helper\QueryParser;

use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
use QueryTranslator\Languages\Galach\Values\Node\Term;
use QueryTranslator\Languages\Galach\Values\Token\Word as WordToken;
use QueryTranslator\Values\Node;

final class WordVisitor extends AbstractSolrVisitor
{
    public function __construct(array $domainFieldMap = [], $defaultFieldName = '')
    {
        parent::__construct($domainFieldMap, $defaultFieldName);
    }

    public function accept(Node $node)
    {
        return $node instanceof Term && $node->token instanceof WordToken;
    }

    public function visit(Node $node, Visitor $subVisitor = null, $options = null)
    {
        if (!$node instanceof Term) {
            throw new \LogicException(
                'Implementation accepts instance of Term Node'
            );
        }

        $token = $node->token;

        if (!$token instanceof WordToken) {
            throw new \LogicException(
                'Implementation accepts instance of Word Token'
            );
        }

        $fieldPrefix = $this->getSolrFieldPrefix($token);
        $wordEscaped = $this->escapeWord($token->word);

        return $fieldPrefix.$wordEscaped;
    }

    /**
     * Escape special characters in the given word $string.
     *
     * @param string $string
     *
     * @return string
     */
    protected function escapeWord($string)
    {
        return preg_replace(
            '/(\\+|-|&&|\\|\\||!|\\(|\\)|\\{|}|\\[|]|\\^|"|~|\\*|\\?|:|\\/|\\\\| )/',
            '\\\\$1',
            $string
        );
    }
}
