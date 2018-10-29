<?php

namespace App\CouchDB;

use Doctrine\CouchDB\CouchDBClient;
use Doctrine\CouchDB\HTTP\HTTPException;

class DocumentManager
{
    private $client;

    public function __construct($dbName)
    {
        $this->client = CouchDBClient::create(array('dbname' => $dbName));
    }

    public function save(CouchEntity $entity)
    {
        $document = $entity->toDocument();

        try{
            $client = $this->client;
            $exists = $client->findDocument($document['_id']);

            switch($exists->status){
                case 200:
                    $client->putDocument($document, $document['_id'], $exists['_rev']);
                    break;
                case 404:
                    $client->postDocument($document);
                    break;
            }
        } catch (HTTPException $e) {
            throw $e;
        }
    }
}