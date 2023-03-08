<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\GPAOModels;

use App\Model\GPAODATAModel;

/**
 * Description of Fichiers
 *
 * @author dev
 */
class Fichiers extends GPAODATAModel
{
    //put your code here

    protected function getTable()
    {
        return "fichiers";
    }

    protected function getRelations()
    {
        return [
            "\App\Model\GPAOModels\Dossier" => "nom_dossier",
            "\App\Model\GPAOModels\EtapeTravail" => "id_etape_travail"
        ];
    }
}
