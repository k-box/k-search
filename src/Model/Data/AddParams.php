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
     * A plain text data with information that will be used for full-text searches to match the given Data.
     *
     * This should only be provided for data representing files which are not supported by the text-extraction system in
     * the KSearch component (such as compressed files, geo files or video files)
     *
     * @var string
     * @JMS\Type("string")
     * @SWG\Property()
     */
    public $dataTextualContents;
}
