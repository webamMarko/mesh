<?php

namespace Pju\Mesh;

use Redis;

class Registration
{
    const REGISTRATION_LIST = 'mesh_registration_list';

    private $redis;

    public function __construct()
    {
        if (!extension_loaded("redis")) {
            throw new \Error("Please install redis extension to use this class", 501);
        }

        if (!isset($_ENV["MESH_REDIS_HOSTNAME"]) || !isset($_ENV["MESH_REDIS_PORT"])) {
            throw new \Error("MESH_REDIS_HOSTNAME and MESH_REDIS_PORT must be set in .env", 501);
        }

        $this->redis = new Redis();
        $this->redis->connect($_ENV["MESH_REDIS_HOSTNAME"], $_ENV["MESH_REDIS_PORT"]);
    }

    public function registerService($serviceName)
    {
        $serviceSpec = file_get_contents($this->getServiceFilePath());
        $this->redis->hset(self::REGISTRATION_LIST, $serviceName, $serviceSpec);
    }

    public function deregisterService($serviceName)
    {
        $this->redis->hdel(self::REGISTRATION_LIST, $serviceName);
    }

    public function getService($serviceName)
    {
        $serviceSpec = $this->redis->hget(self::REGISTRATION_LIST, $serviceName);

        if (!$serviceSpec) {
            throw new \Error("Service not found", 404);
        }

        return json_decode($serviceSpec, true);
    }

    public function getServiceList()
    {
        $servicesSpec = $this->redis->hgetall(self::REGISTRATION_LIST);

        if (!$servicesSpec) {
            throw new \Error("No services found", 404);
        }

        foreach ($servicesSpec as &$serviceSpec) {
            $serviceSpec = json_decode($serviceSpec, true);
        }

        return $servicesSpec;
    }

    protected function getServiceFilePath() {
        return $_SERVER['DOCUMENT_ROOT'] . '/service.json';
    }
}