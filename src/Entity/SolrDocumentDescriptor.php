<?php

namespace App\Entity;

use Pnz\GeoJSON\GeoJSONFeature;
use Solarium\QueryType\Update\Query\Document\Document;
use Solarium\QueryType\Update\Query\Document\Document as SolrDocument;

class SolrDocumentDescriptor extends BaseSolrEntity
{
    const FIELD_DOC_ID = 'id_document_id';
    const FIELD_DOC_INST_ID = 'id_document_institution_id';
    const FIELD_DOC_LOCALDOC_ID = 'id_document_localdoc_id';

    const FIELD_DOC_VISIBILITY = 'id_document_visibility';
    const FIELD_DOC_HASH = 'id_document_hash';
    const FIELD_DOC_LANGUAGE = 'id_language';
    const FIELD_DOC_CREATION_DATE = 'date_document_creation_date';
    // Defining the new "date-range" fields, the default "date_" should be removed when all documents will be reindexed.
    const FIELD_DOC_CREATION_DATERANGE = 'daterange_document_creation_date';
    const FIELD_DOC_UPDATE_DATE = 'daterange_document_update_date';
    const FIELD_DOC_THUMBNAIL_URI = 'str_ss_document_thumbnail_uri';

    const FIELD_DOC_URI = 'str_ss_document_uri';

    const FIELD_DOC_ABSTRACT = 'str_ss_document_abstract';
    const FIELD_DOC_ABSTRACT_INDEXED = 'text_document_abstract';
    const FIELD_DOC_TITLE = 'str_ss_document_title';
    const FIELD_DOC_TITLE_INDEXED = 'text_document_title';
    const FIELD_DOC_CONTENTS = 'text_document_contents';

    const FIELD_DOC_USER_OWNER = 'mail_document_user_owner';
    const FIELD_DOC_USER_UPLOADER = 'mail_document_user_uploader';
    const FIELD_DOC_USER_AUTHOR = 'mails_document_user_author';

    const FIELD_DOC_MIMETYPE = 'id_document_mimetype';
    const FIELD_DOC_DOCTYPE = 'id_document_doctype';

    const FIELD_DOC_TITLE_ALIASES = 'str_sm_document_title_aliases';
    const FIELD_DOC_TITLE_ALIASES_INDEXED = 'text_document_title_aliases';

    const FIELD_DOC_LOCATIONS = 'str_sm_document_locations';
    const FIELD_DOC_LOCATION_STRINGS = 'str_sim_document_location_strings';
    const FIELD_DOC_LOCATION_STRINGS_INDEXED = 'text_document_location_strings';

    const FIELD_DOC_TOPIC_TERMS = 'str_sm_document_topic_terms';
    const FIELD_DOC_TOPIC_TERMS_INDEXED = 'text_document_topic_terms';

    const FIELD_DOC_DOCUMENT_FOLDERS = 'str_sim_document_document_folders';
    const FIELD_DOC_PROJECT_IDS = 'str_sim_document_project_ids';
    const FIELD_DOC_DOCUMENT_GROUPS = 'str_sim_document_document_groups';

    /**
     * @param BaseEntity $documentDescriptor
     *
     * @return SolrDocument
     */
    public static function buildFromEntity(BaseEntity $documentDescriptor)
    {
        $doc = parent::buildFromEntity($documentDescriptor);
        if ($documentDescriptor instanceof DocumentDescriptor) {
            $doc->addField(self::FIELD_DOC_ID, $documentDescriptor->getId());
            $doc->addField(self::FIELD_DOC_INST_ID, $documentDescriptor->getInstitutionId());
            $doc->addField(self::FIELD_DOC_LOCALDOC_ID, $documentDescriptor->getLocalDocumentId());
            $doc->addField(self::FIELD_DOC_VISIBILITY, $documentDescriptor->getVisibility());
            $doc->addField(self::FIELD_DOC_HASH, $documentDescriptor->getHash());

            $doc->addField(self::FIELD_DOC_TITLE, $documentDescriptor->getTitle());
            $doc->addField(self::FIELD_DOC_TITLE_INDEXED, $documentDescriptor->getTitle());
            $doc->addField(self::FIELD_DOC_LANGUAGE, $documentDescriptor->getLanguage());
            $creationTime = $documentDescriptor->getCreationDate();

            $doc->addField(self::FIELD_DOC_CREATION_DATE, BaseSolrEntity::convertDateTimeToUTC($creationTime));
            // Added copied field, with Date-Range
            $doc->addField(self::FIELD_DOC_CREATION_DATERANGE, BaseSolrEntity::convertDateTimeToUTC($creationTime));
            $updateDate = $documentDescriptor->getUpdateDate();
            $doc->addField(self::FIELD_DOC_UPDATE_DATE, BaseSolrEntity::convertDateTimeToUTC($updateDate));

            $doc->addField(self::FIELD_DOC_URI, $documentDescriptor->getDocumentURI());
            $doc->addField(self::FIELD_DOC_THUMBNAIL_URI, $documentDescriptor->getThumbnailURI());
            $doc->addField(self::FIELD_DOC_ABSTRACT, $documentDescriptor->getAbstract());
            $doc->addField(self::FIELD_DOC_ABSTRACT_INDEXED, $documentDescriptor->getAbstract());

            $doc->addField(self::FIELD_DOC_USER_OWNER, $documentDescriptor->getUserOwner());
            $doc->addField(self::FIELD_DOC_USER_UPLOADER, $documentDescriptor->getUserUploader());

            $doc->addField(self::FIELD_DOC_MIMETYPE, $documentDescriptor->getMimeType());
            $doc->addField(self::FIELD_DOC_DOCTYPE, $documentDescriptor->getDocumentType());
            $doc->addField(self::FIELD_DOC_CONTENTS, $documentDescriptor->getContents());

            $authors = $documentDescriptor->getAuthors();
            if (is_array($authors) && count($authors) > 0) {
                foreach ($documentDescriptor->getAuthors() as $author) {
                    if (!empty($author)) {
                        $doc->addField(self::FIELD_DOC_USER_AUTHOR, $author);
                    }
                }
            }

            $titleAliases = $documentDescriptor->getTitleAliases();
            if (is_array($titleAliases) && count($titleAliases) > 0) {
                foreach ($titleAliases as $titleAlias) {
                    if (!empty($titleAlias)) {
                        $doc->addField(self::FIELD_DOC_TITLE_ALIASES, $titleAlias);
                        $doc->addField(self::FIELD_DOC_TITLE_ALIASES_INDEXED, $titleAlias);
                    }
                }
            }

            /*
             }
             */
            $locations = $documentDescriptor->getLocations();
            if (is_array($locations) && count($locations) > 0) {
                /** @var GeoJSONFeature $location */
                foreach ($locations as $location) {
                    $doc->addField(self::FIELD_DOC_LOCATIONS, serialize($location));
                    $doc->addField(self::FIELD_DOC_LOCATION_STRINGS, $location->getProperty('name'));
                    $doc->addField(self::FIELD_DOC_LOCATION_STRINGS_INDEXED, $location->getProperty('name'));
                }
            }

            self::addListField(
                $doc,
                [self::FIELD_DOC_TOPIC_TERMS, self::FIELD_DOC_TOPIC_TERMS_INDEXED],
                $documentDescriptor->getTopicTerms()
            );

            self::addListField($doc, self::FIELD_DOC_PROJECT_IDS, $documentDescriptor->getProjectIds());
            self::addListField($doc, self::FIELD_DOC_DOCUMENT_FOLDERS, $documentDescriptor->getDocumentFolders());
            self::addListField($doc, self::FIELD_DOC_DOCUMENT_GROUPS, $documentDescriptor->getDocumentGroups());
        }

        return $doc;
    }

    /**
     * Build the DocumentDescriptor from the Solr representation.
     *
     * @return DocumentDescriptor
     */
    public function getDocumentDescriptor()
    {
        $docDescr = new DocumentDescriptor(
            $this->getField(self::FIELD_DOC_INST_ID),
            $this->getField(self::FIELD_DOC_LOCALDOC_ID)
        );
        $docDescr->setInstitutionId($this->getField(self::FIELD_DOC_INST_ID));
        $docDescr->setLocalDocumentId($this->getField(self::FIELD_DOC_LOCALDOC_ID));
        $docDescr->setVisibility($this->getField(self::FIELD_DOC_VISIBILITY));
        $docDescr->setHash($this->getField(self::FIELD_DOC_HASH));

        $docDescr->setTitle($this->getField(self::FIELD_DOC_TITLE));
        $docDescr->setLanguage($this->getField(self::FIELD_DOC_LANGUAGE));
        $docDescr->setCreationDate(new \DateTime($this->getField(self::FIELD_DOC_CREATION_DATE)));

        $updateDate = $this->getField(self::FIELD_DOC_UPDATE_DATE) ?: $this->getField(self::FIELD_DOC_CREATION_DATE);
        $docDescr->setUpdateDate(new \DateTime($updateDate));

        $docDescr->setDocumentURI($this->getField(self::FIELD_DOC_URI));
        $docDescr->setThumbnailURI($this->getField(self::FIELD_DOC_THUMBNAIL_URI));
        $docDescr->setAbstract($this->getField(self::FIELD_DOC_ABSTRACT));

        $docDescr->setUserOwner($this->getField(self::FIELD_DOC_USER_OWNER));
        $docDescr->setUserUploader($this->getField(self::FIELD_DOC_USER_UPLOADER));
        $docDescr->setAuthors($this->getField(self::FIELD_DOC_USER_AUTHOR));

        $docDescr->setMimeType($this->getField(self::FIELD_DOC_MIMETYPE));
        $docDescr->setDocumentType($this->getField(self::FIELD_DOC_DOCTYPE));

        $docDescr->setTitleAliases($this->getField(self::FIELD_DOC_TITLE_ALIASES));

        $docDescr->setTopicTerms($this->getField(self::FIELD_DOC_TOPIC_TERMS));

        $docDescr->setDocumentFolders($this->getField(self::FIELD_DOC_DOCUMENT_FOLDERS));

        $docDescr->setDocumentGroups($this->getField(self::FIELD_DOC_DOCUMENT_GROUPS));

        $locationsCentroidsStrings = $this->getField(self::FIELD_DOC_LOCATIONS);
        if (is_array($locationsCentroidsStrings)) {
            foreach ($locationsCentroidsStrings as $locationsCentroidsString) {
                $docDescr->addLocation(unserialize($locationsCentroidsString));
            }
        }

        return $docDescr;
    }

    /**
     * @param Document     $doc        The document
     * @param string|array $fieldNames The field name, as array of field names or as a single string
     * @param array        $list       The fields contents, as array
     */
    private static function addListField(Document $doc, $fieldNames, $list)
    {
        if (empty($fieldNames) || empty($list)) {
            return;
        }

        if (!is_array($fieldNames)) {
            $fieldNames = [$fieldNames];
        }

        if (is_array($list) && count($list) > 0) {
            foreach ($list as $item) {
                if (!empty($item)) {
                    foreach ($fieldNames as $fieldName) {
                        $doc->addField($fieldName, $item);
                    }
                }
            }
        }
    }
}
