<?php

namespace App\Model\GPAOModels;

use App\Model\GPAODATAModel;

/**
 * Description of Personnel
 *
 * @author Administrateur
 */
class CompteRecolteHeure extends GPAODATAModel
{

    //put your code here
    protected function getRelations()
    {
        return [
            "\App\Model\GPAOModels\Personnel" => "id_personnel",
            "\App\Model\GPAOModels\CompteSalaire" => "id_compte_salaire"
        ];
    }

    protected function getTable()
    {
        return "compte_recolte_heure";
    }
}
