<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pju\Mesh\Registration;
use Redis;

class RegistrationTest extends TestCase {
    const SERVICE_SPEC = "[]";

    private $clientMock;
    private $responseMock;
    private $streamMock;
    private $redisMock;
    private Registration $registration;

    protected function setUp(): void {
        $this->mockStream();
        $this->mockResponse();
        $this->mockClient();
        $this->mockRedis();

        $registrationMock = \Mockery::mock(Registration::class)
           ->makePartial();
        $registrationMock->shouldReceive('getServiceSpec')
           ->withAnyArgs()
           ->andReturn(self::SERVICE_SPEC);

        $this->registration = $registrationMock;
        $this->registration->setRedis($this->redisMock);
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

    private function mockRedis() {
        $this->redisMock = \Mockery::mock(\Redis::class);
        $this->redisMock->shouldReceive('connect')->once();
        $this->redisMock->shouldReceive('hset')->withArgs([Registration::REGISTRATION_LIST, "Test", self::SERVICE_SPEC])->once()->andReturn(true);
        $this->redisMock->shouldReceive('hget')->withArgs([Registration::REGISTRATION_LIST, "Test"])->once()->andReturn(self::SERVICE_SPEC);
        $this->redisMock->shouldReceive('hdel')->withArgs([Registration::REGISTRATION_LIST, "Test"])->once()->andReturn(true);
        $this->redisMock->shouldReceive('hgetall')->withArgs([Registration::REGISTRATION_LIST])->once()->andReturn([self::SERVICE_SPEC]);

    }

    public function testRegisterService() {
        $registerResult = $this->registration->registerService("Test");
        $this->assertEquals(true, $registerResult);
    }

    public function testGetService() {
        $testService = $this->registration->getService("Test");
        $this->assertEquals(json_decode(self::SERVICE_SPEC), $testService);
    }

    public function testDeregisterService() {
        $registerResult = $this->registration->deregisterService("Test");
        $this->assertEquals(true, $registerResult);
    }

    public function testGetServiceList() {
        $getListResult = $this->registration->getServiceList();
        $this->assertEquals(self::SERVICE_SPEC, json_encode($getListResult[0]));
    }
}
