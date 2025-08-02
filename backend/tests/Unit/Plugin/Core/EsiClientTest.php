<?php

/** @noinspection DuplicatedCode */

declare(strict_types=1);

namespace Tests\Unit\Plugin\Core;

use GuzzleHttp\Psr7\Response;
use Neucore\Data\EsiErrorLimit;
use Neucore\Factory\RepositoryFactory;
use Neucore\Plugin\Core\EsiClient;
use Neucore\Plugin\Core\EsiClientInterface;
use Neucore\Plugin\Core\Exception;
use Neucore\Service\ObjectManager;
use Neucore\Storage\StorageInterface;
use Neucore\Storage\SystemVariableStorage;
use Neucore\Storage\Variables;
use PHPUnit\Framework\TestCase;
use Tests\Client;
use Tests\Helper;
use Tests\HttpClientFactory;
use Tests\Logger;

class EsiClientTest extends TestCase
{
    private Helper $helper;

    private Client $httpClient;

    private StorageInterface $storage;

    private EsiClient $esiClient;

    protected function setUp(): void
    {
        $this->helper = new Helper();
        $om = $this->helper->getObjectManager();
        $logger = new Logger();
        $this->httpClient = new Client();
        $this->storage = new SystemVariableStorage(new RepositoryFactory($om), new ObjectManager($om, $logger));
        $this->esiClient = new EsiClient(
            Helper::getEsiClientService($this->httpClient, $logger),
            new HttpClientFactory($this->httpClient),
            $this->storage,
        );
        $this->helper->emptyDb();
    }

    public function testGetErrorLimitRemaining(): void
    {
        $this->assertSame(15, $this->esiClient->getErrorLimitRemaining());
    }

    public function testSetCompatibilityDate(): void
    {
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        self::assertNull($this->esiClient->setCompatibilityDate('2025-07-11'));
    }

    public function testRequest_ErrorLimit(): void
    {
        $time = time();
        $this->storage->set(Variables::ESI_ERROR_LIMIT, (string) json_encode(new EsiErrorLimit($time, 10, 45)));

        $this->expectException(Exception::class);
        $this->expectExceptionCode($time + 45);
        $this->expectExceptionMessage(EsiClientInterface::ERROR_ERROR_LIMIT_REACHED);

        $this->esiClient->request('/latest/characters/102003000/', 'GET', null, 20300400);
    }

    public function testRequest_RateLimit(): void
    {
        $time = time();
        $this->storage->set(Variables::ESI_RATE_LIMIT, (string) ($time + 20));

        $this->expectException(Exception::class);
        $this->expectExceptionCode($time + 20);
        $this->expectExceptionMessage(EsiClientInterface::ERROR_RATE_LIMIT_REACHED);

        $this->esiClient->request('/latest/characters/102003000/', 'GET', null, 20300400);
    }

    public function testRequest_Throttled(): void
    {
        $time = time();
        $this->storage->set(Variables::ESI_THROTTLED, (string) ($time + 50));

        $this->expectException(Exception::class);
        $this->expectExceptionCode($time + 50);
        $this->expectExceptionMessage(EsiClientInterface::ERROR_TEMPORARILY_THROTTLED);

        $this->esiClient->request('/latest/characters/102003000/', 'GET', null, 20300400);
    }

    public function testRequest_CharNotFound(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage(EsiClientInterface::ERROR_CHARACTER_NOT_FOUND);

        $this->esiClient->request('/latest/characters/102003000/', 'GET', null, 20300400);
    }

    public function testRequest_TokenInvalid(): void
    {
        $this->helper->addCharacterMain('char name', 20300400, [], [], false);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage(EsiClientInterface::ERROR_INVALID_TOKEN);

        $this->esiClient->request('/latest/characters/102003000/', 'GET', null, 20300400);
    }

    /**
     * @throws Exception
     */
    public function testRequest_OK(): void
    {
        // Create char with valid, not expired, ESI token.
        $this->helper->addCharacterMain('char name', 20300400, [], [], true, null, time() + 60, true);

        $this->httpClient->setResponse(new Response(200, [], '{"name": "char name", "corporation_id": 20}'));

        $this->esiClient->setCompatibilityDate('2025-07-11');

        $response = $this->esiClient->request('/latest/characters/102003000/', 'GET', null, 20300400);

        $this->assertSame(
            ['X-Compatibility-Date' => '2025-07-11', 'Accept-Language' => 'en'],
            $this->httpClient->getHeaders()
        );
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            ['name' => 'char name', 'corporation_id' => 20],
            json_decode($response->getBody()->__toString(), true),
        );
    }

    /**
     * @throws Exception
     */
    public function testRequest_OptionalHeaders(): void
    {
        $this->helper->addCharacterMain('char name', 20300400, tokenExpires: time() + 60, tokenValid: true);

        $this->esiClient->setCompatibilityDate('2025-07-11');

        $this->httpClient->setResponse(new Response());

        $this->esiClient->request(
            '/latest/characters/102003000/',
            'GET',
            null,
            20300400,
            compatibilityDate: '2025-07-12',
            acceptLanguage: 'de',
        );

        $this->assertSame(
            [
                'X-Compatibility-Date' => '2025-07-12',
                'Accept-Language' => 'de',
            ],
            $this->httpClient->getHeaders(),
        );
    }
}
