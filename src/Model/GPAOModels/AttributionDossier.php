<?php

namespace App\Model\GPAOModels;

use App\Model\GPAODATAModel;

/**
 * Description of Personnel
 *
 * @author Administrateur
 */
class AttributionDossier extends GPAODATAModel
{

    //put your code here
    protected function getRelations()
    {
        return [
            "\App\Model\GPAOModels\Personnel" => "id_personnel",
            "\App\Model\GPAOModels\Dossier" => "nom_dossier"
        ];
    }

    protected function getTable()
    {
        return "attribution_dossier";
    }
}
