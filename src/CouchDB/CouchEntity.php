<?php

namespace App\CouchDB;


class CouchEntity
{
    public function toDocument(){
        return get_object_vars($this);
    }
}