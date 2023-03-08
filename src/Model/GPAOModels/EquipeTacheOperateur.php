<?php
namespace App\Model\GPAOModels;

use App\Model\GPAODATAModel;

class EquipeTacheOperateur extends GPAODATAModel{

    protected function getRelations() {
        return [];
    }

    protected function getTable() {
        return "equipe_tache_operateur";
    }

}