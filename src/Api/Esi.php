<?php

namespace App\Api;

use Seat\Eseye\Cache\FileCache;
use Seat\Eseye\Configuration;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Eseye;

use App\Entity\User;

class Esi
{
    public static function getApiHandleForUser(User $user){

        $configuration = Configuration::getInstance();
        $configuration->file_cache_location = getenv('ESI_CACHE_DIRECTORY');
        $configuration->logfile_location = getenv('ESI_LOG_DIRECTORY');
        $configuration->cache = FileCache::class;

        $authentication = new EsiAuthentication([
            'client_id'     => getenv('ESI_CLIENT_ID'),
            'secret'        => getenv('ESI_SECRET_KEY'),
            'refresh_token' => $user->getRefreshToken(),
        ]);

        return new Eseye($authentication);
    }
}