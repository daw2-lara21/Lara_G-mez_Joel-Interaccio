<?php

include('abstract.databoundobject.php');

class Login_details extends DataBoundObject {

    protected $login_details_id;
    protected $user_id;
    protected $last_activity;
    protected $is_type;

    protected function DefineTableName() { 
                return("login_details");
    }

    protected function DefineRelationMap() { 
                return(array(
                        "login_details_id" => "ID",
                        "user_id" => "User_id",
                        "last_activity" => "Last_activity",
                        "is_type" => "Is_type"));
    }
}

?>