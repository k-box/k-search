<?php

namespace App\Model\Search;

use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     required={"score", "data", "data-type"}
 * )
 */
class SearchItem
{
    /**
     * The search score of the Item.
     *
     * @var string
     * @SWG\Property(readOnly=true)
     */
    public $score;

    /**
     * @var string
     * @SWG\Property()
     */
    public $dataType;

    /**
     * @var Data
     *
     * @SWG\Property()
     */
    public $data;
}
