<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use Seat\Eseye\Cache\FileCache;
use Seat\Eseye\Configuration;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Eseye;
use Seat\Eseye\Exceptions\RequestFailedException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Symfony\Component\HttpFoundation\JsonResponse;

use App\Entity\CorpStructureList;
use App\CouchDB\DocumentManager;

class TimersController extends AbstractController
{
    /**
     * @Route("/fueltimers", name="structureFuelTimers")
     */
    public function fuelTimers()
    {
        try{
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        } catch(AccessDeniedException $e) {
            return $this->render('base.html.twig', [
                'error_message' => 'Please log in to access this page.'
            ]);
        }

        $user = $this->getUser();

        $repository = $this->getDoctrine()->getRepository(CorpStructureList::class);

        // $corpStructureList = $repository->find(['_id' => $user->getCorporationId()]);
        $corpStructureList = $repository->find(['_id' => 98285237]);

        // default to updating every 12h
        if(empty($corpStructureList) || (gmdate('U') < $corpStructureList->updated + 43200)){
            $corpStructureList = $this->updateStructures();
        }
        // return new JsonResponse($structures);
        return $this->render('timers/fueltimers.html.twig', [
            'structures' => $corpStructureList->getStructures(),
        ]);
    }

    private function updateStructures()
    {
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

        $dm = new DocumentManager('structures');

        $structureList = new CorpStructureList();
        $structureList->setUpdated(gmdate('U'));
        // $structureList->setCorporationId($user->getCorporationId());
        $structureList->setCorporationId(98285237);

        try{
            $characterInfo = $esi->invoke('get', '/characters/{character_id}/', [
                'character_id' => $user->getCharacterId()
            ]);
            $structures = $esi->invoke('get', '/corporations/{corporation_id}/structures/', [
                'corporation_id' => $characterInfo->corporation_id
            ]);

            foreach($structures as $structure){
                $structure->details = $esi->invoke('get', '/universe/structures/{structure_id}', [
                    'structure_id' => $structure->structure_id
                ]);
                //todo replace this with SDE data
                $structure->system_name = $esi->invoke('get', '/universe/systems/{system_id}', [
                    'system_id' => $structure->details->solar_system_id
                ])->name;
                
                if(!empty($structure->fuel_expires)){
                    $fuelDate = new \DateTime($structure->fuel_expires);
                } else {
                    $fuelDate = new \DateTime('1970-01-01 00:00:00');
                }

                $structureDetails[] = [
                    'structure_id' => $structure->structure_id,
                    'structure_name' => $structure->details->name,
                    'system_name' => $structure->system_name,
                    'fuel_expires' => $fuelDate->format('U')
                ];
            }
        } catch (RequestFailedException $e) {
            throw $e;
        }

        $structureList->setStructures($structureDetails);
        $dm->save($structureList);
        return $structureList;
    }
}
