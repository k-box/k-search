<?php

namespace App\Model\Data\Search;

use App\Entity\SolrEntityData;
use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\Search\SortParam",
 *     required={"field"}
 * )
 */
class SortParam
{
    const ORDER_DESC = 'desc';
    const ORDER_ASC = 'asc';

    /**
     * Field to apply the sorting on, check the documentation for the list of available fields.
     *
     * @var string
     * @Assert\NotBlank()
     * @Assert\Choice(callback="getSortableFields")
     * @JMS\Type("string")
     * @SWG\Property()
     */
    public $field;

    /**
     * Field sorting order.
     *
     * @var string
     * @Assert\NotBlank()
     * @Assert\Choice(callback="getSortOrders")
     * @JMS\Type("string")
     * @SWG\Property(
     *     enum={"asc","desc"},
     *     default="desc",
     * )
     */
    public $order = self::ORDER_DESC;

    public static function getSortOrders(): array
    {
        return [
            self::ORDER_ASC,
            self::ORDER_DESC,
        ];
    }

    public static function getSortableFields(): array
    {
        return array_keys(SolrEntityData::getSortingFields());
    }
}
