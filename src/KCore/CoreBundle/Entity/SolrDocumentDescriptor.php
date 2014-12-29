<?php

namespace KCore\CoreBundle\Entity;

use Solarium\QueryType\Update\Query\Document\Document as SolrDocument;

class SolrDocumentDescriptor extends BaseSolrEntity {

    const FIELD_DOC_ID               = 'id_document_id';
    const FIELD_DOC_INST_ID          = 'id_document_institution_id';
    const FIELD_DOC_LOCALDOC_ID      = 'id_document_localdoc_id';

    const FIELD_DOC_VISIBILITY       = 'id_document_visibility';
    const FIELD_DOC_HASH             = 'id_document_hash';
    const FIELD_DOC_LANGUAGE         = 'id_language';
    const FIELD_DOC_CREATION_DATE    = 'date_document_creation_date';
    const FIELD_DOC_THUMBNAIL_URI    = 'str_ss_document_thumbnail_uri';

    const FIELD_DOC_URI              = 'str_ss_document_uri';

    const FIELD_DOC_ABSTRACT         = 'str_ss_document_abstract';
    const FIELD_DOC_ABSTRACT_INDEXED = 'text_document_abstract';
    const FIELD_DOC_TITLE            = 'str_ss_document_title';
    const FIELD_DOC_TITLE_INDEXED    = 'text_document_title';
    const FIELD_DOC_CONTENTS         = 'text_document_contents';

    const FIELD_DOC_USER_OWNER       = 'mail_document_user_owner';
    const FIELD_DOC_USER_UPLOADER    = 'mail_document_user_uploader';
    const FIELD_DOC_USER_AUTHOR      = 'mails_document_user_author';

    const FIELD_DOC_MIMETYPE         = 'id_document_mimetype';
    const FIELD_DOC_DOCTYPE          = 'id_document_doctype';

    /**
     * @param BaseEntity $documentDescriptor
     * @return SolrDocument
     */
    public static function buildFromEntity(BaseEntity $documentDescriptor) {
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

            $doc->addField(self::FIELD_DOC_URI, $documentDescriptor->getDocumentURI());
            $doc->addField(self::FIELD_DOC_THUMBNAIL_URI, $documentDescriptor->getThumbnailURI());
            $doc->addField(self::FIELD_DOC_ABSTRACT, $documentDescriptor->getAbstract());
            $doc->addField(self::FIELD_DOC_ABSTRACT_INDEXED, $documentDescriptor->getAbstract());

            $doc->addField(self::FIELD_DOC_USER_OWNER, $documentDescriptor->getUserOwner());
            $doc->addField(self::FIELD_DOC_USER_UPLOADER, $documentDescriptor->getUserUploader());
            foreach($documentDescriptor->getAuthors() as $author) {
                if (!empty($author)) {
                    $doc->addField(self::FIELD_DOC_USER_AUTHOR, $author);
                }
            }

            $doc->addField(self::FIELD_DOC_MIMETYPE, $documentDescriptor->getMimeType());
            $doc->addField(self::FIELD_DOC_DOCTYPE, $documentDescriptor->getDocumentType());
            $doc->addField(self::FIELD_DOC_CONTENTS, $documentDescriptor->getContents());
        }
        return $doc;
    }


    /**
     * Build the DocumentDescriptor from the Solr representation
     * @return DocumentDescriptor
     */
    public function getDocumentDescriptor() {
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

        $docDescr->setDocumentURI($this->getField(self::FIELD_DOC_URI));
        $docDescr->setThumbnailURI($this->getField(self::FIELD_DOC_THUMBNAIL_URI));
        $docDescr->setAbstract($this->getField(self::FIELD_DOC_ABSTRACT));

        $docDescr->setUserOwner($this->getField(self::FIELD_DOC_USER_OWNER));
        $docDescr->setUserUploader($this->getField(self::FIELD_DOC_USER_UPLOADER));
        $docDescr->setAuthors($this->getField(self::FIELD_DOC_USER_AUTHOR));

        $docDescr->setMimeType($this->getField(self::FIELD_DOC_MIMETYPE));
        $docDescr->setDocumentType($this->getField(self::FIELD_DOC_DOCTYPE));

        return $docDescr;
    }
}
