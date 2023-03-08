<?php
namespace App\Model\GPAOModels;

use App\Model\GPAODATAModel;

class ExceptionConnection extends GPAODATAModel{

    protected function getRelations() {
        return [
            "\App\Model\GPAOModels\Personnel"=>"id_personnel",
        ];
    }

    protected function getTable() {
        return "exception_connection";
    }

}