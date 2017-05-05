<?php

namespace App\Model\Data;

use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="DataRequest",
 *     required={"uuid"}
 * )
 */
class DataRequest
{
    /**
     * The UUID assigned to this Data.
     *
     * @var string
     * @SWG\Property(readOnly=true)
     */
    public $uuid;

    /**
     * The region where the order will be shipped to, used to retrieve the correct price of products.
     *
     * @var string
     * @SWG\Property()
     */
    public $author;
}
