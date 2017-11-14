<?php

namespace App\Helper;

use Solarium\QueryType\Select\Query\FilterQuery;

class SolrSearchHelper
{
    const SOLR_ESCAPE_FIELD_CHARS = ['+', '-', '&&', '||', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '\\'];
    const SOLR_ESCAPE_VALUE_CHARS = ['+', '-', '&&', '||', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '?', ':', '\\'];

    const FILTER_SEPARATOR_OR = ',';
    const FILTER_SEPARATOR_AND = '|';

    /**
     * @param array $filters
     *
     * @return FilterQuery[]
     */
    public static function buildFilterQueries(array $filters)
    {
        /** @var FilterQuery[] $queries */
        $queries = [];

        // Invocated as a single filter set
        if (isset($filters['field']) && isset($filters['value'])) {
            $filters = [$filters];
        }

        foreach ($filters as $key => $filter) {
            if (is_array($filter)) {
                $q = new FilterQuery();
                $q->setKey($key);
                $query = self::buildFilterQueryForSingleValue($filter['field'], $filter['value']);
                $q->setQuery($query);
                $queries[] = $q;
            }
        }

        return $queries;
    }

    public static function buildFilterQueryForSingleValue($filterField, $filterValue)
    {
        return self::escapeSolrFieldName($filterField).':'.self::escapeSolrFieldValue($filterValue);
    }

    /**
     * Builds a filter query for a multi-value filter.
     *
     * @param string $filterField The filter field name
     * @param string $filterValue The Filter values, separate multiple values with filter separators
     *
     * @return string
     */
    public static function buildFilterQueryForMultipleValues($filterField, $filterValue)
    {
        if (false !== strpos($filterValue, self::FILTER_SEPARATOR_OR)) {
            $filterValue = self::buildFilterValueForSeparator($filterValue, self::FILTER_SEPARATOR_OR);
        } elseif (false !== strpos($filterValue, self::FILTER_SEPARATOR_AND)) {
            $filterValue = self::buildFilterValueForSeparator($filterValue, self::FILTER_SEPARATOR_AND);
        } else {
            $filterValue = self::escapeSolrFieldValue($filterValue);
        }

        return self::escapeSolrFieldName($filterField).':'.$filterValue;
    }

    /**
     * @param $s
     *
     * @return string
     */
    protected static function escapeSolrFieldName($s)
    {
        return self::escapeSolrString($s, self::SOLR_ESCAPE_FIELD_CHARS);
    }

    /**
     * @param $s
     *
     * @return string
     */
    protected static function escapeSolrFieldValue($s)
    {
        $escaped = self::escapeSolrString($s, self::SOLR_ESCAPE_VALUE_CHARS);
        if (false !== strpos($s, ' ')) {
            $escaped = '"'.$escaped.'"';
        }

        return $escaped;
    }

    /**
     * Helper function to escape a SOLR string, the given chars will be escaped.
     *
     * @param string $text          The text to escape
     * @param array  $charsToEscape A list of chars to escale
     *
     * @return string
     */
    protected static function escapeSolrString($text, array $charsToEscape)
    {
        $stringArray = str_split($text);
        $escapedStringArray = [];
        foreach ($stringArray as $char) {
            if (in_array($char, $charsToEscape, true)) {
                $char = '\\'.$char;
            }
            $escapedStringArray[] = $char;
        }

        return implode('', $escapedStringArray);
    }

    /**
     * @param string $filterValue
     * @param string $separator
     *
     * @return string
     */
    private static function buildFilterValueForSeparator($filterValue, $separator)
    {
        $filterValue = explode($separator, $filterValue);
        $filterValue = array_filter($filterValue, 'trim');

        if (count($filterValue) > 1) {
            $connector = (self::FILTER_SEPARATOR_OR === $separator) ? 'OR' : 'AND';
            $values = [];
            foreach ($filterValue as $v) {
                $values[] = self::escapeSolrFieldValue(trim($v));
            }

            $filterValue = '('.implode(' '.$connector.' ', $values).')';
        } elseif (1 === count($filterValue)) {
            $filterValue = self::escapeSolrFieldValue(trim(reset($filterValue)));
        }

        return $filterValue;
    }
}
