<?php

namespace fortrabbit\ObjectStorage;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3ClientInterface;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter;
use League\Flysystem\AwsS3V3\VisibilityConverter;
use League\Flysystem\Config;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use Throwable;

class ObjectStorageAdapter extends AwsS3V3Adapter
{

    /**
     * @var string
     */
    private $bucket;
    /**
     * @var S3ClientInterface
     */
    private $client;
    /**
     * @var MimeTypeDetector
     */
    private $mimeTypeDetector;
    /**
     * @var array
     */
    private $options;
    /**
     * @var PathPrefixer
     */
    private $prefixer;

    /**
     * @var VisibilityConverter
     */
    private $visibility;

    public function __construct(
        S3ClientInterface   $client,
        string              $bucket,
        string              $prefix = '',
        VisibilityConverter $visibility = null,
        MimeTypeDetector    $mimeTypeDetector = null,
        array               $options = [],
        bool                $streamReads = true
    )
    {
        $this->client = $client;
        $this->prefixer = new PathPrefixer($prefix);
        $this->bucket = $bucket;
        $this->visibility = $visibility ?: new PortableVisibilityConverter();
        $this->mimeTypeDetector = $mimeTypeDetector ?: new FinfoMimeTypeDetector();
        $this->options = $options;

        parent::__construct(
            $client,
            $bucket,
            $prefix,
            $visibility,
            $mimeTypeDetector,
            $options,
            $streamReads
        );
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->upload($path, $contents, $config);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->upload($path, $contents, $config);
    }


    /**
     * @param string          $path
     * @param string|resource $body
     * @param Config          $config
     */
    private function upload(string $path, $body, Config $config): void
    {
        $key = $this->prefixer->prefixPath($path);
        $options = $this->createOptionsFromConfig($config);
        $acl = $options['ACL'] ?? $this->determineAcl($config);

        $shouldDetermineMimetype = $body !== '' && !array_key_exists('ContentType', $options['params']);

        if ($shouldDetermineMimetype && $mimeType = $this->mimeTypeDetector->detectMimeType($key, $body)) {
            $options['params']['ContentType'] = $mimeType;
        }

        // Perform a regular PutObject operation,
        // since fortrabbit does not support
        // S3 multi-part uploads so far

        $command = $this->client->getCommand('PutObject', [
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => $body,
                'ACL' => $acl,
            ] + $this->options['params']);


        try {
            $this->client->execute($command);
        } catch (Throwable $exception) {
            throw UnableToWriteFile::atLocation($path, '', $exception);
        }
    }

    private function determineAcl(Config $config): string
    {
        $visibility = (string)$config->get(Config::OPTION_VISIBILITY, Visibility::PRIVATE);

        return $this->visibility->visibilityToAcl($visibility);
    }


    private function createOptionsFromConfig(Config $config): array
    {
        $options = ['params' => []];

        foreach (static::AVAILABLE_OPTIONS as $option) {
            $value = $config->get($option, '__NOT_SET__');

            if ($value !== '__NOT_SET__') {
                $options['params'][$option] = $value;
            }
        }

        foreach (static::MUP_AVAILABLE_OPTIONS as $option) {
            $value = $config->get($option, '__NOT_SET__');

            if ($value !== '__NOT_SET__') {
                $options[$option] = $value;
            }
        }

        return $options + $this->options;
    }


}
