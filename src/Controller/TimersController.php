<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use Seat\Eseye\Cache\FileCache;
use Seat\Eseye\Configuration;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Eseye;

use Symfony\Component\HttpFoundation\JsonResponse;

class TimersController extends AbstractController
{
    /**
     * @Route("/timers", name="structureFuelTimers")
     */
    public function index()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $configuration = Configuration::getInstance();
        $configuration->setfile_cache_location(getenv('ESI_CACHE_DIRECTORY'));
        $configuration->cache = FileCache::class;

        $user = $this->getUser();

        $authentication = new EsiAuthentication([
            'client_id'     => getenv('ESI_CLIENT_ID'),
            'secret'        => getenv('ESI_SECRET_KEY'),
            'refresh_token' => $user->getRefreshToken(),
        ]);

        $esi = new Eseye($authentication);

        $characterInfo = $esi->invoke('get', '/characters/{character_id}/', [
            'character_id' => $user->getCharacterId()
        ]);

        $structures = $esi->invoke('get', '/corporations/{corporation_id}/structures/', [
            'corporation_id' => $characterInfo->corporation_id
        ]);

        return new JsonResponse($structures);
        // return $this->render('timers/index.html.twig', [
        //     'controller_name' => 'TimersController',
        // ]);
    }
}
