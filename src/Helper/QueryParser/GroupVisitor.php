<?php

namespace App\Helper\QueryParser;

use LogicException;
use QueryTranslator\Languages\Galach\Generators\Common\Visitor;
use QueryTranslator\Languages\Galach\Values\Node\Group as GroupNode;
use QueryTranslator\Values\Node;

/**
 * Group Node Visitor implementation.
 */
final class GroupVisitor extends AbstractSolrVisitor
{
    public function __construct(array $domainFieldMap = [], $defaultFieldName = '')
    {
        parent::__construct($domainFieldMap, $defaultFieldName);
    }

    public function accept(Node $node)
    {
        return $node instanceof GroupNode;
    }

    public function visit(Node $node, Visitor $subVisitor = null, $options = null)
    {
        if (!$node instanceof GroupNode) {
            throw new LogicException(
                'Implementation accepts instance of Group Node'
            );
        }

        if (null === $subVisitor) {
            throw new LogicException('Implementation requires sub-visitor');
        }

        $clauses = [];

        foreach ($node->nodes as $subNode) {
            $clauses[] = $subVisitor->visit($subNode, $subVisitor, $options);
        }

        $fieldPrefix = $this->getSolrFieldPrefix($node->tokenLeft);
        $clauses = implode(' ', $clauses);

        return $fieldPrefix.'('.$clauses.')';
    }
}
