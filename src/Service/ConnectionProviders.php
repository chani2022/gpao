<?php
namespace App\Service;

use Doctrine\DBAL\Driver\Connection;
/**
 * Description of ConnectionProviders
 *
 * @author Administrateur
 */
class ConnectionProviders {
    private $connex;
    public function __construct(Connection $connex) {
        $this->connex = $connex;
    }
    
    public function getConnection(){
        return $this->connex;
    }
}
