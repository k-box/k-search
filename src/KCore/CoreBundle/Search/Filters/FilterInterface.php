<?php

namespace KCore\CoreBundle\Search\Filters;

interface FilterInterface
{
    public function getField();

    public function setQuery($query, $bind = null);

    public function getKey();

    public function setDefaultQueryWithValue($value);
}