<?php

namespace App\Model\GPAOModels;

use App\Model\GPAODATAModel;

/**
 * Description of Personnel
 *
 * @author Administrateur
 */
class SortieAvantHeure extends GPAODATAModel
{

    //put your code here
    protected function getRelations()
    {
        return [
            "\App\Model\GPAOModels\Personnel" => "id_personnel",
            // "\App\Model\GPAOModels\Personnel" => "donneur_ordre"
        ];
    }

    protected function getTable()
    {
        return "sortie_avant_heure";
    }
}