<?php

namespace App\Entity;

use App\Helper\DataHelper;
use App\Model\Data\Copyright;
use App\Model\Data\CopyrightOwner;
use App\Model\Data\CopyrightUsage;
use App\Model\Data\Data;
use App\Model\Data\Properties;

/**
 * Entity for Data.
 */
class SolrEntityData extends SolrEntity
{
    public const FIELD_HASH = 'str_ss_data_hash';
    public const FIELD_STATUS = 'str_ss_data_status';
    public const FIELD_UUID = 'str_ss_data_uuid';
    public const FIELD_TYPE = 'str_ss_data_type';
    public const FIELD_URL = 'str_ss_data_url';
    public const FIELD_COPYRIGHT_STORED = 'str_ss_data_copyright';
    public const FIELD_PROPERTIES_STORED = 'str_ss_data_properties';

    public const FIELD_CONTENTS = 'text_data_contents';

    public const FIELD_COPYRIGHT_OWNER_NAME = 'str_sis_data_copyright_owner_name';
    public const FIELD_COPYRIGHT_OWNER_CONTACT = 'str_sis_data_copyright_owner_contact';
    public const FIELD_COPYRIGHT_OWNER_EMAIL = 'str_sis_data_copyright_owner_email';

    public const FIELD_COPYRIGHT_USAGE_NAME = 'str_sis_data_copyright_usage_name';
    public const FIELD_COPYRIGHT_USAGE_SHORT = 'str_sis_data_copyright_usage_short';
    public const FIELD_COPYRIGHT_USAGE_REFERENCE = 'str_sis_data_copyright_usage_reference';

    public const FIELD_INDEXABLE_ABSTRACT = 'str_si_data_abstract';
    public const FIELD_INDEXABLE_TITLE = 'str_si_data_title';
    public const FIELD_INDEXABLE_LANGUAGE = 'str_si_data_language';
    public const FIELD_INDEXABLE_CREATED_AT = 'date_data_created_at';
    public const FIELD_INDEXABLE_UPDATED_AT = 'date_data_updated_at';
    public const FIELD_INDEXABLE_SIZE = 'int_ii_data_size';

    public static function getEntityType(): string
    {
        return 'data';
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

        $doc->addIndexableFields($data);

        return $doc;
    }

    public function buildModel(): Data
    {
        $data = new Data();
        $data->hash = $this->getField(self::FIELD_HASH);
        $data->uuid = $this->getField(self::FIELD_UUID);
        $data->type = $this->getField(self::FIELD_TYPE);
        $data->url = $this->getField(self::FIELD_URL);
        $data->status = $this->getField(self::FIELD_STATUS);

        $data->copyright = $this->buildCopyrightModel();
        $data->properties = $this->buildPropertiesFromModel();

        return $data;
    }

    public static function getIndexableFields(): array
    {
        return [
            'abstract' => self::FIELD_INDEXABLE_ABSTRACT,
            'title' => self::FIELD_INDEXABLE_TITLE,
            'copyright' => self::FIELD_COPYRIGHT_USAGE_SHORT,
            'uuid' => self::FIELD_ENTITY_ID,
            'language' => self::FIELD_INDEXABLE_LANGUAGE,
            'created_at' => self::FIELD_INDEXABLE_CREATED_AT,
            'updated_at' => self::FIELD_INDEXABLE_UPDATED_AT,
            'size' => self::FIELD_INDEXABLE_SIZE,
            'copyright_owner_name' => self::FIELD_COPYRIGHT_OWNER_NAME,
            'copyright_usage_short' => self::FIELD_COPYRIGHT_USAGE_SHORT,
        ];
    }

    private function addIndexableFields(Data $data)
    {
        $this->addField(self::FIELD_INDEXABLE_ABSTRACT, $data->properties->abstract);
        $this->addField(self::FIELD_INDEXABLE_TITLE, $data->properties->title);
        $this->addField(self::FIELD_INDEXABLE_LANGUAGE, $data->properties->language);
        $this->addField(self::FIELD_INDEXABLE_CREATED_AT, $data->properties->created_at);
        $this->addField(self::FIELD_INDEXABLE_UPDATED_AT, $data->properties->updated_at);
        $this->addField(self::FIELD_INDEXABLE_SIZE, $data->properties->size);
    }

    private function addCopyright(Copyright $copyright)
    {
        $this->addField(self::FIELD_COPYRIGHT_STORED, json_encode($copyright));

        $this->addField(self::FIELD_COPYRIGHT_OWNER_CONTACT, $copyright->owner->contact);
        $this->addField(self::FIELD_COPYRIGHT_OWNER_EMAIL, $copyright->owner->email);
        $this->addField(self::FIELD_COPYRIGHT_OWNER_NAME, $copyright->owner->name);

        $this->addField(self::FIELD_COPYRIGHT_USAGE_NAME, $copyright->usage->name);
        $this->addField(self::FIELD_COPYRIGHT_USAGE_SHORT, $copyright->usage->short);
        $this->addField(self::FIELD_COPYRIGHT_USAGE_REFERENCE, $copyright->usage->reference);
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
        $this->addField(self::FIELD_PROPERTIES_STORED, json_encode($properties));
        // @todo Add all search-able properties to the list of specific fields in the Solr document
    }

    private function buildPropertiesFromModel(): Properties
    {
        $properties = new Properties();
        $json = $this->getField(self::FIELD_PROPERTIES_STORED);
        $data = json_decode($json, true);

        $fields = ['title', 'filename', 'mime_type', 'created_at', 'updated_at', 'size', 'abstract', 'thumbnail', 'language'];

        $this->inflateModelWithData($properties, $fields, $data ?? []);

        $properties->updated_at = DataHelper::createUtcDate($data['updated_at']['date']);
        $properties->created_at = DataHelper::createUtcDate($data['created_at']['date']);

        return $properties;
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
}
