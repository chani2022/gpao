<?php
namespace App\Entity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Description of GPAOUserController
 *
 * @author Administrateur
 */
class GPAOUser implements UserInterface{
    
    private $username,
            $password,
            $roles = ['ROLE_USER'];
    
    public function setData($login,$pass){
        $this->username = $login;
        $this->password = $pass;
    }
    //put your code here
    public function eraseCredentials() {
        
    }

    public function getPassword() {
        return $this->password;
    }

    public function getRoles() {
        return $this->roles;
    }

    public function getSalt() {
        
    }

    public function getUsername(): string {
        return $this->username;
    }

}
