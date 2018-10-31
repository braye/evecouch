<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use Seat\Eseye\Cache\FileCache;
use Seat\Eseye\Configuration;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Eseye;
use Seat\Eseye\Exceptions\RequestFailedException;

use Symfony\Component\HttpFoundation\JsonResponse;

class TimersController extends AbstractController
{
    /**
     * @Route("/fueltimers", name="structureFuelTimers")
     */
    public function fuelTimers()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $configuration = Configuration::getInstance();
        $configuration->file_cache_location = getenv('ESI_CACHE_DIRECTORY');
        $configuration->logfile_location = getenv('ESI_LOG_DIRECTORY');
        $configuration->cache = FileCache::class;

        $user = $this->getUser();

        $authentication = new EsiAuthentication([
            'client_id'     => getenv('ESI_CLIENT_ID'),
            'secret'        => getenv('ESI_SECRET_KEY'),
            'refresh_token' => $user->getRefreshToken(),
        ]);

        $esi = new Eseye($authentication);

        try{
            $characterInfo = $esi->invoke('get', '/characters/{character_id}/', [
                'character_id' => $user->getCharacterId()
            ]);

            $structures = $esi->invoke('get', '/corporations/{corporation_id}/structures/', [
                'corporation_id' => $characterInfo->corporation_id
            ]);

            $structureDetails = array();

            foreach($structures as $structure){
                $structureDetails[] = $esi->invoke('get', '/universe/structures/{structure_id}', [
                    'structure_id' => $structure->structure_id
                ]);
            }
        } catch(RequestFailedException $e){
            return $this->render('base.html.twig', [
                'error_message' => $e->getMessage()
            ]);
        }

        return new JsonResponse($structureDetails);
        // return $this->render('timers/index.html.twig', [
        //     'controller_name' => 'TimersController',
        // ]);
    }
}
