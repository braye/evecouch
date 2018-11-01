<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Doctrine\CouchDB\CouchDBClient;
use Doctrine\CouchDB\HTTP\HTTPException;

use App\CouchDB\DocumentManager;

use App\Security\EveSsoAuthenticator;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

use Seat\Eseye\Configuration;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Eseye;
use Seat\Eseye\Cache\FileCache;

use App\Entity\User;

class SsoController extends AbstractController
{

    /**
     * @Route("/sso/callback/", name="ssoCallback")
     */
    public function callback(EveSsoAuthenticator $authenticator, GuardAuthenticatorHandler $guardHandler, Request $request, SessionInterface $session)
    {
        $code = $request->query->get('code');

        $client = new \GuzzleHttp\Client();

        try{
            $tokenResponse = $client->request('POST', 'https://login.eveonline.com/oauth/token',[
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode(getenv('ESI_CLIENT_ID') . ':' . getenv('ESI_SECRET_KEY'))
                ],
                'form_params' => [
                        'grant_type' => 'authorization_code',
                        'code' => $code
                ]
            ]);

        } catch (RequestException $e) {
            return $this->render('base.html.twig', [
                'error_message' => $e->getMessage()
            ]);
        }

        $accessToken = json_decode($tokenResponse->getBody());

        try{
            $character = $client->request('GET', 'https://login.eveonline.com/oauth/verify',[
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken->access_token
                ]
            ]);
        } catch (RequestException $e) {
            return $this->render('base.html.twig', [
                'error_message' => $e->getMessage()
            ]);
        }

        $characterInfo = json_decode($character->getBody());

        $dm = new DocumentManager('users');

        $charUser = $this->getDoctrine()->getRepository(User::class)->find($characterInfo->CharacterID);

        if(empty($charUser)){
            $currentUser = $this->getUser();
            $user = new User();
            $user->setCharacterId($characterInfo->CharacterID);
            $user->setCharacterName($characterInfo->CharacterName);
            // used when adding additional characters to an account
            if(!empty($currentUser) && $currentUser->getCharacterId() != $characterInfo->CharacterID){
                $user->setParentCharacterId($user->getCharacterId());
                $user->setRoles($user->getRoles());
            }
            $user->setAccessToken($accessToken->access_token);
            $user->setRefreshToken($accessToken->refresh_token);
            // all this for one little ID value...
            $configuration = Configuration::getInstance();
            $configuration->file_cache_location = getenv('ESI_CACHE_DIRECTORY');
            $configuration->logfile_location = getenv('ESI_LOG_DIRECTORY');
            $configuration->cache = FileCache::class;

            $authentication = new EsiAuthentication([
                'client_id'     => getenv('ESI_CLIENT_ID'),
                'secret'        => getenv('ESI_SECRET_KEY'),
                'refresh_token' => $user->getRefreshToken(),
            ]);

            $esi = new Eseye($authentication);

            $characterInfo = $esi->invoke('get', '/characters/{character_id}/', [
                'character_id' => $user->getCharacterId()
            ]);

            $user->setCorporationId($characterInfo->corporation_id);

            $dm->save($user);
        } else {
            $charUser->setAccessToken($accessToken->access_token);
            $charUser->setRefreshToken($accessToken->refresh_token);
            $dm->save($charUser);
            $user = $charUser;
        }

        $guardHandler->authenticateUserAndHandleSuccess(
            $user,
            $request,
            $authenticator,
            'main'
        );

        return $this->redirectToRoute('home');

    }
}
