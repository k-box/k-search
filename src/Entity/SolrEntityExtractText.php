<?php

namespace App\Entity;

interface SolrEntityExtractText extends SolrEntity
{
    /**
     * Returns the field where the text-extraction process will put the text.
     *
     * @return string
     */
    public static function getTextualContentsField(): string;
}
