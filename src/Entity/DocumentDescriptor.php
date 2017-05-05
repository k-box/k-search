<?php

namespace App\Entity;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class DocumentDescriptor.
 */
class DocumentDescriptor extends BaseEntity
{
    const ENTITY_TYPE = 'doc-descriptor';
    const DOCUMENT_VISIBILITY_PUBLIC = 'public';
    const DOCUMENT_VISIBILITY_PRIVATE = 'private';

    /**
     * The document ID; used to build the global ID of the document in the KLink network.
     *
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("localDocumentID")
     * @Assert\NotBlank
     * @Assert\Regex(pattern="/^[a-zA-Z0-9]+$/")
     */
    protected $localDocumentID;

    /**
     * The institution ID of the document. It is used to build the global ID of the document in the KLink network.
     *
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("institutionID")
     * @Assert\NotBlank
     * @Assert\Regex(pattern="/^[a-zA-Z0-9]+$/")
     */
    protected $institutionID;

    /**
     * The document visibility, either "private" or "public".
     *
     * @var string
     * @Serializer\Type("string")
     * @Assert\Choice(
     *     choices={
     *             DocumentDescriptor::DOCUMENT_VISIBILITY_PUBLIC,
     *             DocumentDescriptor::DOCUMENT_VISIBILITY_PRIVATE
     *             },
     *     strict=true,
     * )
     * @Assert\NotBlank
     */
    protected $visibility;

    /**
     * The SHA-2 hash of the Document contents (SHA-512, thus 128 Chars).
     *
     * @var string
     *
     * @Serializer\Type("string")
     * @Assert\NotBlank
     * @Assert\Length(min=128,max=128)
     * @Assert\Type("alnum")
     */
    protected $hash;

    /**
     * The Document title, if available.
     *
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
     * The document language code (ISO 639-1), if empty this value will be set by the indexing procedure.
     *
     * @var string
     *
     * @Serializer\Type("string")
     * @Assert\Language
     */
    protected $language;

    /**
     * Document creation date.
     *
     * @var \DateTime
     * @Serializer\Type("DateTime")
     * @Serializer\SerializedName("creationDate")
     * @Assert\NotBlank
     */
    protected $creationDate;

    /**
     * Document updated date.
     *
     * @var \DateTime
     * @Serializer\Type("DateTime")
     * @Serializer\SerializedName("updateDate")
     * @Serializer\Since("2.2.5")
     */
    protected $updateDate;

    /**
     * URI where the document is stored and retrievable.
     *
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("documentURI")
     * @Assert\NotBlank
     * @Assert\Url
     */
    protected $documentURI;

    /**
     * The URI where the document thumbnail is stored.
     *
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("thumbnailURI")
     * @Assert\NotBlank
     * @Assert\Url
     */
    protected $thumbnailURI;

    /**
     * The document abstract, if available.
     *
     * @var string
     * @Serializer\Type("string")
     */
    protected $abstract;

    /**
     * Document owner, as the responsible of the document and that may be contacted for document info.
     * Field format: "Name Surname <mail@host.com>".
     *
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("userOwner")
     * @Assert\NotBlank()
     *
     * @todo: implement the right validator for this field!
     */
    protected $userOwner;

    /**
     * List of document authors with eMail
     * Field format: "Name Surname <mail@host.com>".
     *
     * @var string[]
     * @Serializer\Type("array<string>")
     *
     * @todo: implement the right validator for this field!
     */
    protected $authors;

    /**
     * User that uploaded the document
     * Field format: "Name Surname <mail@host.com>".
     *
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("userUploader")
     * @Assert\NotBlank()
     *
     * @todo: implement the right validator for this field!
     */
    protected $userUploader;

    /**
     * MimeType of the document.
     *
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("mimeType")
     * @Assert\NotBlank
     */
    protected $mimeType;

    /**
     * Document type (PDF, Document, Presentation, ..).
     *
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("documentType")
     * @Assert\NotBlank
     */
    protected $documentType;

    /**
     * List of the (overrided) titles of the document, as defined by users.
     *
     * @var string[]
     * @Serializer\Type("array<string>")
     * @Serializer\SerializedName("titleAliases")
     */
    protected $titleAliases;

    /**
     * List of topics contained in the document, if this field is left empty
     * then this value will be set during the indexing process.
     *
     * @var string[]
     * @Serializer\Type("array<string>")
     * @Serializer\SerializedName("topicTerms")
     */
    protected $topicTerms;

    /**
     * The list of folders where the document can be found. Every folder is the
     * “document storage” relative path of the file. The path separator is “/” (slash).

     *
     * @var string[]
     * @Serializer\Type("array<string>")
     * @Serializer\SerializedName("documentFolders")
     *
     * @todo: implement the right validator for this field!
     */
    protected $documentFolders;

    /**
     * The list of projects where the document can be found.
     *
     * @var string[]
     * @Serializer\Type("array<string>")
     * @Serializer\SerializedName("projectIds")
     * @Serializer\Since("2.2")
     */
    protected $projectIds;

    /**
     * The list of groups assigned to the document. Each string in the list is in
     * the form "user_id:group_id" where: group_id is the identifier of the group,
     * and the user_id is the user identifier of the user owning the group
     * (the _ids are internally defined by the DMS).

     *
     * @var string[]
     * @Serializer\Type("array<string>")
     * @Serializer\SerializedName("documentGroups")
     *
     * @todo: implement the right validator for this field!
     */
    protected $documentGroups;

    /**
     * @param string $institutionId
     * @param $localDocumentId
     */
    public function __construct($institutionId, $localDocumentId)
    {
        parent::__construct($this->computeDocumentId($institutionId, $localDocumentId));
        $this->institutionID = $institutionId;
        $this->localDocumentID = $localDocumentId;
    }

    /**
     * @param $institutionId
     * @param $documentId
     *
     * @return string
     */
    public static function computeDocumentId($institutionId, $documentId)
    {
        return $institutionId.'-'.$documentId;
    }

    /**
     * @param $id
     *
     * @return array|null
     */
    public static function splitDocumentId($id)
    {
        $split = explode('-', $id, 2);
        if (count($split) !== 2) {
            $split = null;
        }

        return $split;
    }

    /**
     * Gets the current EntityID, computes it if is not been set.
     *
     * @return string
     */
    public function getId()
    {
        if (empty($this->id)) {
            $this->id = $this->computeDocumentId($this->getInstitutionId(), $this->getLocalDocumentId());
        }

        return $this->id;
    }

    /**
     * @return string
     */
    public function getAbstract()
    {
        return $this->abstract;
    }

    /**
     * @param string $abstract
     */
    public function setAbstract($abstract)
    {
        $this->abstract = $abstract;
    }

    /**
     * @return \String[]
     */
    public function getAuthors()
    {
        return $this->authors;
    }

    /**
     * @param \String[] $authors
     */
    public function setAuthors($authors)
    {
        $this->authors = $authors;
    }

    /**
     * @param $author
     */
    public function addAuthor($author)
    {
        $this->authors[] = $author;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param \DateTime $creationDate
     */
    public function setCreationDate(\DateTime $creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        if (!$this->updateDate) {
            return $this->getCreationDate();
        }

        return $this->updateDate;
    }

    /**
     * @param \DateTime|null $updateDate
     */
    public function setUpdateDate($updateDate)
    {
        $this->updateDate = $updateDate;
    }

    /**
     * @return string
     */
    public function getDocumentType()
    {
        return $this->documentType;
    }

    /**
     * @param string $documentType
     */
    public function setDocumentType($documentType)
    {
        $this->documentType = $documentType;
    }

    /**
     * @return string
     */
    public function getDocumentURI()
    {
        return $this->documentURI;
    }

    /**
     * @param string $documentURI
     */
    public function setDocumentURI($documentURI)
    {
        $this->documentURI = $documentURI;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return string
     */
    public function getInstitutionId()
    {
        return $this->institutionID;
    }

    /**
     * @param string $institutionId
     */
    public function setInstitutionId($institutionId)
    {
        $this->institutionID = $institutionId;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @param string $mimeType
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;
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
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getUserOwner()
    {
        return $this->userOwner;
    }

    /**
     * @param string $userOwner
     */
    public function setUserOwner($userOwner)
    {
        $this->userOwner = $userOwner;
    }

    /**
     * @return string
     */
    public function getUserUploader()
    {
        return $this->userUploader;
    }

    /**
     * @param string $userUploader
     */
    public function setUserUploader($userUploader)
    {
        $this->userUploader = $userUploader;
    }

    /**
     * @return string
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @return bool
     */
    public function isPublicDocument()
    {
        return $this->getVisibility() === self::DOCUMENT_VISIBILITY_PUBLIC;
    }

    /**
     * @return bool
     */
    public function isPrivateDocument()
    {
        return $this->getVisibility() === self::DOCUMENT_VISIBILITY_PRIVATE;
    }

    /**
     * @return string
     */
    public function getLocalDocumentId()
    {
        return $this->localDocumentID;
    }

    /**
     * @param string $localDocumentID
     */
    public function setLocalDocumentId($localDocumentID)
    {
        $this->localDocumentID = $localDocumentID;
    }

    /**
     * @param string $visibility
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;
    }

    /**
     * @return string
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * @param string $contents
     */
    public function setContents($contents)
    {
        $this->contents = $contents;
    }

    /**
     * @return \String[]
     */
    public function getTitleAliases()
    {
        return $this->titleAliases;
    }

    /**
     * @param \String[] $titleAliases
     */
    public function setTitleAliases($titleAliases)
    {
        $this->titleAliases = $titleAliases;
    }

    /**
     * @param $titleAlias
     */
    public function addTitleAlias($titleAlias)
    {
        $this->titleAliases[] = $titleAlias;
    }

    /**
     * @return \String[]
     */
    public function getTopicTerms()
    {
        return $this->topicTerms;
    }

    /**
     * @param \String[] $topicTerms
     */
    public function setTopicTerms($topicTerms)
    {
        $this->topicTerms = $topicTerms;
    }

    /**
     * @param $topicTerm
     */
    public function addTopicTerm($topicTerm)
    {
        $this->topicTerms[] = $topicTerm;
    }

    /**
     * @return \String[]
     */
    public function getDocumentFolders()
    {
        return $this->documentFolders;
    }

    /**
     * @param \String[] $documentFolders
     */
    public function setDocumentFolders($documentFolders)
    {
        $this->documentFolders = $documentFolders;
    }

    /**
     * @param $documentFolder
     */
    public function addDocumentFolder($documentFolder)
    {
        $this->documentFolders[] = $documentFolder;
    }

    /**
     * @return string[]
     */
    public function getProjectIds()
    {
        return $this->projectIds;
    }

    /**
     * @param string $projectId
     */
    public function addProjectId($projectId)
    {
        $this->projectIds[] = $projectId;
    }

    /**
     * @param string[] $projectIds
     */
    public function setProjectIds(array $projectIds)
    {
        $this->projectIds = $projectIds;
    }

    /**
     * @return \String[]
     */
    public function getDocumentGroups()
    {
        return $this->documentGroups;
    }

    /**
     * @param \String[] $documentGroups
     */
    public function setDocumentGroups($documentGroups)
    {
        $this->documentGroups = $documentGroups;
    }

    /**
     * @param $documentGroup
     */
    public function addDocumentGroup($documentGroup)
    {
        $this->documentGroups[] = $documentGroup;
    }
}
