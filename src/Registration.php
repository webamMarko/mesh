<?php

namespace Pju\Mesh;

class Registration
{
    const REGISTRATION_LIST = 'mesh_registration_list';

    private $redis;

    public function __construct(\Redis $redis = null)
    {
        if ($redis) {
            $this->redis = $redis;
            return;
        }

        if (!extension_loaded("redis")) {
            throw new \Error("Please install redis extension to use this class", 501);
        }

        if (!isset($_ENV["MESH_REDIS_HOSTNAME"]) || !isset($_ENV["MESH_REDIS_PORT"])) {
            throw new \Error("MESH_REDIS_HOSTNAME and MESH_REDIS_PORT must be set in ENV", 501);
        }

        $redis = new \Redis();
        $redis->connect($_ENV["MESH_REDIS_HOSTNAME"], $_ENV["MESH_REDIS_PORT"]);

        $this->setRedis($redis);
    }

    public function registerService($serviceName = '')
    {
        $serviceSpec = $this->getServiceSpec($serviceName);
        return $this->redis->hset(self::REGISTRATION_LIST, $serviceName, $serviceSpec);
    }

    public function deregisterService($serviceName)
    {
        return $this->redis->hdel(self::REGISTRATION_LIST, $serviceName);
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

    public function getServiceSpec(string $serviceName = '') {
        return file_get_contents($this->getServiceFilePath($serviceName));
    }

    public function setRedis(\Redis $redis) {
        $this->redis = $redis;
    }

    protected function getServiceFilePath(string $serviceName = '') {
        return self::getRootDir() . "/{$serviceName}service.json";
    }

    public static function getRootDir(): string
	{
		if (isset($_ENV['ROOT_DIR'])) return $_ENV['ROOT_DIR'];

		// Find root vendor folder path of composer
		$reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);

		return dirname($reflection->getFileName(), 3);
	}

}