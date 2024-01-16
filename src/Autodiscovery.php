<?php

namespace Pju\Mesh;

use GuzzleHttp\Client;

abstract class Autodiscovery {
    protected static $serviceName = "Ping";
    protected $endpoints;
    protected $hostname;
    protected $client;

    public function __construct() {
        $serviceFilePath = $this->getServiceFilePath();
        if (!file_exists($serviceFilePath)) {
            throw new \Exception("Service file not found");
        }

        $services = json_decode(file_get_contents($serviceFilePath), true);
        if (isset($services[static::$serviceName]['endpoints'])) {
            $this->endpoints = $services[static::$serviceName]['endpoints'];
        } else {
            throw new \Exception("Service not defined or endpoints missing");
        }

        if (isset($services[static::$serviceName]['hostname'])) {
            $this->hostname = $services[static::$serviceName]['hostname'];
        }

        $this->client = new Client(); // Initialize Guzzle client
    }

    public function __call($methodName, $arguments) {
        $endpoint = $this->getEndpoint($methodName);
        if ($endpoint) {
            return $this->handleRequest($endpoint, $arguments);
        }
           

        throw new \Exception("Endpoint not found");
    }

    protected function getServiceFilePath() {
        return __DIR__ . '/service.json';
    }    

    public function getEndpointParams($endpoint, $arguments) {
        $arguments = $arguments[0];
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
}
