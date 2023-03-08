<?php
namespace App\Model\GPAOModels;

use App\Model\GPAODATAModel;

class Fonction extends GPAODATAModel{

    protected function getRelations() {
        return [];
    }

    protected function getTable() {
        return "fonction";
    }

}