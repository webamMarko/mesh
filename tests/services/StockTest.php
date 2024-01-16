<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use Pju\Mesh\Services\Stock;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class StockTest extends TestCase {

    protected $client;

    protected function setUp(): void {
        $this->clientMock = $this->createMock(Client::class);
        $this->stock = $this->createMock(Stock::class);
        $this->stock->setClient($this->clientMock);
        $this->streamMock = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $this->streamMock->method('__toString')->willReturn(json_encode(['success' => true]));
        $this->responseMock = $this->createMock(Response::class);
        $this->responseMock->method('getBody')->willReturn($this->streamMock);
        $this->clientMock->method('request')->willReturn($this->responseMock);
    }

}