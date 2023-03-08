<?php
namespace App\Model\GPAOModels;

use App\Model\GPAODATAModel;

/**
 * Description of Personnel
 *
 * @author Administrateur
 */
class Information extends GPAODATAModel{

    //put your code here
    protected function getRelations() {
        return [
            "\App\Model\GPAOModels\Personnel"=>"id_personnel"
        ];
    }

    protected function getTable() {
        return "information";
    }

}
