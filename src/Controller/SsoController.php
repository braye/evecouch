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

use App\Entity\User;
use App\Entity\EsiToken;

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
            return $this->render('sso/error.html.twig', [
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
            return $this->render('sso/error.html.twig', [
                'error_message' => $e->getMessage()
            ]);
        }

        $characterInfo = json_decode($character->getBody());

        $dm = new DocumentManager('users');

        $user = $this->getUser();

        $newUser = new User();
        $newUser->setCharacterId($characterInfo->CharacterID);
        $newUser->setCharacterName($characterInfo->CharacterName);
        if(!empty($user)){
            $newUser->setParentCharacterId($user->getCharacterId());
        }
        $newUser->setAccessToken($accessToken->access_token);
        $newUser->setRefreshToken($accessToken->refresh_token);

        $dm->save($newUser);

        $guardHandler->authenticateUserAndHandleSuccess(
            $newUser,          // the User object you just created
            $request,
            $authenticator, // authenticator whose onAuthenticationSuccess you want to use
            'main'          // the name of your firewall in security.yaml
        );

        return $this->redirectToRoute('home');

    }
}
