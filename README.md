# A flysystem driver for fortrabbit's Object Storage

## Install

The most recent version (2.x) is compatible with Laravel 9 and 10

```
# For laravel 9 and 10
composer require fortrabbit/laravel-object-storage

# For laravel 6,7,8 
composer require fortrabbit/laravel-object-storage:^1.4
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


Environment variables for Object Storage access are available automatically on fortrabbit.
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


