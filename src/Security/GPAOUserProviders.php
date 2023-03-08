<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use Doctrine\DBAL\Driver\Connection;
/**
 * Description of GPAOUserProviders
 *
 * @author Administrateur
 */
class GPAOUserProviders implements UserProviderInterface{
    
    private $db;
    public function __construct(Connection $connex) {
        $this->db = $connex;
    }
    
    public function loadUserByUsername($username): \Symfony\Component\Security\Core\User\UserInterface {
        $obj = $this->db->createQueryBuilder()->select('id_personnel','login','motsdepasse','nom_privilege','nom_fonction','actif','photo')
                ->from('personnel');
                if (is_numeric($username)){
                    $obj->where('id_personnel = :u')
                    ->setParameter('u',$username);
                }else{
                    $obj->where('login = :u')
                    ->setParameter('u',$username);
                }
                
                $get = $obj->execute()->fetch();
                
        if (is_array($get) && count($get)>0){
            $user = new GPAOUser();
            $user->setData($get['login'], $get['motsdepasse'], $get);
            
            
            return $user;
        }
        
        return new GPAOUser();
        
        //throw new \Exception("ERREUR USERPROVIDER");
    }

    public function refreshUser(\Symfony\Component\Security\Core\User\UserInterface $user): \Symfony\Component\Security\Core\User\UserInterface {
        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class): bool {
        return $class == 'App\Security\GPAOUser';
    }

}
