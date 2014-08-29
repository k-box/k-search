<?php
/**
 * Created by PhpStorm.
 * User: Ema
 * Date: 05/11/2014
 * Time: 14:08
 */

namespace KCore\SearchAPIBundle\Entity;


use KCore\CoreBundle\Entity\DocumentDescriptor;

class SearchResultItem {

    /**
     * @var float
     * @Type("float")
     */
    protected $score;

    /**
     * @var DocumentDescriptor
     * @Type("KCore\DocumentAPIBundle\Entity\DocumentDescriptor")
     * @SerializedName("documentDescriptor")
     */
    protected $documentDescriptor;

} 