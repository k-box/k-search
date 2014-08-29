<?php

namespace KCore\CoreBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class InstitutionDescription
 * @package KCore\CoreBundle\Entity
 *
 * This class represents the main Institution representation for the REST APIs
 */
class InstitutionDescriptor extends BaseEntity {

    const ENTITY_TYPE = 'institution';

    /**
     * @param String $id
     */
    public function __construct($id) {
        parent::__construct($id);
    }


    /**
     * The EntityID
     * Overriding the BaseEntity $id to export this property by the Serializer
     * 
     * @var string
     * @Serializer\Type("string")
     */
    protected $id;

    /**
     * The Institution Name
     *
     * @var String
     * @Serializer\Type("string")
     * @Assert\NotBlank
     */
    protected $name;

    /**
     * The Institution type, according to Schema.org
     *
     * @var String
     * @Serializer\Type("string")
     * @Assert\NotBlank
     */
    protected $type;

    /**
     * @var String
     * @Serializer\Type("string")
     * @Assert\Email
     * @Assert\NotBlank
     */
    protected $email;

    /**
     * @var String
     * @Serializer\Type("string")
     * @Assert\Url
     * @Assert\NotBlank
     */
    protected $url;

    /**
     * @var String
     * @Serializer\Type("string")
     * @Assert\NotBlank
     */
    protected $phone;

    /**
     * @var String
     * @Serializer\Type("string")
     * @Serializer\SerializedName("addressCountry")
     */
    protected $addressCountry;

    /**
     * @var String
     * @Serializer\Type("string")
     * @Serializer\SerializedName("addressZip")
     */
    protected $addressZip;

    /**
     * @var String
     * @Serializer\Type("string")
     * @Serializer\SerializedName("addressStreet")
     */
    protected $addressStreet;

    /**
     * @var String
     * @Serializer\Type("string")
     * @Serializer\SerializedName("addressLocality")
     */
    protected $addressLocality;

    /**
     * @var String
     * @Serializer\Type("string")
     * @Serializer\SerializedName("creationDate")
     * @Assert\NotBlank
     */
    protected $creationDate;

    /**
     * @var String
     * @Serializer\Type("string")
     * @Serializer\SerializedName("thumbnailURI")
     * @Assert\NotBlank
     * @Assert\Url
     *
     */
    protected $thumbnailURI;

    /**
     * @return String
     */
    public function getAddressCountry() {
        return $this->addressCountry;
    }

    /**
     * @param String $addressCountry
     */
    public function setAddressCountry($addressCountry) {
        $this->addressCountry = $addressCountry;
    }

    /**
     * @return String
     */
    public function getAddressLocality() {
        return $this->addressLocality;
    }

    /**
     * @param String $addressLocality
     */
    public function setAddressLocality($addressLocality) {
        $this->addressLocality = $addressLocality;
    }

    /**
     * @return String
     */
    public function getAddressStreet() {
        return $this->addressStreet;
    }

    /**
     * @param String $addressStreet
     */
    public function setAddressStreet($addressStreet) {
        $this->addressStreet = $addressStreet;
    }

    /**
     * @return String
     */
    public function getAddressZip() {
        return $this->addressZip;
    }

    /**
     * @param String $addressZip
     */
    public function setAddressZip($addressZip) {
        $this->addressZip = $addressZip;
    }

    /**
     * @return String
     */
    public function getCreationDate() {
        return $this->creationDate;
    }

    /**
     * @param String $creationDate
     */
    public function setCreationDate($creationDate) {
        $this->creationDate = $creationDate;
    }

    /**
     * @return String
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * @param String $email
     */
    public function setEmail($email) {
        $this->email = $email;
    }

    /**
     * @return String
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param String $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return String
     */
    public function getPhone() {
        return $this->phone;
    }

    /**
     * @param String $phone
     */
    public function setPhone($phone) {
        $this->phone = $phone;
    }

    /**
     * @return String
     */
    public function getThumbnailURI() {
        return $this->thumbnailURI;
    }

    /**
     * @param String $thumbnailURI
     */
    public function setThumbnailURI($thumbnailURI) {
        $this->thumbnailURI = $thumbnailURI;
    }

    /**
     * @return String
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param String $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return String
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * @param String $url
     */
    public function setUrl($url) {
        $this->url = $url;
    }


}
