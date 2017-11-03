<?php

namespace App\Tests\Controller;

use App\Model\Data\Author;
use App\Model\Data\Copyright;
use App\Model\Data\CopyrightOwner;
use App\Model\Data\CopyrightUsage;
use App\Model\Data\Data;
use App\Model\Data\Properties;
use App\Model\Data\Uploader;
use App\Security\Authorization\Voter\DataVoter;
use Symfony\Component\HttpFoundation\Response;
use function GuzzleHttp\Psr7\try_fopen;

class DataControllerAddTest extends AbstractJsonRpcControllerTest
{
    public const DATA_ADD_ENDPOINT = '/api/0.0/data.add';

    public function testDataAddWithMinimalDataSucceeded()
    {
        $this->setUserRoles([DataVoter::ROLE_DATA_ADD]);

        $data = $this->buildDataDocumentMinimal();
        $data->uploader->appUrl = self::APP_URL;
        $data->uploader->email = self::APP_EMAIL;
        $data->properties->updatedAt = new \DateTime();
        $data->status = Data::STATUS_QUEUED;

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->once())
            ->method('addData')
            ->with($this->callback(function (Data $dataIn) use ($data) {
                $this->assertEquals($data->uuid, $dataIn->uuid);
                $this->assertEquals($data->uploader->appUrl, $dataIn->uploader->appUrl);
                $this->assertEquals($data->uploader->email, $dataIn->uploader->email);

                return true;
            }));
        $dataService->expects($this->once())
            ->method('getData')
            ->with($data->uuid)
            ->willReturn($data);

        $addData = try_fopen(__DIR__.'/../fixtures/data-add.document-minimal.json', 'r');
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_ADD_ENDPOINT, $addData);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function provideFailureFiles(): array
    {
        return [
            'data' => [__DIR__.'/../fixtures/data-add.failing-00.json', [
                'params.data' => 'This value should not be blank.',
            ]],
            'min-data-level-1' => [__DIR__.'/../fixtures/data-add.failing-01.json', [
                'params.data.uuid' => 'This value should not be blank.',
                'params.data.url' => 'This value should not be blank.',
                'params.data.hash' => 'This value should not be blank.',
                'params.data.type' => 'This value should not be blank.',
                'params.data.properties' => 'This value should not be blank.',
                'params.data.author' => 'This value should not be blank.',
                'params.data.copyright' => 'This value should not be blank.',
                'params.data.uploader' => 'This value should not be blank.',
            ]],
            'min-data-level-2' => [__DIR__.'/../fixtures/data-add.failing-02.json', [
                'params.data.uuid' => 'This value should not be blank.',
                'params.data.url' => 'This value should not be blank.',
                'params.data.hash' => 'This value should not be blank.',
                'params.data.type' => 'This value should not be blank.',
                'params.data.properties.title' => 'This value should not be blank.',
                'params.data.properties.filename' => 'This value should not be blank.',
                'params.data.properties.mime_type' => 'This value should not be blank.',
                'params.data.properties.language' => 'This value should not be blank.',
                'params.data.properties.created_at' => 'This value should not be blank.',
                'params.data.author' => 'This value should not be blank.',
                'params.data.copyright.owner' => 'This value should not be blank.',
                'params.data.copyright.usage' => 'This value should not be blank.',
                'params.data.uploader.name' => 'This value should not be blank.',
            ]],
            'min-data-level-3' => [__DIR__.'/../fixtures/data-add.failing-03.json', [
                'params.data.uuid' => 'This value should not be blank.',
                'params.data.url' => 'This value should not be blank.',
                'params.data.hash' => 'This value should not be blank.',
                'params.data.type' => 'This value should not be blank.',
                'params.data.properties.title' => 'This value should not be blank.',
                'params.data.properties.filename' => 'This value should not be blank.',
                'params.data.properties.mime_type' => 'This value should not be blank.',
                'params.data.properties.language' => 'This value should not be blank.',
                'params.data.properties.created_at' => 'This value should not be blank.',
                'params.data.author[0].name' => 'This value should not be blank.',
                'params.data.copyright.owner' => 'This value should not be blank.',
                'params.data.copyright.usage' => 'This value should not be blank.',
                'params.data.uploader.name' => 'This value should not be blank.',
            ]],
            'min-data-level-4' => [__DIR__.'/../fixtures/data-add.failing-04.json', [
                'Invalid datetime "xxx", expected format Y-m-d\TH:i:s\Z.',
            ]],
            'min-data-level-4b' => [__DIR__.'/../fixtures/data-add.failing-04b.json', [
                'params.data.uuid' => 'This value should not be blank.',
                'params.data.url' => 'This value should not be blank.',
                'params.data.hash' => 'This value should not be blank.',
                'params.data.type' => 'This value should not be blank.',
                'params.data.properties.language' => 'This value should have exactly 2 characters.',
                'params.data.copyright.owner.contact' => 'This value should not be blank.',
                'params.data.copyright.usage.short' => 'This value should not be blank.',
                'params.data.copyright.usage.name' => 'This value should not be blank.',
            ]],
            'min-data-level-5' => [__DIR__.'/../fixtures/data-add.failing-05.json', [
                'params.data.uuid' => 'This value should not be blank.',
                'params.data.url' => 'This value should not be blank.',
                'params.data.hash' => 'This value should not be blank.',
                'params.data.type' => 'This value should not be blank.',
                'params.data.copyright.owner.contact' => 'This value should not be blank.',
                'params.data.copyright.usage.short' => 'This value should not be blank.',
                'params.data.copyright.usage.name' => 'This value should not be blank.',
            ]],
            'min-data-level-5b' => [__DIR__.'/../fixtures/data-add.failing-05b.json', [
                'params.data.uuid' => 'This value should not be blank.',
                'params.data.url' => 'This value should not be blank.',
                'params.data.hash' => 'This value should not be blank.',
                'params.data.type' => 'This value should not be blank.',
            ]],
        ];
    }

    /**
     * @dataProvider provideFailureFiles
     */
    public function testDataAddWithMissingRequiredFieldsFails(string $file, array $failures)
    {
        $this->setUserRoles([DataVoter::ROLE_DATA_ADD]);

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->never())
            ->method('addData');
        $dataService->expects($this->never())
            ->method('getData');

        $addData = try_fopen($file, 'r');
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_ADD_ENDPOINT, $addData);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonRpcErrorResponse($response->getContent(), 400, 'Wrong data provided!', $failures);
    }

    public function provideFailingRoles(): array
    {
        return [
            'ROLE_DATA_EDIT' => [[DataVoter::ROLE_DATA_EDIT]],
            'ROLE_DATA_REMOVE_OWN' => [[DataVoter::REMOVE_OWN]],
            'ROLE_DATA_REMOVE_ALL' => [[DataVoter::REMOVE_ALL]],
            'ROLE_DATA_SEARCH' => [[DataVoter::ROLE_DATA_SEARCH]],
            'ROLE_DATA_VIEW' => [[DataVoter::ROLE_DATA_VIEW]],
        ];
    }

    /**
     * @dataProvider provideFailingRoles
     *
     * @param array $roles
     */
    public function testDataAddWithoutAddPermissionFails(array $roles)
    {
        $this->setUserRoles($roles);

        $dataService = $this->setMockedDataService();
        $dataService->expects($this->never())
            ->method('addData');
        $dataService->expects($this->never())
            ->method('deleteData');

        $addRequest = $this->getAddRequestData();
        $this->sendAuthenticatedRequest(self::RPC_METHOD, self::DATA_ADD_ENDPOINT, $addRequest);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonRpcErrorResponse($response->getContent(), Response::HTTP_FORBIDDEN, 'Access Denied.');
    }

    private function getAddRequestData(array $params = []): string
    {
        $data = json_encode([
            'id' => self::REQUEST_ID,
            'params' => $params,
        ]);

        return $data;
    }

    private function buildDataDocumentMinimal(): Data
    {
        $data = new Data();
        $data->uuid = 'cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd';
        $data->url = 'https://localhost/data.url';
        $data->hash = 'd6f644b19812e97b5d871658d6d3400ecd4787faeb9b8990c1e7608288664be77257104a58d033bcf1a0e0945ff06468ebe53e2dff36e248424c7273117dac09';
        $data->type = 'document';
        $author = new Author();
        $author->name = 'author.name';
        $data->author[] = $author;
        $data->copyright = new Copyright();
        $data->copyright->owner = new CopyrightOwner();
        $data->copyright->owner->contact = 'copyright.owner.contact';
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
        $data->properties->createdAt = new \DateTime('2017-12-12T10:11:12Z', new \DateTimeZone('UTC'));

        return $data;
    }
}
