<?php
namespace App\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 *
 * @author Administrateur
 */
class GPAOUser implements UserInterface{
    
    private $username,
            $password,
            $isActif=TRUE,
            $roles = ['ROLE_USER'],
            $userdetails = [];
    
    public function setData($login,$pass, array $data){
        $this->username = $login;
        $this->password = $pass;
        $this->userdetails = $data;
        
        if ($this->userdetails['actif']!=="Oui"){
            $this->isActif = FALSE;
        }
        
        $this->setRolesByData();
    }
    
    public function getUserDetails(){
        return $this->userdetails;
    }
    public function getIsActif(){
        return $this->isActif;
    }
    /**
     * definition du role
     */
    private function setRolesByData(){
        if (count($this->userdetails)>0){
            
            
            switch ($this->userdetails['nom_privilege']){
                
                case "admin":
                    $this->roles = ['ROLE_ADMIN'];
                    break;
                case "gp":
                    if (strtolower($this->userdetails['nom_fonction']) == "responsable personnel"){
                        $this->roles = ['ROLE_RH'];
                    }else{
                        $this->roles = ['ROLE_ARH'];
                    }
                    break;
                    
                case "superadmin":
                    $this->roles = ['ROLE_SUPER_ADMIN'];
                    break;
                
                default:
                    $this->roles = ['ROLE_USER'];
                    break;
            }
        }
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
