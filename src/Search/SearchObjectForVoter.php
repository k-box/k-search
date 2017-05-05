<?php

namespace App\Search;

class SearchObjectForVoter
{
    protected $visibility;

    public function __construct($visibility)
    {
        $this->visibility = $visibility;
    }

    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;
    }

    public function getVisibility()
    {
        return $this->visibility;
    }
}
