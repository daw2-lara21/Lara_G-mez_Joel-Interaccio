<?php

include('abstract.databoundobject.php');

class Login extends DataBoundObject {

    protected $user_id;
    protected $username;
    protected $password;

    protected function DefineTableName() { 
                return("login");
    }

    protected function DefineRelationMap() { 
                return(array(
                        "user_id" => "ID",
                        "username" => "Username",
                        "password" => "Password"));

                
    }
}

?>