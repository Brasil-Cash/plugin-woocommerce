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
    
        if ($documentLength == 11) { // CPF
            $this->type = 'individual';
        } elseif ($documentLength == 14) { // CNPJ
            $this->type = 'corporation';
        } else {
            // Tamanho não corresponde a um CPF ou CNPJ
            // Você pode lidar com outros casos aqui, se necessário.
        }

    }
}