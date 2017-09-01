<?php

namespace App\Tests\Entity;

use App\Entity\SolrEntity;
use App\Entity\SolrEntityData;
use App\Tests\Helper\ModelHelper;
use PHPUnit\Framework\TestCase;

class SolrEntityDataTest extends TestCase
{
    const SAMPLE_UUID = '123';

    public function testItBuildsFromModel()
    {
        $data = ModelHelper::createDataModel(self::SAMPLE_UUID);
        $entity = SolrEntityData::buildFromModel($data);

        $this->assertEquals($entity->getField(SolrEntity::FIELD_ENTITY_ID), self::SAMPLE_UUID);
        $this->assertEquals($entity->getField(SolrEntity::FIELD_ENTITY_TYPE), SolrEntityData::getEntityType());
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_UUID), self::SAMPLE_UUID);
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_HASH), $data->hash);
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_TYPE), $data->type);
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_URL), $data->url);
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_STATUS), $data->status);
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_COPYRIGHT_STORED), json_encode($data->copyright));
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_COPYRIGHT_OWNER_CONTACT), $data->copyright->owner->contact);
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_COPYRIGHT_OWNER_EMAIL), $data->copyright->owner->email);
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_COPYRIGHT_OWNER_NAME), $data->copyright->owner->name);
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_COPYRIGHT_USAGE_NAME), $data->copyright->usage->name);
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_COPYRIGHT_USAGE_SHORT), $data->copyright->usage->short);
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_COPYRIGHT_USAGE_REFERENCE), $data->copyright->usage->reference);
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_PROPERTIES_STORED), json_encode($data->properties));
    }

    public function testItBuildsAModel()
    {
        $data = ModelHelper::createDataModel(self::SAMPLE_UUID);
        $entity = SolrEntityData::buildFromModel($data);

        $dataBuilt = $entity->buildModel();

        $this->assertEquals($data, $dataBuilt);
    }
}
