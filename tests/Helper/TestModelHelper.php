<?php

namespace App\Tests\Helper;

use App\Helper\DateHelper;
use App\Model\Data\Author;
use App\Model\Data\Copyright;
use App\Model\Data\CopyrightOwner;
use App\Model\Data\CopyrightUsage;
use App\Model\Data\Data;
use App\Model\Data\Properties;
use App\Model\Data\Search\Aggregation;
use App\Model\Data\Search\SearchParams;
use App\Model\Data\Search\SortParam;
use App\Model\Data\Uploader;
use App\Model\Data\Klink;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TestModelHelper
{
    public const DATA_UUID = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
    public const DATE = '2017-07-28T14:47:31Z';
    public const SIZE = 717590;

    public static function buildDataModelMinimal(string $uuid = self::DATA_UUID): Data
    {
        $data = new Data();
        $data->uuid = $uuid;
        $data->url = 'https://localhost/data.url';
        $data->hash = 'd6f644b19812e97b5d871658d6d3400ecd4787faeb9b8990c1e7608288664be77257104a58d033bcf1a0e0945ff06468ebe53e2dff36e248424c7273117dac09';
        $data->type = 'document';
        $data->authors = [];
        $data->copyright = null;
        $data->uploader = new Uploader();
        $data->uploader->name = '';
        $data->properties = new Properties();
        $data->properties->title = 'properties.title';
        $data->properties->filename = 'properties.filename';
        $data->properties->mimeType = 'properties.mime_type';
        $data->properties->language = 'en';
        $data->properties->createdAt = DateHelper::createUtcDate('2017-12-12 10:11:12');

        return $data;
    }

    public static function buildDataModelStrict(string $uuid = self::DATA_UUID): Data
    {
        $data = new Data();
        $data->uuid = $uuid;
        $data->url = 'https://localhost/data.url';
        $data->hash = 'd6f644b19812e97b5d871658d6d3400ecd4787faeb9b8990c1e7608288664be77257104a58d033bcf1a0e0945ff06468ebe53e2dff36e248424c7273117dac09';
        $data->type = 'document';
        $author = new Author();
        $author->name = 'author.name';
        $data->authors[] = $author;
        $data->copyright = new Copyright();
        $data->copyright->owner = new CopyrightOwner();
        $data->copyright->owner->name = 'copyright.owner.name';
        $data->copyright->owner->website = 'copyright.owner.website';
        $data->copyright->usage = new CopyrightUsage();
        $data->copyright->usage->name = 'copyright.usage.name';
        $data->copyright->usage->short = 'copyright.usage.short';
        $data->uploader = new Uploader();
        $data->uploader->name = 'uploader.name';
        $data->properties = new Properties();
        $data->properties->title = 'properties.title';
        $data->properties->filename = 'properties.filename';
        $data->properties->mimeType = 'properties.mime_type';
        $data->properties->language = 'en';
        $data->properties->createdAt = DateHelper::createUtcDate('2017-12-12 10:11:12');

        return $data;
    }

    public static function createDataModel($sampleUUID): Data
    {
        $date = new \DateTime(self::DATE, new \DateTimeZone('UTC'));

        $data = new Data();
        $data->hash = hash('sha512', 'hash');
        $data->type = 'document';
        $data->url = 'http://example.com/data.txt';
        $data->uuid = $sampleUUID;
        $data->geoLocation = '{"type": "Point", "coordinates": [100.0, 0.0] }';

        $data->copyright = new Copyright();
        $data->copyright->owner = new CopyrightOwner();
        $data->copyright->owner->name = 'KLink Organization';
        $data->copyright->owner->email = 'info@klink.asia';
        $data->copyright->owner->website = 'http://www.klink.asia';
        $data->copyright->owner->address = 'Berlin';

        $data->copyright->usage = new CopyrightUsage();
        $data->copyright->usage->short = 'MPL-2.0';
        $data->copyright->usage->name = 'Mozilla Public License 2.0';
        $data->copyright->usage->reference = 'https://spdx.org/licenses/MPL-2.0.html';

        $data->properties = new Properties();
        $data->properties->title = 'Adventures of Sherlock Holmes';
        $data->properties->filename = 'adventures-of-sherlock-holmes.pdf';
        $data->properties->mimeType = 'application/pdf';
        $data->properties->language = 'en';
        $data->properties->createdAt = $date;
        $data->properties->updatedAt = $date;
        $data->properties->size = self::SIZE;
        $data->properties->abstract = 'It is a novel about a detective';
        $data->properties->thumbnail = 'https://ichef.bbci.co.uk/news/660/cpsprodpb/153B4/production/_89046968_89046967.jpg';
        $data->properties->tags = ['tag1', 'tag2'];
        $data->properties->collections = ['123', '456'];

        $data->uploader = new Uploader();
        $data->uploader->name = 'Uploader name';

        $data->authors = [];
        $author = new Author();
        $author->name = 'Arthur Conan Doyle';
        $author->email = 'arthur@conan.doyle';
        $author->contact = '221B Baker Street';
        $data->authors[] = $author;

        $data->status = 'index.ok';
        $data->updatedAt = $date;
        $data->requestId = 'a1b2c3d4e5f6';

        return $data;
    }

    public static function createLatestDataModel($sampleUUID): Data
    {
        $data = self::createDataModel($sampleUUID);

        $klink = new Klink();
        $klink->id = "1";
        $klink->name = "Test K-Link";
        $data->klinks = [
            $klink
        ];
        
        return $data;
    }

    public static function createDataArray($dataUUID): array
    {
        return [
            'hash' => hash('sha512', 'hash'),
            'type' => 'document',
            'url' => 'http://example.com/data.txt',
            'uuid' => $dataUUID,
            'copyright' => [
                'owner' => [
                    'name' => 'KLink Organization',
                    'email' => 'info@klink.asia',
                    'website' => 'http://www.klink.asia',
                    'address' => 'Berlin',
                ],
                'usage' => [
                    'short' => 'MPL-2.0',
                    'name' => 'Mozilla Public License 2.0',
                    'reference' => 'https://spdx.org/licenses/MPL-2.0.html',
                ],
            ],
            'properties' => [
                'title' => 'Adventures of Sherlock Holmes',
                'filename' => 'adventures-of-sherlock-holmes.pdf',
                'mime_type' => 'application/pdf',
                'language' => 'en',
                'created_at' => self::DATE,
                'updated_at' => self::DATE,
                'size' => self::SIZE,
                'abstract' => 'It is a novel about a detective',
                'thumbnail' => 'https://ichef.bbci.co.uk/news/660/cpsprodpb/153B4/production/_89046968_89046967.jpg',
                'tags' => ['tag1', 'tag2'],
                'collections' => ['123', '456'],
            ],
            'author' => [
                [
                    'email' => 'arthur@conan.doyle',
                    'name' => 'Arthur Conan Doyle',
                    'contact' => '221B Baker Street',
                ],
            ],
            'uploader' => [
                'name' => 'Uploader name',
            ],
        ];
    }

    public static function createLatestDataArray($dataUUID): array
    {
        return [
            'hash' => hash('sha512', 'hash'),
            'type' => 'document',
            'url' => 'http://example.com/data.txt',
            'uuid' => $dataUUID,
            'klinks' => [['id' => '1', 'name' => 'Test K-Link']],
            'copyright' => [
                'owner' => [
                    'name' => 'KLink Organization',
                    'email' => 'info@klink.asia',
                    'website' => 'http://www.klink.asia',
                    'address' => 'Berlin',
                ],
                'usage' => [
                    'short' => 'MPL-2.0',
                    'name' => 'Mozilla Public License 2.0',
                    'reference' => 'https://spdx.org/licenses/MPL-2.0.html',
                ],
            ],
            'properties' => [
                'title' => 'Adventures of Sherlock Holmes',
                'filename' => 'adventures-of-sherlock-holmes.pdf',
                'mime_type' => 'application/pdf',
                'language' => 'en',
                'created_at' => self::DATE,
                'updated_at' => self::DATE,
                'size' => self::SIZE,
                'abstract' => 'It is a novel about a detective',
                'thumbnail' => 'https://ichef.bbci.co.uk/news/660/cpsprodpb/153B4/production/_89046968_89046967.jpg',
                'tags' => ['tag1', 'tag2'],
                'collections' => ['123', '456'],
            ],
            'authors' => [
                [
                    'email' => 'arthur@conan.doyle',
                    'name' => 'Arthur Conan Doyle',
                    'contact' => '221B Baker Street',
                ],
            ],
            'uploader' => [
                'name' => 'Uploader name',
            ],
            "geo_location" =>'{"type": "Point", "coordinates": [100.0, 0.0] }'
        ];
    }

    public static function createDataSearchParamAggregationModel(array $data = []): Aggregation
    {
        return new Aggregation();
    }

    public static function createDataSearchParamsModel(array $data = []): SearchParams
    {
        $searchParamModel = new SearchParams();

        return $searchParamModel;
    }

    public static function createDataSearchParamSort(array $data = []): SortParam
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(['field']);
        $resolver->setDefaults([
            'order' => 'desc',
        ]);
        $resolver->setAllowedValues('order', ['desc', 'asc']);
        $options = $resolver->resolve($data);

        $sort = new SortParam();

        $sort->field = $options['field'];
        $sort->order = $options['order'];

        return $sort;
    }
}
