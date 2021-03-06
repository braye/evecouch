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
                    $client->putDocument($document, $document['_id'], $exists->body['_rev']);
                    break;
                case 404:
                    $client->postDocument($document);
                    break;
            }
        } catch (HTTPException $e) {
            throw $e;
        }
    }

    public function getById(string $id)
    {
        try{
            $client = $this->client;
            $doc = $client->findDocument($id);

            switch($doc->status){
                case 200:
                    return $doc->body;
                case 404:
                    return null;
            }
        } catch (HTTPException $e) {
            throw $e;
        }
    }

    public function createViewQuery($designDoc, $view)
    {
        return $this->client->createViewQuery($designDoc, $view);
    }
}