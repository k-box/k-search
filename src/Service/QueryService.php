<?php

namespace App\Service;

use App\Exception\FilterQuery\FilterQueryException;
use App\Helper\QueryParser\GroupVisitor;
use App\Helper\QueryParser\PhraseVisitor;
use App\Helper\QueryParser\RangeVisitor;
use App\Helper\QueryParser\WordVisitor;
use QueryTranslator\Languages\Galach\Generators;
use QueryTranslator\Languages\Galach\Parser;
use QueryTranslator\Languages\Galach\TokenExtractor\Full;
use QueryTranslator\Languages\Galach\Tokenizer;

class QueryService
{
    /**
     * @var array of Translators
     */
    private $translators = [];

    /**
     * Returns the Solr filter query, from the user input.
     *
     * @param string[] $fieldMappings
     *
     * @throws FilterQueryException
     *
     * @return string
     */
    public function getFilterQuery(string $filterQuery, array $fieldMappings)
    {
        $tokenizer = new Tokenizer(new Full());
        $tokenSequence = $tokenizer->tokenize($filterQuery);

        $parser = new Parser();
        $syntaxTree = $parser->parse($tokenSequence);

        $generator = $this->getTranslator($fieldMappings);

        return $generator->generate($syntaxTree);
    }

    /**
     * Get a translator for the given fields mapping.
     *
     * @param string[] $fieldMappings
     */
    private function getTranslator(array $fieldMappings): Generators\ExtendedDisMax
    {
        $key = md5(serialize(ksort($fieldMappings)));
        if (!array_key_exists($key, $this->translators)) {
            $visitors = [];

            $visitors[] = new Generators\Lucene\Common\Prohibited();
            $visitors[] = new GroupVisitor($fieldMappings);
            $visitors[] = new Generators\Lucene\Common\Mandatory();
            $visitors[] = new Generators\Lucene\Common\LogicalAnd();
            $visitors[] = new Generators\Lucene\Common\LogicalNot();
            $visitors[] = new Generators\Lucene\Common\LogicalOr();
            $visitors[] = new PhraseVisitor($fieldMappings);
            $visitors[] = new Generators\Lucene\Common\Query();
            $visitors[] = new WordVisitor($fieldMappings);
            $visitors[] = new RangeVisitor($fieldMappings);
            $aggregate = new Generators\Common\Aggregate($visitors);

            $this->translators[$key] = new Generators\ExtendedDisMax($aggregate);
        }

        return $this->translators[$key];
    }
}
