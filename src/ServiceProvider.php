<?php

namespace fortrabbit\ObjectStorage;

use Aws\S3\S3Client;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use League\Flysystem\Filesystem;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * @param array $config
     *
     * @return array
     */
    protected static function mergeDefaults(array $config): array
    {
        $defaults = [
            'version'  => 'latest',
            'key'      => env('OBJECT_STORAGE_KEY'),
            'secret'   => env('OBJECT_STORAGE_SECRET'),
            'region'   => env('OBJECT_STORAGE_REGION'),
            'bucket'   => env('OBJECT_STORAGE_BUCKET'),
            'endpoint' => "https://" . env('OBJECT_STORAGE_SERVER'),
            'url'      => env('OBJECT_STORAGE_URL')
        ];

        return $config + $defaults;
    }

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
        $storage->extend('object-storage', function ($app, array $config) {

            $s3Config = self::mergeDefaults($config);
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
