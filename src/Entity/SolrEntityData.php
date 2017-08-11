<?php

namespace App\Entity;

use App\Model\Data\Copyright;
use App\Model\Data\CopyrightOwner;
use App\Model\Data\CopyrightUsage;
use App\Model\Data\Data;

/**
 * Entity for Data.
 */
class SolrEntityData extends SolrEntity
{
    protected const FIELD_HASH = 'str_ss_data_hash';
    protected const FIELD_UUID = 'str_ss_data_uuid';
    protected const FIELD_TYPE = 'str_ss_data_type';
    protected const FIELD_URL = 'str_ss_data_url';
    protected const FIELD_COPYRIGHT_STORED = 'str_ss_data_copyright';

    protected const FIELD_COPYRIGHT_OWNER_NAME = 'str_si_data_copyright_owner_name';
    protected const FIELD_COPYRIGHT_OWNER_CONTACT = 'str_si_data_copyright_owner_contact';
    protected const FIELD_COPYRIGHT_OWNER_EMAIL = 'str_si_data_copyright_owner_email';

    protected const FIELD_COPYRIGHT_USAGE_NAME = 'str_si_data_copyright_owner_name';
    protected const FIELD_COPYRIGHT_USAGE_SHORT = 'str_si_data_copyright_owner_short';
    protected const FIELD_COPYRIGHT_USAGE_REFERENCE = 'str_si_data_copyright_owner_reference';

    public static function getEntityType(): string
    {
        return 'data';
    }

    public static function buildFromModel(Data $data): SolrEntityData
    {
        $doc = new self($data->uuid);

        $doc->addField(self::FIELD_UUID, $data->uuid);
        $doc->addField(self::FIELD_HASH, $data->hash);
        $doc->addField(self::FIELD_TYPE, $data->type);
        $doc->addField(self::FIELD_URL, $data->url);

        // Specific sub-entity handling
        $doc->addCopyright($data->copyright);

        return $doc;
    }

    public function buildModel(): Data
    {
        $data = new Data();
        $data->hash = $this->getField(self::FIELD_HASH);
        $data->uuid = $this->getField(self::FIELD_UUID);
        $data->type = $this->getField(self::FIELD_TYPE);
        $data->url = $this->getField(self::FIELD_URL);

        $data->copyright = $this->buildCopyrightModel();

        return $data;
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

    private function inflateModelWithData($model, array $fields, array $data)
    {
        foreach ($fields as $field) {
            $model->{$field} = $data[$field] ?? null;
        }
    }
}
