<?php

namespace Yiisoft\Files;

use League\Flysystem\FilesystemAdapter;
use Yiisoft\Di\Container;
use Yiisoft\Di\Support\ServiceProvider;
use Yiisoft\Factory\Factory;

class FileStorageServiceProvider extends ServiceProvider
{
    public function register(Container $container): void
    {
        $factory = new Factory();
        $configs = $container->get(FileStorageConfigs::class)->getConfigs();
        foreach ($configs as $alias => $config) {
            $this->validateAdapter($alias, $config);
            $configParams = $config['config'] ?? [];
            $aliases = $config['aliases'] ?? [];
            $adapter = $factory->create($config['adapter']);
            $container->set($alias, [
                '__class' => Filesystem::class,
                '__construct()' => [
                    $adapter, $aliases, $configParams
                    ]
            ]);
        }
    }

    private function validateAdapter(string $alias, array $config)
    {
        $adapter = $config['adapter']['__class'] ?? false;
        if (!$adapter) {
            throw new \InvalidArgumentException("Adapter is not defined in the '$alias' storage config.");
        }
        if (!is_subclass_of($adapter, FilesystemAdapter::class)) {
            throw new \InvalidArgumentException("Adapter must implements FilesystemAdapterInterface.");
        }
    }
}
