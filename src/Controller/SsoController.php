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

class SsoController extends AbstractController
{

    /**
     * @Route("/sso/callback/", name="ssoCallback")
     */
    public function callback(Request $request, SessionInterface $session)
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

        $client = CouchDBClient::create(array('dbname' => 'sso'));

        try{
            $ssoDocument = $client->findDocument($characterInfo->CharacterID);

            switch($ssoDocument->status){
                case 200:
                    $document = $ssoDocument->body;
                    $document['access_token'] = $accessToken->access_token;
                    $document['refresh_token'] = $accessToken->refresh_token;
                    $client->putDocument($document, $document['_id'], $document['_rev']);
                    break;
                case 404:
                    $client->postDocument([
                        // couch only allows strings as IDs
                        '_id' => (string)$characterInfo->CharacterID,
                        'access_token' => $accessToken->access_token,
                        'refresh_token' => $accessToken->refresh_token
                    ]);
                    break;
                default:
                    // something bad happened
                    break;
            }
        } catch (HTTPException $e) {
            return $this->render('sso/error.html.twig', [
                'error_message' => $e->getMessage()
            ]);
        }

        $session = new Session();
        $session->set('CharacterID', $characterInfo->CharacterID);

        return $this->redirectToRoute('home');
    }
}
