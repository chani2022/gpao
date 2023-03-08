<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace App\Model\GPAOModels;
use App\Model\GPAODATAModel;
/**
 * Description of Rib
 *
 * @author dev
 */
class Rib extends GPAODATAModel{
    //put your code here
    protected function getTable() {
        return "rib";
    }

    protected function getRelations() {
        return [
                "\App\Model\GPAOModels\Personnel"=>"id_personnel"
        ];
    }
}
