<?php

namespace fortrabbit\ObjectStorage;

use Aws\S3\Exception\S3Exception;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Config;
use League\Flysystem\Util;

class ObjectStorageAdapter extends AwsS3Adapter
{
    /**
     * Upload an object.
     *
     * {@inheritdoc}
     */
    protected function upload($path, $body, Config $config)
    {
        $key     = $this->applyPathPrefix($path);
        $options = $this->getOptionsFromConfig($config);
        $acl     = array_key_exists('ACL', $options) ? $options['ACL'] : 'private';

        if (!$this->isOnlyDir($path)) {
            if (!isset($options['ContentType'])) {
                $options['ContentType'] = Util::guessMimeType($path, $body);
            }

            if (!isset($options['ContentLength'])) {
                $options['ContentLength'] = is_resource($body) ? Util::getStreamSize($body) : Util::contentSize($body);
            }

            if ($options['ContentLength'] === null) {
                unset($options['ContentLength']);
            }
        }

        // Perform a regular PutObject operation,
        // since fortrabbit does not support
        // S3 multi-part uploads so far
        $command = $this->s3Client->getCommand('PutObject', [
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'Body'   => $body,
                'ACL'    => $acl,
            ] + $options);

        try {
            $this->s3Client->execute($command);
        } catch (S3Exception $exception) {
            return false;
        }

        return $this->normalizeResponse($options, $path);
    }

    /**
     * Check if the path contains only directories
     *
     * @param string $path
     *
     * @return bool
     */
    private function isOnlyDir($path)
    {
        return substr($path, -1) === '/';
    }
}
