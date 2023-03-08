<?php
namespace App\Model\GPAOModels;

use App\Model\GPAODATAModel;

class LimitationAccess extends GPAODATAModel{

    protected function getRelations() {
        return [
            "\App\Model\GPAOModels\Personnel"=>"id_personnel",
        ];
    }

    protected function getTable() {
        return "limitation_acces";
    }

}