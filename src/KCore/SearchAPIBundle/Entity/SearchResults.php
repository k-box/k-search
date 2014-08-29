<?php
/**
 * Created by PhpStorm.
 * User: Ema
 * Date: 04/11/2014
 * Time: 13:59
 */

namespace KCore\SearchAPIBundle\Entity;

use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use KCore\CoreBundle\Entity\DocumentDescriptor;
use Symfony\Component\Validator\Constraints as Assert;




class SearchResults {

    /**
     * @var integer
     * @Type("integer")
     * @SerializedName("numFound")
     */
    protected $numFound;

    /**
     * @var String
     * @Type("string")
     */
    protected $query;

    /**
     * @var String
     * @Type("string")
     */
    protected $visibility;

    /**
     * @var integer
     * @Type("integer")
     * @SerializedName("numResults")
     */
    protected $numResults;

    /**
     * @var integer
     * @Type("integer")
     * @SerializedName("startResult")
     */
    protected $startResult;

    /**
     * @var integer
     * @Type("integer")
     * @SerializedName("queryTime")
     */
    protected $queryTime;

    /**
     * The list of retrieved results
     * @var DocumentDescriptor
     * @Type("array<KCore\CoreBundle\Entity\DocumentDescriptor>")
     */
    protected $items;

    /**
     * Default Constructor
     * @param string $query
     * @param $visibility
     * @param integer $startResult
     * @param integer $numResults
     */
    function __construct($query, $visibility, $startResult, $numResults) {
        $this->itemCount = 0;
        $this->items = array();
        $this->query = $query;
        $this->visibility = $visibility;
        $this->startResult = $startResult;
        $this->numResults = $numResults;
    }

    /**
     * @Type("integer")
     * @SerializedName("itemCount")
     * @VirtualProperty
     */
    public function getItemCount() {
        return count($this->items);
    }

    /**
     * @return \KCore\CoreBundle\Entity\DocumentDescriptor[]
     */
    public function getItems() {
        return $this->items;
    }

    /**
     * @param DocumentDescriptor[] $items
     */
    public function setItems(array $items) {
        $this->items = $items;
    }

    /**
     * @param DocumentDescriptor $docDesc
     */
    public function addItem(DocumentDescriptor $docDesc) {
        $this->items[] = $docDesc;
    }

    /**
     * @return int
     */
    public function getNumFound() {
        return $this->numFound;
    }

    /**
     * @param int $numFound
     */
    public function setNumFound($numFound) {
        $this->numFound = $numFound;
    }

    /**
     * @return int
     */
    public function getNumResults() {
        return $this->numResults;
    }

    /**
     * @return String
     */
    public function getQuery() {
        return $this->query;
    }

    /**
     * @return int
     */
    public function getQueryTime() {
        return $this->queryTime;
    }

    /**
     * @param int $queryTime
     */
    public function setQueryTime($queryTime) {
        $this->queryTime = $queryTime;
    }

    /**
     * @return int
     */
    public function getStartResult() {
        return $this->startResult;
    }

    /**
     * @return String
     */
    public function getVisibility() {
        return $this->visibility;
    }
}