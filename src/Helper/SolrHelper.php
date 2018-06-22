<?php

namespace App\Helper;

final class SolrHelper
{
    /**
     * Transforms the given string into a Solr 'key' format.
     *
     * @param string $name
     *
     * @return string
     */
    public static function buildSolrKey(string $name): string
    {
        return str_replace('_', '-', strtolower($name));
    }
}
