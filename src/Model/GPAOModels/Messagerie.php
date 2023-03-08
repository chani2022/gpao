<?php
namespace App\Model\GPAOModels;

use App\Model\GPAODATAModel;
/**
 * Messagerie
 *
 * @author Administrateur
 */
class Messagerie extends GPAODATAModel{

    //put your code here
    protected function getRelations() {
        return [
            "\App\Model\GPAOModels\Personnel"=>"id_personnel"
        ];
    }

    protected function getTable() {
        return "messagerie";
    }
    
    

}
