<?php

namespace Bcpag\Models;

use Analog\Analog;

Class Customer {
    public string $name;
    public string $email;
    public string $type = '';
    public string $country;
    public string $external_id;
    public array $documents;

    public function addDocument(Document $document) {
        $this->documents[] = $document;
    }

    public function getDocuments(){
        return $this->documents;
    }

    public function setType($document) {
        $cleanDocument = preg_replace('/[^0-9]/', '', $document);
    
        $documentLength = strlen($cleanDocument);
    
        if ($documentLength == 14) {
            $this->type = 'corporation';
        } else {
            $this->type = 'individual';
        }

    }
}