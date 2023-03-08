<?php
namespace App\Model\GPAOModels;

use App\Model\GPAODATAModel;
/**
 * Pointage
 *
 * @author Administrateur
 */
class TypePointage extends GPAODATAModel{

    //put your code here
    protected function getRelations() {
        return [
            //"\App\Model\GPAOModels\Personnel"=>"id_personnel",
        ];
    }

    protected function getTable() {
        return "type_pointage";
    }
    
    
}
