<?php

namespace KCore\CoreBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;



/**
 * Class DocumentDescriptor
 * @package KCore\DocumentAPIBundle\Entity
 *
 */
class DocumentDescriptor extends BaseEntity {

    const ENTITY_TYPE = 'doc-descriptor';
    const DOCUMENT_VISIBILITY_PUBLIC = 'public';
    const DOCUMENT_VISIBILITY_PRIVATE= 'private';

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("localDocumentID")
     * @Assert\NotBlank
     * @Assert\Regex(pattern="/^[a-zA-Z0-9]+$/")
     */
    protected $localDocumentId;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("institutionID")
     * @Assert\NotBlank
     * @Assert\Regex(pattern="/^[a-zA-Z0-9]+$/")
     */
    protected $institutionId;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Assert\Choice(choices={
     *      DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC,
     *      DocumentDescriptor::DOCUMENT_VISIBILITY_PRIVATE
     * })
     * @Assert\NotBlank
     */
    protected $visibility;

    /**
     * The SHA-2 hash of the Document contents (SHA-512, thus 128 Chars)
     *
     * @var String
     *
     * @Serializer\Type("string")
     * @Assert\NotBlank
     * @Assert\Length(min=128,max=128)
     * @Assert\Type("alnum")
     */
    protected $hash;

    /**
     * The Document title, if available
     * @var string
     * @Serializer\Type("string")
     */
    protected $title;

    /**
     * @var string
     *
     * @Serializer\Exclude
     */
    protected $contents;

    /**
     * The document language code (in RFC ), if empty this value will be set during the Core indexing procedure
     * @var string
     *
     * @Serializer\Type("string")
     * @Assert\Language
     */
    protected $language;

    /**
     * Document creation date
     * @var \DateTime
     * @Serializer\Type("DateTime")
     * @Serializer\SerializedName("creationDate")
     * @Assert\NotBlank
     */
    protected $creationDate;

    /**
     * URI where the document is stored and retrievable
     *
     * @var String
     * @Serializer\Type("string")
     * @Serializer\SerializedName("documentURI")
     * @Assert\NotBlank
     * @Assert\Url
     */
    protected $documentURI;

    /**
     * The URI where the document thumbnail is stored
     *
     * @var String
     * @Serializer\Type("string")
     * @Serializer\SerializedName("thumbnailURI")
     * @Assert\NotBlank
     * @Assert\Url
     */
    protected $thumbnailURI;

    /**
     * The document abstract, if available
     *
     * @var String
     * @Serializer\Type("string")
     */
    protected $abstract;

    /**
     * Document owner, as the responsible of the document and that may be contacted for document info.
     * Field format: "Name Surname <mail@host.com>"
     *
     * @var String
     * @Serializer\Type("string")
     * @Serializer\SerializedName("userOwner")
     * @Assert\NotBlank()
     *
     * @todo: implement the right validator for this field!
     */
    protected $userOwner;


    /**
     * List of document authors with eMail
     * Field format: "Name Surname <mail@host.com>"
     *
     * @var String[]
     * @Serializer\Type("array<string>")
     *
     * @todo: implement the right validator for this field!
     */
    protected $authors;

    /**
     *
     * User that uploaded the document
     * Field format: "Name Surname <mail@host.com>"
     *
     * @var String
     * @Serializer\Type("string")
     * @Serializer\SerializedName("userUploader")
     * @Assert\NotBlank()
     *
     * @todo: implement the right validator for this field!
     */
    protected $userUploader;

    /**
     * MimeType of the document
     *
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("mimeType")
     * @Assert\NotBlank
     */
    protected $mimeType;

    /**
     * Document type (PDF, Document, Presentation, ..)
     *
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("documentType")
     * @Assert\NotBlank
     */
    protected $documentType;

    /**
     * @param String $institutionId
     * @param $localDocumentId
     */
    function __construct($institutionId, $localDocumentId) {
        parent::__construct($this->computeDocumentId($institutionId, $localDocumentId));
        $this->institutionId   = $institutionId;
        $this->localDocumentId = $localDocumentId;
    }

    /**
     * @param $institutionId
     * @param $documentId
     * @return string
     */
    public static function computeDocumentId($institutionId, $documentId) {
        return $institutionId . '-'. $documentId;
    }

    /**
     * @param $id
     * @return array|null
     */
    public static function splitDocumentId($id) {
        $split = explode('-', $id, 2);
        if (count($split) != 2) {
            $split = null;
        }
        return $split;
    }

    /**
     * Gets the current EntityID, computes it if is not been set
     *
     * @return String
     */
    public function getId() {
        if (empty($this->id)) {
            $this->id = $this->computeDocumentId($this->getInstitutionId(), $this->getLocalDocumentId());
        }
        return $this->id;
    }

    /**
     * @return String
     */
    public function getAbstract() {
        return $this->abstract;
    }

    /**
     * @param String $abstract
     */
    public function setAbstract($abstract) {
        $this->abstract = $abstract;
    }

    /**
     * @return \String[]
     */
    public function getAuthors() {
        return $this->authors;
    }

    /**
     * @param \String[] $authors
     */
    public function setAuthors($authors) {
        $this->authors = $authors;
    }

    /**
     * @param $author
     */
    public function addAuthor($author) {
        $this->authors[] = $author;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDate() {
        return $this->creationDate;
    }

    /**
     * @param \DateTime $creationDate
     */
    public function setCreationDate(\DateTime $creationDate) {
        $this->creationDate = $creationDate;
    }

    /**
     * @return string
     */
    public function getDocumentType() {
        return $this->documentType;
    }

    /**
     * @param string $documentType
     */
    public function setDocumentType($documentType) {
        $this->documentType = $documentType;
    }

    /**
     * @return String
     */
    public function getDocumentURI() {
        return $this->documentURI;
    }

    /**
     * @param String $documentURI
     */
    public function setDocumentURI($documentURI) {
        $this->documentURI = $documentURI;
    }

    /**
     * @return String
     */
    public function getHash() {
        return $this->hash;
    }

    /**
     * @param String $hash
     */
    public function setHash($hash) {
        $this->hash = $hash;
    }

    /**
     * @return string
     */
    public function getInstitutionId() {
        return $this->institutionId;
    }

    /**
     * @param string $institutionID
     */
    public function setInstitutionId($institutionID) {
        $this->institutionId = $institutionID;
    }

    /**
     * @return string
     */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language) {
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getMimeType() {
        return $this->mimeType;
    }

    /**
     * @param string $mimeType
     */
    public function setMimeType($mimeType) {
        $this->mimeType = $mimeType;
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
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @return String
     */
    public function getUserOwner() {
        return $this->userOwner;
    }

    /**
     * @param String $userOwner
     */
    public function setUserOwner($userOwner) {
        $this->userOwner = $userOwner;
    }

    /**
     * @return String
     */
    public function getUserUploader() {
        return $this->userUploader;
    }

    /**
     * @param String $userUploader
     */
    public function setUserUploader($userUploader) {
        $this->userUploader = $userUploader;
    }

    /**
     * @return string
     */
    public function getVisibility() {
        return $this->visibility;
    }

    /**
     * @return string
     */
    public function getLocalDocumentId() {
        return $this->localDocumentId;
    }

    /**
     * @param string $localDocumentID
     */
    public function setLocalDocumentId($localDocumentID) {
        $this->localDocumentId = $localDocumentID;
    }

    /**
     * @param string $visibility
     */
    public function setVisibility($visibility) {
        $this->visibility = $visibility;
    }

    /**
     * @return string
     */
    public function getContents() {
        return $this->contents;
    }

    /**
     * @param string $contents
     */
    public function setContents($contents) {
        $this->contents = $contents;
    }



}
