<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\GPAOModels;
use App\Model\GPAODATAModel;
/**
 * Description of EtapeTravail
 *
 * @author dev
 */
class EtapeTravail extends GPAODATAModel{
    //put your code here
     protected function getRelations() {
        return [
            
        ];
    }

    protected function getTable() {
        return "etape_travail";
    }
}
