<?php

namespace App\Model\GPAOModels;

use App\Model\GPAODATAModel;

/**
 * Pointage
 *
 * @author Administrateur
 */
class Pointage extends GPAODATAModel
{

    //put your code here
    protected function getRelations()
    {
        return [
            "\App\Model\GPAOModels\Personnel" => "id_personnel",
            "\App\Model\GPAOModels\TypePointage" => "id_type_pointage",
        ];
    }

    protected function getTable()
    {
        return "pointage";
    }

    public function getPointages($matricule, $debut, $fin = NULL)
    {

        if (is_null($fin)) {
            $fin = $debut;
        }


        $obj = $this->Get(array(
            'pointage.date_debut', 'pointage.date_fin', 'pointage.heure_entre', 'pointage.heure_sortie', 'pointage.total',
            'type_pointage.heure_entre as heure_entre_def', 'type_pointage.heure_sortie as heure_sortie_def',
            'type_pointage.description as nom_type_pointage'
        ));

        $obj->where('pointage.date_debut BETWEEN :d and :f')
            ->setParameter('d', $debut)->setParameter('f', $fin);
        $obj->andWhere('pointage.id_personnel = :id')->setParameter('id', $matricule);

        return $obj->execute()->fetchAll();
    }
}