# A flysystem driver for fortrabbit's Object Storage

## Install

```
composer require fortrabbit/laravel-object-storage
```

## Configure the 'object-storage' driver

You are free to choose the disk name. In this example it is `s3`, but it's up to you.

```
# config/filesystems.php

// ...
'disks' => [
    's3' => [
        'driver' => 'object-storage'
    ]
],

```


Environment variables for Object Storage access are avaible automatically on fortrabbit.
For local access you need to set them.

```
# local .env

OBJECT_STORAGE_KEY="{your-app-name}"
OBJECT_STORAGE_SECRET="{object-storage-secrect}"
OBJECT_STORAGE_REGION="{eu-west-1 or us-west-1} "
OBJECT_STORAGE_BUCKET="{your-app-name}"
OBJECT_STORAGE_SERVER="objects.{region}.frbit.com"
OBJECT_STORAGE_URL="https://{your-app-name}.objects.frb.io"
```


