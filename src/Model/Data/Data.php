<?php

namespace App\Model\Data;

use Swagger\Annotations as SWG;

/**
 * @SWG\Definition(
 *     definition="Data",
 *     required={"uuid", "application_id"}
 * )
 */
class Data
{
    /**
     * The UUID of this Data.
     *
     * @var string
     * @SWG\Property(readOnly=true)
     */
    public $uuid;

    /**
     * ID of the uploaded Application.
     *
     * @var string
     * @SWG\Property()
     */
    public $applicationId;

    /**
     * The region where the order will be shipped to, used to retrieve the correct price of products.
     *
     * @var string
     * @SWG\Property()
     */
    public $author;
}
