<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pju\Mesh\Autodiscovery;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class AutodiscoveryTest extends TestCase {
    private $autodiscovery;
    private $clientMock;
    private $responseMock;
    private $streamMock;

    protected function setUp(): void {
        $this->clientMock = $this->createMock(Client::class);
        $this->autodiscovery = $this->getMockForAbstractClass(Autodiscovery::class, [], '', true, true, true, []);
        $this->autodiscovery->setClient($this->clientMock);
        $this->streamMock = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $this->streamMock->method('__toString')->willReturn(json_encode(['success' => true]));
        $this->responseMock = $this->createMock(Response::class);
        $this->responseMock->method('getBody')->willReturn($this->streamMock);
        $this->clientMock->method('request')->willReturn($this->responseMock);
    }

    public function testSuccessfulRequest() {
        $result = $this->autodiscovery->ping();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
    }

    public function testRequestException() {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Endpoint not found");
        $this->autodiscovery->getStuff();
    }

    public function testGetEndpoint()
    {
        $endpoint = $this->autodiscovery->getEndpoint("ping");
        $this->assertIsArray($endpoint);
        $this->assertEquals("ping", $endpoint["name"]);
        $this->assertArrayHasKey("path", $endpoint);
        $this->assertArrayHasKey("method", $endpoint);
        $this->assertArrayHasKey("params", $endpoint);
    }

    public function testGetEndpointParams()
    {
        $endpoint = $this->autodiscovery->getEndpoint("ping");
        $params = $this->autodiscovery->getEndpointParams($endpoint, []);
        $this->assertIsArray($params);    
    }

}
