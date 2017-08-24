<?php

namespace App\Tests\Helper;

class ModelHelper
{
    public static function createDataModel($sampleUUID): \App\Model\Data\Data
    {
        $data = new \App\Model\Data\Data();
        $data->hash = hash('sha512', 'hash');
        $data->type = 'document';
        $data->url = 'http://example.com/data.txt';
        $data->uuid = $sampleUUID;

        $data->copyright = new \App\Model\Data\Copyright();
        $data->copyright->owner = new \App\Model\Data\CopyrightOwner();
        $data->copyright->owner->name = 'KLink Organization';
        $data->copyright->owner->email = 'info@klink.asia';
        $data->copyright->owner->contact = 'KLink Website: http://www.klink.asia';

        $data->copyright->usage = new \App\Model\Data\CopyrightUsage();
        $data->copyright->usage->short = 'MPL-2.0';
        $data->copyright->usage->name = 'Mozilla Public License 2.0';
        $data->copyright->usage->reference = 'https://spdx.org/licenses/MPL-2.0.html';

        $data->properties = new \App\Model\Data\Properties();
        $data->properties->title = 'Adventures of Sherlock Holmes';
        $data->properties->filename = 'adventures-of-sherlock-holmes.pdf';
        $data->properties->mime_type = 'application/pdf';
        $data->properties->language = 'en';
        $data->properties->created_at = new \DateTime('2008-07-28T14:47:31Z');
        $data->properties->updated_at = new \DateTime('2008-07-28T14:47:31Z');
        $data->properties->size = '717590';
        $data->properties->abstract = 'It is a novel about a detective';
        $data->properties->thumbnail = 'https://ichef.bbci.co.uk/news/660/cpsprodpb/153B4/production/_89046968_89046967.jpg';

        return $data;
    }

    public static function createDataArray($sampleUUID): array
    {
        return [
            'hash' => hash('sha512', 'hash'),
            'type' => 'document',
            'url' => 'http://example.com/data.txt',
            'uuid' => $sampleUUID,
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
                'created_at' => '2008-07-28T14:47:31Z',
                'updated_at' => '2008-07-28T14:47:31Z',
                'size' => '717590',
                'abstract' => 'It is a novel about a detective',
                'thumbnail' => 'https://ichef.bbci.co.uk/news/660/cpsprodpb/153B4/production/_89046968_89046967.jpg',
            ],
        ];
    }
}
