<?php

namespace App\Tests\Entity;

use App\Entity\AbstractSolrEntity;
use App\Entity\SolrEntityData;
use App\Helper\SolrHelper;
use App\Model\Data\Data;
use App\Model\Data\Properties\Source;
use App\Model\Data\Properties\Streaming;
use App\Model\Data\Properties\Video;
use App\Tests\Helper\ModelHelper;
use PHPUnit\Framework\TestCase;

class SolrEntityDataTest extends TestCase
{
    const SAMPLE_UUID = '123';

    public function testItBuildsADocumentFromModel()
    {
        $data = ModelHelper::createDataModel(self::SAMPLE_UUID);
        $entity = SolrEntityData::buildFromModel($data);

        $this->assertSavedFields($entity, $data);
    }

    public function testItBuildsADocumentModel()
    {
        $data = ModelHelper::createDataModel(self::SAMPLE_UUID);
        $entity = SolrEntityData::buildFromModel($data);

        $dataBuilt = $entity->buildModel();

        $this->assertEquals($data, $dataBuilt);
    }

    public function testItBuildsAVideoFromModel()
    {
        $data = ModelHelper::createDataModel(self::SAMPLE_UUID);

        $data->type = 'video';

        $data->properties->video = new Video();
        $streaming = new Streaming();
        $streaming->type = 'type_s1';
        $streaming->url = 'https://www.youtube.com/watch?v=iEueWyu0TXA';
        $data->properties->video->streaming = [$streaming];

        $data->properties->video->duration = '10 min';
        $data->properties->video->source = new Source();
        $data->properties->video->source->resolution = '1080';
        $data->properties->video->source->format = 'format';
        $data->properties->video->source->bitrate = 'bitrate';

        $entity = SolrEntityData::buildFromModel($data);

        $this->assertSavedFields($entity, $data);

        $this->assertEquals($entity->getField(AbstractSolrEntity::FIELD_ENTITY_ID), self::SAMPLE_UUID);
        $this->assertEquals($entity->getField(AbstractSolrEntity::FIELD_ENTITY_TYPE), SolrEntityData::getEntityType());
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
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_PROPERTIES_COLLECTIONS), $data->properties->collections);
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_PROPERTIES_TAGS), $data->properties->tags);
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_PROPERTIES_MIME_TYPE), $data->properties->mimeType);
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_AUTHOR_STORED), json_encode($data->author));
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_UPLOADER_STORED), json_encode($data->uploader));
    }

    public function testItBuildsAVideoModel()
    {
        $data = ModelHelper::createDataModel(self::SAMPLE_UUID);

        $data->type = 'video';

        $data->properties->video = new Video();
        $streaming = new Streaming();
        $streaming->type = 'type_s1';
        $streaming->url = 'https://www.youtube.com/watch?v=iEueWyu0TXA';
        $data->properties->video->streaming = [$streaming];

        $data->properties->video->duration = '10 min';
        $data->properties->video->source = new Source();
        $data->properties->video->source->resolution = '1080';
        $data->properties->video->source->format = 'format';
        $data->properties->video->source->bitrate = 'bitrate';

        $entity = SolrEntityData::buildFromModel($data);

        $dataBuilt = $entity->buildModel();

        $this->assertEquals($data, $dataBuilt);
    }

    private function assertSavedFields(SolrEntityData $entity, Data $data)
    {
        $this->assertEquals($entity->getField(AbstractSolrEntity::FIELD_ENTITY_ID), self::SAMPLE_UUID);
        $this->assertEquals($entity->getField(AbstractSolrEntity::FIELD_ENTITY_TYPE), SolrEntityData::getEntityType());
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
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_PROPERTIES_COLLECTIONS), $data->properties->collections);
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_PROPERTIES_TAGS), $data->properties->tags);
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_PROPERTIES_MIME_TYPE), $data->properties->mimeType);
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_AUTHOR_STORED), json_encode($data->author));
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_UPLOADER_STORED), json_encode($data->uploader));
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_PROPERTIES_CREATED_AT), $data->properties->createdAt->format(SolrHelper::DATE_FORMAT));
        $this->assertEquals($entity->getField(SolrEntityData::FIELD_PROPERTIES_UPDATED_AT), $data->properties->updatedAt->format(SolrHelper::DATE_FORMAT));
    }
}
