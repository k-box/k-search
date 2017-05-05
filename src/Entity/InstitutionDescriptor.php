<?php

namespace App\Entity;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class InstitutionDescription.
 */
class InstitutionDescriptor extends BaseEntity
{
    const ENTITY_TYPE = 'institution';

    /**
     * The Institution ID.
     *
     * @var string
     * @Assert\NotBlank
     * @Assert\Regex(pattern="/^[a-zA-Z0-9-]+$/")
     * @Serializer\Type("string")
     */
    protected $id;

    /**
     * The Institution Name.
     *
     * @var string
     * @Serializer\Type("string")
     * @Assert\NotBlank
     */
    protected $name;

    /**
     * The Institution type, according to Schema.org.
     *
     * @var string
     * @Serializer\Type("string")
     * @Assert\NotBlank
     */
    protected $type;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Assert\NotBlank
     * @Assert\Email
     */
    protected $email;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Assert\NotBlank
     * @Assert\Url
     */
    protected $url;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Assert\NotBlank
     */
    protected $phone;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("address")
     * @Serializer\Since("2.2")
     */
    protected $address;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("addressCountry")
     */
    protected $addressCountry;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("addressZip")
     */
    protected $addressZip;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("addressStreet")
     */
    protected $addressStreet;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("addressLocality")
     */
    protected $addressLocality;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("creationDate")
     * @Assert\NotBlank
     */
    protected $creationDate;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("thumbnailURI")
     * @Assert\NotBlank
     * @Assert\Url
     */
    protected $thumbnailURI;

    /**
     * @param string $id
     */
    public function __construct($id)
    {
        parent::__construct($id);
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        if (empty($this->address)) {
            $address = $this->generateAddressFromComponents();
            if (!empty($address)) {
                $this->address = $address;
            }
        }

        return $this->address;
    }

    /**
     * @return string
     */
    public function generateAddressFromComponents()
    {
        return trim(implode("\n", [
            $this->getAddressStreet(),
            $this->getAddressZip().' '.$this->getAddressLocality(),
            $this->getAddressCountry(),
        ]));
    }

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getAddressCountry()
    {
        return $this->addressCountry;
    }

    /**
     * @param string $addressCountry
     */
    public function setAddressCountry($addressCountry)
    {
        $this->addressCountry = $addressCountry;
    }

    /**
     * @return string
     */
    public function getAddressLocality()
    {
        return $this->addressLocality;
    }

    /**
     * @param string $addressLocality
     */
    public function setAddressLocality($addressLocality)
    {
        $this->addressLocality = $addressLocality;
    }

    /**
     * @return string
     */
    public function getAddressStreet()
    {
        return $this->addressStreet;
    }

    /**
     * @param string $addressStreet
     */
    public function setAddressStreet($addressStreet)
    {
        $this->addressStreet = $addressStreet;
    }

    /**
     * @return string
     */
    public function getAddressZip()
    {
        return $this->addressZip;
    }

    /**
     * @param string $addressZip
     */
    public function setAddressZip($addressZip)
    {
        $this->addressZip = $addressZip;
    }

    /**
     * @return string
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param string $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return string
     */
    public function getThumbnailURI()
    {
        return $this->thumbnailURI;
    }

    /**
     * @param string $thumbnailURI
     */
    public function setThumbnailURI($thumbnailURI)
    {
        $this->thumbnailURI = $thumbnailURI;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }
}
