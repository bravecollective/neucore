<?php

declare(strict_types=1);

namespace Tests\Functional\Controller;

use Doctrine\Persistence\ObjectManager;
use Neucore\Data\PluginConfigurationDatabase;
use Neucore\Entity\Plugin;
use Psr\Log\LoggerInterface;
use Tests\Functional\WebTestCase;
use Tests\Helper;
use Tests\Logger;

class PluginControllerTest extends WebTestCase
{
    private Helper $helper;

    private ObjectManager $om;

    protected function setUp(): void
    {
        $this->helper = new Helper();
        $this->om = $this->helper->getObjectManager();
        $this->helper->emptyDb();
    }

    protected function tearDown(): void
    {
        unset($_SESSION['character_id']);
    }

    public function testRequest_403_NotLoggedIn()
    {
        $response = $this->runApp('GET', '/plugin/1/auth');
        $this->assertStringStartsWith('Not logged in.', $response->getBody()->__toString());
        $this->assertSame('Not logged in.<br><br><a href="/">Home</a>', $response->getBody()->__toString());
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testRequest_404_NoPlugin()
    {
        $this->helper->addCharacterMain('User 100', 100);
        $this->loginUser(100);

        $response = $this->runApp('GET', '/plugin/1/auth');
        $this->assertStringStartsWith('Plugin not found.', $response->getBody()->__toString());
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testRequest403_MissingGroup()
    {
        $this->helper->addCharacterMain('User 100', 100);
        $this->loginUser(100);
        $configuration = new PluginConfigurationDatabase();
        $configuration->directoryName = 'plugin1';
        $configuration->requiredGroups = [1];
        $plugin = (new Plugin())->setName('Plugin 1')->setConfigurationDatabase($configuration);
        $this->om->persist($plugin);
        $this->om->flush();

        $response = $this->runApp(
            'GET',
            '/plugin/'.$plugin->getId().'/auth',
            null,
            null,
            [],
            [['NEUCORE_PLUGINS_INSTALL_DIR', __DIR__ . '/PluginController']],
        );
        $this->assertStringStartsWith('Not allowed to use this plugin.', $response->getBody()->__toString());
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testRequest404_NoImplementation()
    {
        $this->helper->addCharacterMain('User 100', 100);
        $this->loginUser(100);
        $configuration = new PluginConfigurationDatabase();
        $configuration->directoryName = 'plugin2';
        $plugin = (new Plugin())->setName('Plugin 2')->setConfigurationDatabase($configuration);
        $this->om->persist($plugin);
        $this->om->flush();

        $response = $this->runApp(
            'GET',
            '/plugin/'.$plugin->getId().'/auth',
            null,
            null,
            [],
            [['NEUCORE_PLUGINS_INSTALL_DIR', __DIR__ . '/PluginController']],
        );
        $this->assertStringStartsWith('Plugin implementation not found.', $response->getBody()->__toString());
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testRequest404_NoPlayer()
    {
        $character = $this->helper->addCharacterMain('User 100', 100);
        $character->setMain(false);
        $this->loginUser(100);
        $configuration = new PluginConfigurationDatabase();
        $configuration->directoryName = 'plugin1';
        $plugin = (new Plugin())->setName('Plugin 1')->setConfigurationDatabase($configuration);
        $this->om->persist($character);
        $this->om->persist($plugin);
        $this->om->flush();

        $response = $this->runApp(
            'GET',
            '/plugin/'.$plugin->getId().'/auth',
            null,
            null,
            [],
            [['NEUCORE_PLUGINS_INSTALL_DIR', __DIR__ . '/PluginController']],
        );
        $this->assertStringStartsWith(
            'Player or main character account not found.',
            $response->getBody()->__toString()
        );
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testRequest_Success()
    {
        $character = $this->helper->addCharacterMain('User 100', 100, [], ['Group 1']);
        $this->loginUser(100);
        $configuration = new PluginConfigurationDatabase();
        $configuration->directoryName = 'plugin1';
        $configuration->requiredGroups = [$character->getPlayer()->getGroups()[0]->getId()];
        $plugin = (new Plugin())->setName('Plugin 1')->setConfigurationDatabase($configuration);
        $this->om->persist($plugin);
        $this->om->flush();

        $response = $this->runApp(
            'GET',
            '/plugin/'.$plugin->getId().'/auth',
            null,
            null,
            [],
            [['NEUCORE_PLUGINS_INSTALL_DIR', __DIR__ . '/PluginController']],
        );
        $this->assertSame('Response from plugin.', $response->getBody()->__toString());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRequest302_Exception()
    {
        $this->helper->addCharacterMain('User 100', 100);
        $this->loginUser(100);
        $configuration = new PluginConfigurationDatabase();
        $configuration->directoryName = 'plugin1';
        $plugin = (new Plugin())->setName('Plugin 1')->setConfigurationDatabase($configuration);
        $this->om->persist($plugin);
        $this->om->flush();

        $logger = new Logger('Test');
        $response = $this->runApp(
            'GET',
            '/plugin/'.$plugin->getId().'/auth?error=1',
            null,
            null,
            [LoggerInterface::class => $logger],
            [['NEUCORE_PLUGINS_INSTALL_DIR', __DIR__ . '/PluginController']],
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(
            '/#Service/'.$plugin->getId().'/?message=Unknown%20error.',
            $response->getHeader('Location')[0]
        );
        $this->assertSame('Exception from plugin.', $logger->getHandler()->getRecords()[0]['message']);
    }
}
