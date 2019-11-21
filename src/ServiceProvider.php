<?php

namespace fortrabbit\ObjectStorage;

use Aws\S3\S3Client;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use League\Flysystem\Filesystem;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register 'object-storage' driver
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function boot()
    {
        /** @var \Illuminate\Filesystem\FilesystemManager $storage */
        $storage = $this->app->make('filesystem');
        $storage->extend('object-storage', function ($app, $config) {

            $s3Config = $config + ['version' => 'latest'];
            $root     = $s3Config['root'] ?? null;
            $options  = $config['options'] ?? [];

            return new Filesystem(
                new ObjectStorageAdapter(
                    new S3Client($s3Config),
                    $s3Config['bucket'],
                    $root,
                    $options
                ), $config);
        });
    }
}
