<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;

class SsoController extends AbstractController
{
    /**
     * @Route("/sso", name="sso")
     */
    public function index()
    {
        return $this->render('sso/index.html.twig', [
            'controller_name' => 'SsoController',
        ]);
    }

    /**
     * @Route("/sso/callback/", name="ssoCallback")
     */
    public function callback(Response $response, SessionInterface $session)
    {
        $code = $request->query->get('code');

        $client = new GuzzleHttp\Client();
        
        $refreshToken = $client->request('POST', 'https://login.eveonline.com/oauth/token',[
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode(getenv('ESI_CLIENT_ID') . ':' . getenv('ESI_SECRET_KEY'))
            ],
            'body' => [
                json_encode(
                    [
                        'grant_type' => 'authorization_code',
                        'code' => $refreshToken
                    ]
                )
            ]
        ]);

        var_dump($refreshToken);


        // $who = $client->request('GET', 'https://login.eveonline.com/oauth/verify', [
        //     'headers' => [
        //         'Authorization' => 'Bearer ' .
        //     ]
        // ]);





    }
}
