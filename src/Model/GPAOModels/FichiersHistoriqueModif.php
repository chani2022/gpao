<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Model\GPAOModels;

use App\Model\GPAODATAModel;

/**
 * Description of Dossier
 *
 * @author dev
 */
class FichiersHistoriqueModif extends GPAODATAModel
{
    //put your code here
    protected function getRelations()
    {
        return [
            "\App\Model\GPAOModels\Fichiers" => "id_fichiers",
            "\App\Model\GPAOModels\Personnel" => "id_personnel",
        ];
    }

    protected function getTable()
    {
        return "fichiers_historique_modif";
    }
}
