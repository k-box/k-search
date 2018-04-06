<?php

namespace App\Helper\QueryParser;

use App\Exception\FilterQuery\ParsingException;
use App\Exception\FilterQuery\UnknownPropertyException;
use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
use QueryTranslator\Values\Token;

abstract class AbstractSolrVisitor extends Visitor
{
    /**
     * Mapping of token domain to Solr field name.
     *
     * @var array
     */
    protected $domainFieldMap = [];

    /**
     * Solr field name to be used when no mapping for a domain is found.
     *
     * @var string
     */
    protected $defaultFieldName;

    /**
     * @param string[] $domainFieldMap
     * @param string   $defaultFieldName
     */
    public function __construct(array $domainFieldMap, string $defaultFieldName)
    {
        $this->domainFieldMap = $domainFieldMap;
        $this->defaultFieldName = $defaultFieldName;
    }

    /**
     * Return Solr backend field name prefix for the given $token.
     *
     * @param Token $token
     *
     * @throws ParsingException
     * @throws UnknownPropertyException
     *
     * @return string
     */
    protected function getSolrFieldPrefix(Token $token)
    {
        if (!property_exists($token, 'domain')) {
            return '';
        }

        if (!$token->domain) {
            throw ParsingException::fromToken($token);
        }

        if (!array_key_exists($token->domain, $this->domainFieldMap)) {
            throw UnknownPropertyException::fromDomainAndToken($token->domain, $token);
        }

        return $this->domainFieldMap[$token->domain].':';
    }
}
