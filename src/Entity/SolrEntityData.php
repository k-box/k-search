<?php

namespace App\Entity;

use App\Helper\DateHelper;
use App\Model\Data\Author;
use App\Model\Data\Copyright;
use App\Model\Data\CopyrightOwner;
use App\Model\Data\CopyrightUsage;
use App\Model\Data\Data;
use App\Model\Data\Klink;
use App\Model\Data\Properties;
use App\Model\Data\Properties\Source;
use App\Model\Data\Properties\Streaming;
use App\Model\Data\Properties\Video;
use App\Model\Data\Uploader;

/**
 * Solr entity for Data model.
 */
class SolrEntityData extends AbstractSolrEntity implements SolrEntityExtractText
{
    public const FIELD_AUTHORS_STORED = 'str_ss_data_author';
    public const FIELD_CONTENTS = 'text_data_contents';
    public const FIELD_COPYRIGHT_OWNER_WEBSITE = 'str_si_data_copyright_owner_website';
    public const FIELD_COPYRIGHT_OWNER_ADDRESS = 'str_si_data_copyright_owner_address';
    public const FIELD_COPYRIGHT_OWNER_EMAIL = 'str_si_data_copyright_owner_email';
    public const FIELD_COPYRIGHT_OWNER_EMAIL_SORTING = 'str_ssl_data_copyright_owner_email';
    public const FIELD_COPYRIGHT_OWNER_NAME = 'str_si_data_copyright_owner_name';
    public const FIELD_COPYRIGHT_OWNER_NAME_SORTING = 'str_ssl_data_copyright_owner_name';
    public const FIELD_COPYRIGHT_STORED = 'str_ss_data_copyright';
    public const FIELD_COPYRIGHT_USAGE_NAME = 'str_si_data_copyright_usage_name';
    public const FIELD_COPYRIGHT_USAGE_REFERENCE = 'str_si_data_copyright_usage_reference';
    public const FIELD_COPYRIGHT_USAGE_SHORT = 'str_si_data_copyright_usage_short';
    public const FIELD_COPYRIGHT_USAGE_SHORT_SORTING = 'str_ssl_data_copyright_usage_short';
    public const FIELD_ERROR_STATUS = 'str_ss_data_error_status';
    public const FIELD_HASH = 'str_sis_data_hash';
    public const FIELD_PROPERTIES_ABSTRACT = 'text_data_abstract';
    public const FIELD_PROPERTIES_COLLECTIONS = 'str_sim_data_collections';
    public const FIELD_PROPERTIES_CREATED_AT = 'date_data_created_at';
    public const FIELD_PROPERTIES_LANGUAGE = 'str_si_data_language';
    public const FIELD_PROPERTIES_MIME_TYPE = 'str_si_data_mime_type';
    public const FIELD_PROPERTIES_SIZE = 'int_iss_data_size';
    public const FIELD_PROPERTIES_STORED = 'str_ss_data_properties';
    public const FIELD_PROPERTIES_TAGS = 'str_sim_data_tags';
    public const FIELD_PROPERTIES_TITLE = 'text_data_title';
    public const FIELD_PROPERTIES_FILENAME = 'filename_data_filename';
    public const FIELD_PROPERTIES_TITLE_SORTING = 'str_ssl_data_title';
    public const FIELD_PROPERTIES_UPDATED_AT = 'date_data_updated_at';
    public const FIELD_STATUS = 'str_sis_data_status';
    public const FIELD_TYPE = 'str_sis_data_type';
    public const FIELD_UPLOADER_APP_URL_SORTING = 'str_ssl_data_uploader_app_url';
    public const FIELD_UPLOADER_NAME = 'str_sis_data_uploader_name';
    public const FIELD_UPLOADER_NAME_SEARCH = 'text_data_uploader_name';
    public const FIELD_UPLOADER_NAME_SORTING = 'str_ssl_data_uploader_name';
    public const FIELD_UPLOADER_ORGANIZATION = 'str_sis_data_uploader_organization';
    public const FIELD_UPLOADER_ORGANIZATION_SEARCH = 'text_data_uploader_organization';
    public const FIELD_UPLOADER_ORGANIZATION_SORTING = 'str_ssl_data_uploader_organization';
    public const FIELD_UPLOADER_STORED = 'str_ss_data_uploader';
    public const FIELD_URL = 'str_sis_data_url';
    public const FIELD_UUID = 'str_sis_data_uuid';
    public const FIELD_REQUEST_ID = 'str_sis_data_internal_request_id';
    public const FIELD_UPDATED_AT = 'date_data_internal_updated_at';
    public const FIELD_GEO_LOCATION = 'geom_data_geo_location';
    public const FIELD_GEO_LOCATION_STORED = 'str_ss_data_geo_location';
    public const FIELD_KLINKS = 'str_sim_data_klinks';

    public static function getEntityType(): string
    {
        return 'data';
    }

    public static function getTextualContentsField(): string
    {
        return self::FIELD_CONTENTS;
    }

    public static function buildFromModel(Data $data): self
    {
        $doc = new self($data->uuid);

        $doc->addField(self::FIELD_UUID, $data->uuid);
        $doc->addField(self::FIELD_HASH, $data->hash);
        $doc->addField(self::FIELD_TYPE, $data->type);
        $doc->addField(self::FIELD_URL, $data->url);
        $doc->addField(self::FIELD_STATUS, $data->status);
        $doc->addField(self::FIELD_ERROR_STATUS, $data->errorStatus);
        $doc->addField(self::FIELD_GEO_LOCATION, $data->geoLocation);
        $doc->addField(self::FIELD_GEO_LOCATION_STORED, $data->geoLocation);
        $doc->addField(self::FIELD_REQUEST_ID, $data->requestId);
        $doc->addField(self::FIELD_UPDATED_AT, DateHelper::formatDate($data->updatedAt));

        // Specific sub-entity handling
        $doc->addCopyright($data->copyright ?? new Copyright());
        $doc->addProperties($data->properties);
        $doc->addAuthors($data->authors);
        $doc->addUploader($data->uploader);
        $doc->addKlinks($data->klink_ids);

        return $doc;
    }

    public function buildModel(): Data
    {
        $data = new Data();
        $data->hash = (string) $this->getField(self::FIELD_HASH);
        $data->uuid = (string) $this->getField(self::FIELD_UUID);
        $data->type = (string) $this->getField(self::FIELD_TYPE);
        $data->url = (string) $this->getField(self::FIELD_URL);
        $data->status = (string) $this->getField(self::FIELD_STATUS);
        $error = $this->getField(self::FIELD_ERROR_STATUS);
        $data->errorStatus = $error ? (string) $error : null;
        $data->geoLocation = (string) $this->getField(self::FIELD_GEO_LOCATION_STORED);
        $data->klink_ids = $this->getField(self::FIELD_KLINKS);

        $data->requestId = (string) $this->getField(self::FIELD_REQUEST_ID);
        if ($dateString = $this->getField(self::FIELD_UPDATED_AT)) {
            $data->updatedAt = DateHelper::createUtcDate($dateString);
        }

        $data->copyright = $this->buildCopyrightModel();
        $data->properties = $this->buildPropertiesModel();
        $data->authors = $this->buildAuthorsModel();
        $data->uploader = $this->buildUploaderModel();
        $data->klinks = $this->buildKlinks();

        return $data;
    }

    public static function getTextSearchFields(): array
    {
        return [
            self::FIELD_PROPERTIES_TITLE.'^3.0',
            self::FIELD_PROPERTIES_FILENAME.'^3.0',
            self::FIELD_PROPERTIES_ABSTRACT.'^1.5',
            self::FIELD_CONTENTS.'^1.0',
        ];
    }

    public static function getTextPhraseSearchFields(): array
    {
        return self::getTextSearchFields();
    }

    public static function getSortingFields(): array
    {
        return [
            // Adding pseudo-field "_score"
            '_score' => 'score',

            // Add regular fields
            'uuid' => self::FIELD_ENTITY_ID,
            'type' => self::FIELD_TYPE,
            'copyright.owner.name' => self::FIELD_COPYRIGHT_OWNER_NAME_SORTING,
            'copyright.owner.email' => self::FIELD_COPYRIGHT_OWNER_EMAIL_SORTING,
            'copyright.usage.short' => self::FIELD_COPYRIGHT_USAGE_SHORT_SORTING,
            'properties.created_at' => self::FIELD_PROPERTIES_CREATED_AT,
            'properties.language' => self::FIELD_PROPERTIES_LANGUAGE,
            'properties.mime_type' => self::FIELD_PROPERTIES_MIME_TYPE,
            'properties.size' => self::FIELD_PROPERTIES_SIZE,
            'properties.title' => self::FIELD_PROPERTIES_TITLE_SORTING,
            'properties.updated_at' => self::FIELD_PROPERTIES_UPDATED_AT,
            'uploader.name' => self::FIELD_UPLOADER_NAME_SORTING,
            'uploader.organization' => self::FIELD_UPLOADER_ORGANIZATION_SORTING,
            'uploader.app_url' => self::FIELD_UPLOADER_APP_URL_SORTING,
            'klink_ids' => self::FIELD_KLINKS,
        ];
    }

    public static function getFilterFields(): array
    {
        return [
            'uuid' => self::FIELD_ENTITY_ID,
            'type' => self::FIELD_TYPE,
            'copyright.owner.name' => self::FIELD_COPYRIGHT_OWNER_NAME,
            'copyright.usage.short' => self::FIELD_COPYRIGHT_USAGE_SHORT,
            'properties.abstract' => self::FIELD_PROPERTIES_ABSTRACT,
            'properties.collections' => self::FIELD_PROPERTIES_COLLECTIONS,
            'properties.created_at' => self::FIELD_PROPERTIES_CREATED_AT,
            'properties.language' => self::FIELD_PROPERTIES_LANGUAGE,
            'properties.mime_type' => self::FIELD_PROPERTIES_MIME_TYPE,
            'properties.size' => self::FIELD_PROPERTIES_SIZE,
            'properties.tags' => self::FIELD_PROPERTIES_TAGS,
            'properties.title' => self::FIELD_PROPERTIES_TITLE,
            'properties.updated_at' => self::FIELD_PROPERTIES_UPDATED_AT,
            'uploader.name' => self::FIELD_UPLOADER_NAME_SEARCH,
            'uploader.organization' => self::FIELD_UPLOADER_ORGANIZATION_SEARCH,
            'klink_ids' => self::FIELD_KLINKS,
        ];
    }

    public static function getAggregationFields(): array
    {
        return array_merge(self::getFilterFields(), [
            // Override the uploader name to aggregate on the not-analyzed field
            'uploader.name' => self::FIELD_UPLOADER_NAME,
            // Override the uploader organization to aggregate on the not-analyzed field
            'uploader.organization' => self::FIELD_UPLOADER_ORGANIZATION,
        ]);
    }

    /**
     * Add the textual contents of the entity.
     * This is a read-only property, used only for indexing, not for retrieving data.
     */
    public function addTextualContents(string $text)
    {
        $this->addField(self::FIELD_CONTENTS, $text);
    }

    private function addCopyright(Copyright $copyright)
    {
        $this->addField(self::FIELD_COPYRIGHT_OWNER_WEBSITE, $copyright->owner->website);
        $this->addField(self::FIELD_COPYRIGHT_OWNER_ADDRESS, $copyright->owner->address);
        $this->addField(self::FIELD_COPYRIGHT_OWNER_EMAIL, $copyright->owner->email);
        $this->addField(self::FIELD_COPYRIGHT_OWNER_EMAIL_SORTING, $copyright->owner->email);
        $this->addField(self::FIELD_COPYRIGHT_OWNER_NAME, $copyright->owner->name);
        $this->addField(self::FIELD_COPYRIGHT_OWNER_NAME_SORTING, $copyright->owner->name);
        $this->addField(self::FIELD_COPYRIGHT_USAGE_NAME, $copyright->usage->name);
        $this->addField(self::FIELD_COPYRIGHT_USAGE_SHORT, $copyright->usage->short);
        $this->addField(self::FIELD_COPYRIGHT_USAGE_SHORT_SORTING, $copyright->usage->short);
        $this->addField(self::FIELD_COPYRIGHT_USAGE_REFERENCE, $copyright->usage->reference);

        // Adding the whole object as JSON
        $this->addField(self::FIELD_COPYRIGHT_STORED, json_encode($copyright));
    }

    /**
     * @param Author[] $authors
     */
    private function addAuthors($authors)
    {
        if (!empty($authors) && \is_array($authors)) {
            $this->addField(self::FIELD_AUTHORS_STORED, json_encode($authors));
        }
    }

    private function addUploader(Uploader $uploader)
    {
        $this->addField(self::FIELD_UPLOADER_APP_URL_SORTING, $uploader->appUrl);
        $this->addField(self::FIELD_UPLOADER_NAME, $uploader->name);
        $this->addField(self::FIELD_UPLOADER_NAME_SEARCH, $uploader->name);
        $this->addField(self::FIELD_UPLOADER_NAME_SORTING, $uploader->name);
        $this->addField(self::FIELD_UPLOADER_ORGANIZATION, $uploader->organization);
        $this->addField(self::FIELD_UPLOADER_ORGANIZATION_SEARCH, $uploader->organization);
        $this->addField(self::FIELD_UPLOADER_ORGANIZATION_SORTING, $uploader->organization);
        $this->addField(self::FIELD_UPLOADER_STORED, json_encode($uploader));
    }

    private function buildCopyrightModel(): Copyright
    {
        $copyright = new Copyright();
        $json = $this->getField(self::FIELD_COPYRIGHT_STORED);
        $data = json_decode($json, true);

        $copyright->owner = $this->buildCopyrightOwnerModel($data['owner'] ?? []);
        $copyright->usage = $this->buildCopyrightUsageModel($data['usage'] ?? []);

        return $copyright;
    }

    private function buildCopyrightOwnerModel(array $data): CopyrightOwner
    {
        $model = new CopyrightOwner();
        $fields = ['name', 'email', 'website', 'address'];
        $this->inflateModelWithData($model, $fields, $data);

        return $model;
    }

    private function buildCopyrightUsageModel(array $data): CopyrightUsage
    {
        $model = new CopyrightUsage();
        $fields = ['name', 'reference', 'short'];
        $this->inflateModelWithData($model, $fields, $data);

        return $model;
    }

    private function addProperties(Properties $properties)
    {
        $this->addField(self::FIELD_PROPERTIES_ABSTRACT, $properties->abstract);
        $this->addField(self::FIELD_PROPERTIES_FILENAME, $properties->filename);
        $this->addField(self::FIELD_PROPERTIES_TITLE, $properties->title);
        $this->addField(self::FIELD_PROPERTIES_TITLE_SORTING, $properties->title);
        $this->addField(self::FIELD_PROPERTIES_LANGUAGE, $properties->language);
        $this->addField(self::FIELD_PROPERTIES_CREATED_AT, DateHelper::formatDate($properties->createdAt));
        $this->addField(self::FIELD_PROPERTIES_UPDATED_AT, DateHelper::formatDate($properties->updatedAt ?? DateHelper::createUtcDate()));
        $this->addField(self::FIELD_PROPERTIES_SIZE, $properties->size);
        $this->addField(self::FIELD_PROPERTIES_COLLECTIONS, $properties->collections);
        $this->addField(self::FIELD_PROPERTIES_TAGS, $properties->tags);
        $this->addField(self::FIELD_PROPERTIES_MIME_TYPE, $properties->mimeType);

        // Adding the whole object as JSON
        $this->addField(self::FIELD_PROPERTIES_STORED, json_encode($properties));
    }

    private function buildPropertiesModel(): Properties
    {
        $properties = new Properties();
        $json = $this->getField(self::FIELD_PROPERTIES_STORED);
        $data = json_decode($json, true);

        $fields = [
            'abstract',
            'collections',
            'filename',
            'language',
            'mimeType',
            'size',
            'tags',
            'thumbnail',
            'title',
        ];

        $this->inflateModelWithData($properties, $fields, $data ?? []);

        if ($dateString = $this->getField(self::FIELD_PROPERTIES_UPDATED_AT)) {
            $properties->updatedAt = DateHelper::createUtcDate($dateString);
        }
        if ($dateString = $this->getField(self::FIELD_PROPERTIES_CREATED_AT)) {
            $properties->createdAt = DateHelper::createUtcDate($dateString);
        }

        if ($data['video'] ?? null) {
            $this->updateVideoProperties($data ?? [], $properties);
        }

        return $properties;
    }

    /**
     * @return Author[]
     */
    private function buildAuthorsModel(): array
    {
        $json = $this->getField(self::FIELD_AUTHORS_STORED);
        $data = json_decode($json, true) ?? [];
        $authors = [];
        foreach ($data as $authorData) {
            $author = new Author();
            $fields = ['name', 'email', 'contact'];
            $this->inflateModelWithData($author, $fields, $authorData);
            $authors[] = $author;
        }

        return $authors;
    }

    private function buildUploaderModel()
    {
        $uploader = new Uploader();
        $json = $this->getField(self::FIELD_UPLOADER_STORED);
        $data = json_decode($json, true);

        $fields = ['name', 'organization', 'url', 'appUrl', 'email'];

        $this->inflateModelWithData($uploader, $fields, $data ?? []);

        return $uploader;
    }

    /**
     * Add the K-Links ids to which this data piece needs to be visible on.
     *
     * @param string[] $ids
     */
    private function addKlinks($ids)
    {
        if (\is_array($ids)) {
            $this->addField(self::FIELD_KLINKS, $ids);
        }
    }

    /**
     * Create the public K-Link list based on the ids of the K-Links on which the data
     * is published.
     *
     * @return Klink[]
     */
    private function buildKlinks(): array
    {
        $klink_ids = $this->getField(self::FIELD_KLINKS);

        if (!$klink_ids) {
            return [];
        }

        if (!$this->klink_resolver) {
            return [];
        }

        $klinks = [];
        foreach ($klink_ids as $id) {
            $klinkData = $this->klink_resolver->getKlink($id);
            if ($klinkData) {
                $klink = new Klink();
                $klink->id = $klinkData->getId();
                $klink->name = $klinkData->getName();
                $klinks[] = $klink;
            }
        }

        return $klinks;
    }

    /**
     * Iterates over the list of fields and set their values in the model from the given data.
     *
     * @param object   $model  The model object to be inflated
     * @param string[] $fields The list of fields to update from the given data
     * @param array    $data   The raw data to be used
     */
    private function inflateModelWithData($model, array $fields, array $data)
    {
        foreach ($fields as $field) {
            $model->{$field} = $data[$field] ?? null;
        }
    }

    /**
     * Updates the given properties with video related data.
     *
     * @param array      $data       The raw data
     * @param Properties $properties The current Properties
     */
    private function updateVideoProperties(array $data, Properties $properties)
    {
        $video = new Video();
        $this->inflateModelWithData($video, ['duration', 'streaming'], $data['video']);

        if ($data['video']['source']) {
            $source = new Source();
            $this->inflateModelWithData($source, ['format', 'resolution', 'bitrate'], $data['video']['source']);
            $video->source = $source;
        }

        if ($data['video']['streaming']) {
            $streamingList = [];
            foreach ($data['video']['streaming'] ?? [] as $streamingData) {
                $streaming = new Streaming();
                $this->inflateModelWithData($streaming, ['type', 'url'], $streamingData);
                $streamingList[] = $streaming;
            }
            $video->streaming = $streamingList;
        }

        $properties->video = $video;
    }
}
