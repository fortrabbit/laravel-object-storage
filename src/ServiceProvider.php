<?php

namespace fortrabbit\ObjectStorage;

use Aws\S3\S3Client;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter;
use League\Flysystem\Filesystem;
use League\Flysystem\Visibility;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * @var array
     */
    protected static array $OBJECT_STORAGE_SECRETS = [];

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
            $root = (string) ($s3Config['root'] ?? '');
            $streamReads = $s3Config['stream_reads'] ?? false;
            $visibility = new PortableVisibilityConverter(
                $config['visibility'] ?? Visibility::PUBLIC
            );

            $adapter = new ObjectStorageAdapter(
                new S3Client($s3Config),
                $s3Config['bucket'],
                $root,
                $visibility,
                null,
                [],
                $streamReads
            );

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }

    /**
     * @param array $config
     *
     * @return array
     */
    protected static function mergeDefaults(array $config): array
    {
        $defaults = [
            'version'     => 'latest',
            'region'      => env('OBJECT_STORAGE_REGION', self::secret('REGION')),
            'bucket'      => env('OBJECT_STORAGE_BUCKET', self::secret('BUCKET')),
            'endpoint'    => "https://" . env('OBJECT_STORAGE_SERVER', self::secret('SERVER')),
            'url'         => env('OBJECT_STORAGE_URL', self::secret('URL')),
            'credentials' => [
                'key'    => env('OBJECT_STORAGE_KEY', self::secret('KEY')),
                'secret' => env('OBJECT_STORAGE_SECRET', self::secret('SECRET')),
            ]
        ];

        if (!empty($config['key']) && !empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
            unset($config['key'], $config['secret']);
        }

        return $config + $defaults;
    }

    /**
     * @param string $key
     *
     * @return string|null
     */
    protected static function secret(string $key): ?string
    {
        if (!getenv('APP_SECRETS')) {
            return null;
        }

        if (count(self::$OBJECT_STORAGE_SECRETS) === 0) {
            $secrets = json_decode(file_get_contents(getenv('APP_SECRETS')), true);
            if ($secrets != null && isset($secrets['OBJECT_STORAGE'])) {
                self::$OBJECT_STORAGE_SECRETS = $secrets['OBJECT_STORAGE'];
            }
        }
        return self::$OBJECT_STORAGE_SECRETS[$key] ?? null;
    }
}
