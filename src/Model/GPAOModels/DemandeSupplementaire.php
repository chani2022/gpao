<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\GPAOModels;

/**
 * Description of DemandeSupplementaire
 *
 * @author dev
 */
class DemandeSupplementaire extends \App\Model\GPAODATAModel{
    
    protected function getRelations() {
        return [
            "\App\Model\GPAOModels\Personnel"=>"id_personnel",
        ];
    }
    //put your code here
    protected function getTable() {
        return "demande_supplementaire";
    }
}
