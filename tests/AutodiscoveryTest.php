<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pju\Mesh\Autodiscovery;
use Pju\Mesh\Registration;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class AutodiscoveryTest extends TestCase {
    private $autodiscovery;
    private $clientMock;
    private $responseMock;
    private $streamMock;
    private $registrationMock;

    protected function setUp(): void {
        $this->mockStream();
        $this->mockResponse();
        $this->mockRegistration();
        $this->mockClient();

        $this->autodiscovery = new Autodiscovery($this->registrationMock);
        $this->autodiscovery->setClient($this->clientMock);
        $this->autodiscovery->setRegistration($this->registrationMock);
    }

    private function mockStream() {
        $this->streamMock = \Mockery::mock(\Psr\Http\Message\StreamInterface::class);
        $this->streamMock->shouldReceive('__toString')->andReturn(json_encode(['success' => true]));
    }

    private function mockResponse() {
        $this->responseMock = \Mockery::mock(Response::class);
        $this->responseMock->shouldReceive('getBody')->andReturn($this->streamMock);
    }

    private function mockClient() {
        $this->clientMock = \Mockery::mock(Client::class);
        $this->clientMock->shouldReceive('request')->andReturn($this->responseMock);
    }

    private function mockRegistration() {
        $this->registrationMock = \Mockery::mock(Registration::class);
        $this->registrationMock->shouldReceive('__construct');
        $this->registrationMock->shouldReceive('getService')
            ->andReturn([
                "hostname" => "ping",
                "endpoints" => json_decode('[
                    {
                        "name": "ping",
                        "path": "/ping",
                        "method": "GET",
                        "params": [
                        ],
                        "headers": {
                            "Authorization": "test"
                        }
                    }
                ]', true)
            ]);
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
