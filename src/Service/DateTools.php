<?php

/**
 * pour la gestion des dates
 */

namespace App\Service;

/**
 * @author Administrateur
 */
class DateTools {
    
    public function getDatesFromDateRangePicker(string $data){
        
        $result = ["debut"=>NULL,"fin"=>NULL];
        
        $exp = explode(" - ", $data);
        
        if (count($exp)>0){
            try{
                $deb = new \DateTime(str_replace("/", "-", $exp[0]));
                $result["debut"] = $deb->format("Y-m-d");
            } catch (\Exception $ex) {

            }
            
            try{
                $fin = new \DateTime(str_replace("/", "-", $exp[1]));
                $result["fin"] = $fin->format("Y-m-d");
            } catch (\Exception $ex) {

            }
        }
        
        return $result;
    }
}
