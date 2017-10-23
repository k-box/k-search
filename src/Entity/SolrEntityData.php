<?php

namespace App\Entity;

use App\Helper\SolrHelper;
use App\Model\Data\Author;
use App\Model\Data\Copyright;
use App\Model\Data\CopyrightOwner;
use App\Model\Data\CopyrightUsage;
use App\Model\Data\Data;
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
    public const FIELD_HASH = 'str_sis_data_hash';
    public const FIELD_STATUS = 'str_sis_data_status';
    public const FIELD_UUID = 'str_sis_data_uuid';
    public const FIELD_TYPE = 'str_sis_data_type';
    public const FIELD_URL = 'str_sis_data_url';
    public const FIELD_COPYRIGHT_STORED = 'str_ss_data_copyright';
    public const FIELD_PROPERTIES_STORED = 'str_ss_data_properties';
    public const FIELD_AUTHOR_STORED = 'str_ss_data_author';
    public const FIELD_UPLOADER_STORED = 'str_ss_data_uploader';

    public const FIELD_CONTENTS = 'text_data_contents';
    public const FIELD_COPYRIGHT_OWNER_NAME = 'str_si_data_copyright_owner_name';

    public const FIELD_COPYRIGHT_OWNER_CONTACT = 'str_sis_data_copyright_owner_contact';
    public const FIELD_COPYRIGHT_OWNER_EMAIL = 'str_si_data_copyright_owner_email';
    public const FIELD_COPYRIGHT_USAGE_NAME = 'str_si_data_copyright_usage_name';

    public const FIELD_COPYRIGHT_USAGE_SHORT = 'str_si_data_copyright_usage_short';
    public const FIELD_COPYRIGHT_USAGE_REFERENCE = 'str_si_data_copyright_usage_reference';
    public const FIELD_PROPERTIES_ABSTRACT = 'text_data_abstract';
    public const FIELD_PROPERTIES_TITLE = 'text_data_title';
    public const FIELD_PROPERTIES_LANGUAGE = 'str_si_data_language';
    public const FIELD_PROPERTIES_CREATED_AT = 'date_data_created_at';
    public const FIELD_PROPERTIES_UPDATED_AT = 'date_data_updated_at';
    public const FIELD_PROPERTIES_SIZE = 'int_ii_data_size';
    public const FIELD_PROPERTIES_COLLECTION = 'str_sim_data_collection';
    public const FIELD_PROPERTIES_TAGS = 'str_sim_data_tags';
    public const FIELD_PROPERTIES_MIME_TYPE = 'str_sim_data_mime_type';

    public static function getEntityType(): string
    {
        return 'data';
    }

    public static function getTextualContentsField(): string
    {
        return self::FIELD_CONTENTS;
    }

    public static function buildFromModel(Data $data): SolrEntityData
    {
        $doc = new self($data->uuid);

        $doc->addField(self::FIELD_UUID, $data->uuid);
        $doc->addField(self::FIELD_HASH, $data->hash);
        $doc->addField(self::FIELD_TYPE, $data->type);
        $doc->addField(self::FIELD_URL, $data->url);
        $doc->addField(self::FIELD_STATUS, $data->status);

        // Specific sub-entity handling
        $doc->addCopyright($data->copyright);
        $doc->addProperties($data->properties);
        $doc->addAuthor($data->author);
        $doc->addUploader($data->uploader);

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

        $data->copyright = $this->buildCopyrightModel();
        $data->properties = $this->buildPropertiesModel();
        $data->author = $this->buildAuthorModel();
        $data->uploader = $this->buildUploaderModel();

        return $data;
    }

    public static function getModelPropertyToFieldMappings(): array
    {
        return [
            'uuid' => self::FIELD_ENTITY_ID,
            'type' => self::FIELD_TYPE,
            'properties.language' => self::FIELD_PROPERTIES_LANGUAGE,
            'properties.created_at' => self::FIELD_PROPERTIES_CREATED_AT,
            'properties.updated_at' => self::FIELD_PROPERTIES_UPDATED_AT,
            'properties.size' => self::FIELD_PROPERTIES_SIZE,
            'properties.abstract' => self::FIELD_PROPERTIES_ABSTRACT,
            'properties.title' => self::FIELD_PROPERTIES_TITLE,
            'properties.collection' => self::FIELD_PROPERTIES_COLLECTION,
            'properties.tags' => self::FIELD_PROPERTIES_TAGS,
            'properties.mime_type' => self::FIELD_PROPERTIES_MIME_TYPE,
            'copyright.owner.name' => self::FIELD_COPYRIGHT_OWNER_NAME,
            'copyright.usage.short' => self::FIELD_COPYRIGHT_USAGE_SHORT,
        ];
    }

    public static function getTextSearchFields(): array
    {
        return [
            self::FIELD_CONTENTS,
            self::FIELD_PROPERTIES_TITLE,
            self::FIELD_PROPERTIES_ABSTRACT,
        ];
    }

    public static function getFilterFields(): array
    {
        return [
            self::FIELD_ENTITY_ID,
            self::FIELD_TYPE,
            self::FIELD_PROPERTIES_LANGUAGE,
            self::FIELD_PROPERTIES_CREATED_AT,
            self::FIELD_PROPERTIES_UPDATED_AT,
            self::FIELD_PROPERTIES_SIZE,
            self::FIELD_PROPERTIES_COLLECTION,
            self::FIELD_PROPERTIES_TAGS,
            self::FIELD_PROPERTIES_MIME_TYPE,
            self::FIELD_COPYRIGHT_OWNER_NAME,
            self::FIELD_COPYRIGHT_USAGE_SHORT,
        ];
    }

    public static function getAggregationFields(): array
    {
        return self::getFilterFields();
    }

    /**
     * Add the textual contents of the entity.
     * This is a read-only property, used only for indexing, not for retrieving data.
     *
     * @param string $text
     */
    public function addTextualContents(string $text)
    {
        $this->addField(self::FIELD_CONTENTS, $text);
    }

    private function addCopyright(Copyright $copyright)
    {
        $this->addField(self::FIELD_COPYRIGHT_OWNER_CONTACT, $copyright->owner->contact);
        $this->addField(self::FIELD_COPYRIGHT_OWNER_EMAIL, $copyright->owner->email);
        $this->addField(self::FIELD_COPYRIGHT_OWNER_NAME, $copyright->owner->name);
        $this->addField(self::FIELD_COPYRIGHT_USAGE_NAME, $copyright->usage->name);
        $this->addField(self::FIELD_COPYRIGHT_USAGE_SHORT, $copyright->usage->short);
        $this->addField(self::FIELD_COPYRIGHT_USAGE_REFERENCE, $copyright->usage->reference);

        // Adding the whole object as JSON
        $this->addField(self::FIELD_COPYRIGHT_STORED, json_encode($copyright));
    }

    /**
     * @param Author[] $author
     */
    private function addAuthor(array $author)
    {
        $this->addField(self::FIELD_AUTHOR_STORED, json_encode($author));
    }

    /**
     * @param Uploader $uploader
     */
    private function addUploader(Uploader $uploader)
    {
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
        $fields = ['name', 'contact', 'email'];
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
        $this->addField(self::FIELD_PROPERTIES_TITLE, $properties->title);
        $this->addField(self::FIELD_PROPERTIES_LANGUAGE, $properties->language);
        $this->addField(self::FIELD_PROPERTIES_CREATED_AT, $properties->createdAt);
        $this->addField(self::FIELD_PROPERTIES_UPDATED_AT, $properties->updatedAt);
        $this->addField(self::FIELD_PROPERTIES_SIZE, $properties->size);
        $this->addField(self::FIELD_PROPERTIES_COLLECTION, $properties->collection);
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
            'collection',
            'filename',
            'language',
            'mimeType',
            'size',
            'tags',
            'thumbnail',
            'title',
            'createdAt',
            'updatedAt',
        ];

        $this->inflateModelWithData($properties, $fields, $data ?? []);

        $properties->updatedAt = SolrHelper::createUtcDate($data['updatedAt']['date']);
        $properties->createdAt = SolrHelper::createUtcDate($data['createdAt']['date']);

        $this->updateVideoProperties($data, $properties);

        return $properties;
    }

    /**
     * @return Author[]
     */
    private function buildAuthorModel(): array
    {
        $json = $this->getField(self::FIELD_AUTHOR_STORED);
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

        $fields = ['name', 'url', 'appUrl', 'email'];

        $this->inflateModelWithData($uploader, $fields, $data ?? []);

        return $uploader;
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
        if (isset($data['video'])) {
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
}
