<?php
namespace App\Model\GPAOModels;

use App\Model\GPAODATAModel;

/**
 * Description of Production
 *
 * @author Administrateur
 */
class Production extends GPAODATAModel{
    
    protected function getRelations() {
        return [
            "\App\Model\GPAOModels\Personnel"=>"id_personnel",
            "\App\Model\GPAOModels\Fichiers"=>"id_fichiers",
            //"\App\Model\GPAOModels\Dossier"=>"id_dossier"
        ];
    }

    protected function getTable() {
        return "production";
    }
}
