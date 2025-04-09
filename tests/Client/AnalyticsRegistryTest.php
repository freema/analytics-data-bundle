<?php

declare(strict_types=1);

namespace Freema\GA4AnalyticsDataBundle\Tests\Client;

use Freema\GA4AnalyticsDataBundle\Analytics\AnalyticsClientInterface;
use Freema\GA4AnalyticsDataBundle\Client\AnalyticsRegistry;
use Freema\GA4AnalyticsDataBundle\Exception\ClientConfigKeyDontExistException;
use PHPUnit\Framework\TestCase;

class AnalyticsRegistryTest extends TestCase
{
    private $registry;

    protected function setUp(): void
    {
        $this->registry = new AnalyticsRegistry();
    }

    public function testAddClient(): void
    {
        $clientMock = $this->createMock(AnalyticsClientInterface::class);

        $this->registry->addClient('test', $clientMock);

        $this->assertTrue($this->registry->hasClient('test'));
        $this->assertSame($clientMock, $this->registry->getClient('test'));
    }

    public function testGetClients(): void
    {
        $client1 = $this->createMock(AnalyticsClientInterface::class);
        $client2 = $this->createMock(AnalyticsClientInterface::class);

        $this->registry->addClient('test1', $client1);
        $this->registry->addClient('test2', $client2);

        $clients = $this->registry->getClients();

        $this->assertCount(2, $clients);
        $this->assertSame($client1, $clients['test1']);
        $this->assertSame($client2, $clients['test2']);
    }

    public function testGetClientThrowsExceptionForNonExistentKey(): void
    {
        $this->expectException(ClientConfigKeyDontExistException::class);

        $this->registry->getClient('non_existent');
    }

    public function testHasClient(): void
    {
        $clientMock = $this->createMock(AnalyticsClientInterface::class);

        $this->registry->addClient('test', $clientMock);

        $this->assertTrue($this->registry->hasClient('test'));
        $this->assertFalse($this->registry->hasClient('non_existent'));
    }
}
