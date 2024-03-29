<?php

namespace Pju\Mesh;

use GuzzleHttp\Client;
use Pju\Mesh\Registration;

class Autodiscovery {
    protected static $serviceName;
    protected string $hostname;
    protected array $endpoint;
    protected array $endpoints;
    protected array $service;
    protected Registration $registration;
    protected Client $client;


    public function __construct(Registration $registration = null) {
        if ($registration) {
            $this->registration = $registration;
        } else {
            $this->registration = new Registration();
        }
        
        $this->service = $this->registration->getService(self::$serviceName);
        $this->hostname = $this->service["hostname"];
        $this->endpoints = $this->service["endpoints"];
        $this->client = new Client();
    }

    public function __call($methodName, $arguments) {
        $endpoint = $this->getEndpoint($methodName);
        if ($endpoint) {
            return $this->handleRequest($endpoint, $arguments);
        }
           
        throw new \Exception("Endpoint not found");
    }

    public function getEndpointParams($endpoint, $arguments) {
        $arguments = $arguments[0] ?? [];
        // Map arguments to named parameters based on endpoint definition
        $params = [];
        foreach ($endpoint['params'] as $param) {
            if ($param['required']) {
                if (!isset($arguments[$param['name']])) {
                    throw new \Exception("Required parameter missing");
                }
            }

            if (isset($arguments[$param['name']])) {
                $params[$param['name']] = $arguments[$param['name']];
            }
        }

        return $params;
    }

    public function getEndpoint($endpointName) {
        foreach ($this->endpoints as $endpoint) {
            if ($endpoint['name'] === $endpointName) {
                return $endpoint;
            }
        }

        throw new \Exception("Endpoint not found");
    }

    private function handleRequest($endpoint, $arguments) {
        $params = $this->getEndpointParams($endpoint, $arguments);

        $headers = [];
        if (isset($endpoint['headers'])) {
            foreach ($endpoint['headers'] as $header_name => $header_value) {
                $headers[$header_name] = $header_value;
            }
        }

        $uri = $this->hostname . $endpoint['path'];

        if ($endpoint['method'] === 'GET' && $params) {
            $uri .= '?' . http_build_query($params);
        }

        // Make the API call
        $response = $this->client->request($endpoint['method'], $uri, [
            'json' => $endpoint['method'] === 'GET' ? [] : $params,
            'headers' => $headers
        ]);

        return json_decode($response->getBody(), true);
    }

    public function setClient(Client $client) {
        $this->client = $client;
    }

    public function setRegistration(Registration $registration) {
        $this->registration = $registration;
    }
}
