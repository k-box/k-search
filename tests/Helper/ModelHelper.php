<?php

namespace App\Tests\Helper;

use App\Model\Data\Author;
use App\Model\Data\Copyright;
use App\Model\Data\CopyrightOwner;
use App\Model\Data\CopyrightUsage;
use App\Model\Data\Data;
use App\Model\Data\Properties;
use App\Model\Data\Uploader;

class ModelHelper
{
    public const DATE = '2008-07-28T14:47:31Z';
    public const SIZE = 717590;

    public static function createDataModel($sampleUUID): Data
    {
        $date = new \DateTime(self::DATE, new \DateTimeZone('UTC'));

        $data = new Data();
        $data->hash = hash('sha512', 'hash');
        $data->type = 'document';
        $data->url = 'http://example.com/data.txt';
        $data->uuid = $sampleUUID;

        $data->copyright = new Copyright();
        $data->copyright->owner = new CopyrightOwner();
        $data->copyright->owner->name = 'KLink Organization';
        $data->copyright->owner->email = 'info@klink.asia';
        $data->copyright->owner->contact = 'KLink Website: http://www.klink.asia';

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

        $author = new Author();
        $author->email = 'arthur@conan.doyle';
        $author->name = 'Arthur Conan Doyle';
        $author->contact = '221B Baker Street';

        $data->author = [$author];

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
                    'contact' => 'KLink Website: http://www.klink.asia',
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
            'uploader' => [
                'name' => 'Uploader name',
            ],
            'author' => [
                [
                    'email' => 'arthur@conan.doyle',
                    'name' => 'Arthur Conan Doyle',
                    'contact' => '221B Baker Street',
                ],
            ],
        ];
    }
}
