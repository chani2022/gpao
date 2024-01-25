<?php

namespace App\Model;

use App\Service\ConnectionProviders;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Query\QueryBuilder as QueryQueryBuilder;
use Doctrine\ORM\QueryBuilder;

/**
 * GPAODATAModel
 *
 * @author Administrateur
 */
abstract class GPAODATAModel
{

    protected $db;
    private $table = NULL,
        $relations = [];

    public function __construct(Connection $db)
    {

        $this->db = $db;

        $this->table = $this->getTable();
        $this->relations = $this->getRelations();
    }
    /**
     * 
     * parametrage de la table
     */
    abstract protected function getTable();

    /**
     * parametrage de la relation
     */
    abstract protected function getRelations();

    public function getTableName()
    {
        return $this->table;
    }

    public function getRelationsList()
    {
        return $this->relations;
    }

    /**
     * insertion donnee
     * @param array $data
     * @return type
     */
    public function insertData(array $data)
    {
        $objQuery = $this->db->createQueryBuilder()
            ->insert($this->table);

        foreach ($data as $field => $value) {
            $objQuery->setValue($field, ":" . $field);
            $objQuery->setParameter($field, $value);
        }

        return $objQuery;
    }

    /**
     * Mise a jour donnee
     * @param array $data
     * @return type
     */
    public function updateData(array $data, array $where = null)
    {

        $objQuery = $this->db->createQueryBuilder()
            ->update($this->table);

        foreach ($data as $field => $value) {
            $objQuery->set($field, ":" . $field);
            $objQuery->setParameter($field, $value);
        }
        if ($where) {
            $whereBegin = true;
            foreach ($where as $keys => $val) {
                if ($whereBegin) {
                    $whereBegin = false;
                    $objQuery->where($keys . " = :" . $keys)
                        ->setParameter($keys, $val);
                } else {
                    $objQuery->andWhere($keys . " = :" . $keys)
                        ->setParameter($keys, $val);
                }
            }
        }


        return $objQuery;
    }

    /**
     * suppression donnee
     * @return type
     */
    public function deleteData()
    {
        $objQuery = $this->db->createQueryBuilder()
            ->delete($this->table);
        return $objQuery;
    }


    /**
     * 
     * @param array $fields
     * @return QueryBuilder
     */
    public function Get(array $fields = NULL)
    {
        $objQuery = $this->db->createQueryBuilder();

        //les champs à récuperer
        if (is_null($fields)) {
            $objQuery->select('*');
        } else {
            if (is_array($fields) && count($fields) > 0) {
                $objQuery->select($fields);
            } else {
                $objQuery->select('*');
            }
        }

        /**
         * si l'objet n'a pas de relation
         */
        if (count($this->relations) == 0) {
            $objQuery->from($this->table);
        } else {

            //from de la table concerne
            $objQuery->from($this->table, $this->table);

            //ajout des relations
            foreach ($this->relations as $ObjName => $field) {

                $classExt = new $ObjName($this->db);

                $objQuery->innerJoin($this->table, $classExt->getTableName(), $classExt->getTableName(), $this->table . "." . $field . "=" . $classExt->getTableName() . "." . $field);
            }
        }

        return $objQuery;
    }
}
