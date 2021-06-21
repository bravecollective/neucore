<?php

declare(strict_types=1);

namespace Tests\Functional\Command;

use Neucore\Entity\Character;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Tests\Client;
use Tests\Functional\ConsoleTestCase;
use Tests\Helper;
use Tests\Logger;

class RevokeTokenTest extends ConsoleTestCase
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var Client
     */
    private $client;

    protected function setUp(): void
    {
        $this->helper = new Helper();
        $this->helper->emptyDb();
        $this->client = new Client();
    }

    public function testExecute_NoCharacter()
    {
        $output = $this->runConsoleApp('revoke-token', ['id' => 3]);

        $actual = explode("\n", $output);
        $this->assertSame(2, count($actual));
        $this->assertSame('Character not found.', $actual[0]);
        $this->assertSame('', $actual[1]);
    }

    public function testExecute_NoToken()
    {
        $char = (new Character())->setId(3)->setName('char1');
        $this->helper->addNewPlayerToCharacterAndFlush($char);

        $output = $this->runConsoleApp('revoke-token', ['id' => 3]);

        $actual = explode("\n", $output);
        $this->assertSame(2, count($actual));
        $this->assertSame('Character has no default token.', $actual[0]);
        $this->assertSame('', $actual[1]);
    }

    public function testExecute_InvalidToken()
    {
        $char = (new Character())->setId(3)->setName('char1');
        $this->helper->addNewPlayerToCharacterAndFlush($char);
        $this->helper->createOrUpdateEsiToken($char, 0, '');

        $output = $this->runConsoleApp('revoke-token', ['id' => 3]);

        $actual = explode("\n", $output);
        $this->assertSame(2, count($actual));
        $this->assertSame('Error reading token.', $actual[0]);
        $this->assertSame('', $actual[1]);
    }

    public function testExecute()
    {
        $char = (new Character())->setId(3)->setName('char1');
        $this->helper->addNewPlayerToCharacterAndFlush($char);
        $this->helper->createOrUpdateEsiToken($char);

        $this->client->setResponse(new Response(200));

        $output = $this->runConsoleApp('revoke-token', ['id' => 3], [
            ClientInterface::class => $this->client,
        ]);

        $actual = explode("\n", $output);
        $this->assertSame(2, count($actual));
        $this->assertSame('Success.', $actual[0]);
        $this->assertSame('', $actual[1]);
    }

    public function testExecute_Error()
    {
        $char = (new Character())->setId(3)->setName('char1');
        $this->helper->addNewPlayerToCharacterAndFlush($char);
        $this->helper->createOrUpdateEsiToken($char);

        $this->client->setResponse(new Response(400));

        $output = $this->runConsoleApp('revoke-token', ['id' => 3], [
            ClientInterface::class => $this->client,
            LoggerInterface::class => new Logger('test')
        ]);

        $actual = explode("\n", $output);
        $this->assertSame(2, count($actual));
        $this->assertSame('Error, check log.', $actual[0]);
        $this->assertSame('', $actual[1]);
    }
}
