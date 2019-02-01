<?php

namespace App\Model\Data;

use JMS\Serializer\Annotation as JMS;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @SWG\Definition(
 *     definition="Data\AddParams",
 *     required={"data"}
 * )
 */
class AddParams
{
    /**
     * The Data object to be added.
     *
     * @var Data
     * @Assert\NotBlank()
     * @Assert\Valid()
     * @JMS\Type("App\Model\Data\Data")
     * @SWG\Property()
     */
    public $data;

    /**
     * The K-Links to which the data needs to be published.
     *
     * Use the K-Link identifiers
     *
     * @var array
     * @Assert\Type("array")
     * @JMS\Type("array<string>")
     * @JMS\Since("3.7")
     * @SWG\Property(
     *     @SWG\Items(type="string"),
     *     example={"KLINK_ID_1", "KLINK_ID_2"},
     *     x={"since-version":"3.7"},
     * )
     */
    public $klinks = [];

    /**
     * A plain text data with information that will be used for full-text searches to match the given Data.
     *
     * This should only be provided for data representing files which are not supported by the text-extraction system in
     * the KSearch component (such as compressed files, geo files or video files)
     *
     * @var string
     * @JMS\Type("string")
     * @SWG\Property(
     *     property="data_textual_contents"
     * )
     */
    public $dataTextualContents;
}
