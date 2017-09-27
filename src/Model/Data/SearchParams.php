<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\SearchParams",
 *     required={"search", "filters"}
 * )
 */
class SearchParams
{
    /**
     * URI encoded string of the search query. If no query is specified, an empty result set will be returned.
     *
     * @var string
     * @Assert\NotBlank()
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="Sherlock Holmes"
     * )
     */
    public $search;

    /**
     * Search filters in the [Lucene query parser syntax](https://lucene.apache.org/core/2_9_4/queryparsersyntax.html).
     *
     * @var string
     * @JMS\Type("string")
     * @SWG\Property(
     *     example="language:en AND created_at:[""2008-07-28T14:47:31Z"" TO NOW] AND updated_at:[""2008-07-28T14:47:31Z"" TO NOW] AND size:[717589 TO 717591] copyright_owner_name:""KLink Organization"" AND copyright_usage_short:""MPL-2.0"""
     * )
     */
    public $filters = '';

    /**
     * An object containing the aggregations to be retrieved, keyed by the Aggregation field name.
     *
     * @var Aggregation[]
     * @Assert\Valid()
     * @JMS\Type("array<string,App\Model\Data\Aggregation>")
     * @SWG\Property(
     *      example={
     *          "language": {
     *              "limit": 5,
     *              "counts_filtered": false
     *           },
     *          "copyright_usage_short": {
     *              "limit": 4,
     *              "counts_filtered": true
     *          }
     *     }
     * )
     */
    public $aggregations = [];

    /**
     * Specify the number of results to retrieve. If no value is given the default value of 10 is used.
     *
     * @var int
     * @JMS\Type("integer")
     * @Assert\Type("integer")
     * @Assert\Range(
     *     min=1,
     *     max=50,
     * )
     * @SWG\Property(
     *     type="integer",
     *     maximum=100,
     *     minimum=1,
     *     default=10,
     *     example=10,
     * )
     */
    public $limit = 10;

    /**
     * Specify the first result to return from the complete set of retrieved documents, the value is 0-based.
     * If no value is given the default value of 0 is used.
     *
     * @var int
     * @JMS\Type("integer")
     * @Assert\Type("integer")
     * @Assert\Range(
     *     min=0,
     * )
     * @SWG\Property(
     *     type="integer",
     *     minimum=0,
     *     default=0,
     * )
     */
    public $offset = 0;
}
