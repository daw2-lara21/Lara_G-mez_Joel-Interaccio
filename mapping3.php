<?php

include('abstract.databoundobject.php');

class Chat_message extends DataBoundObject {

    protected $chat_message_id;
    protected $to_user_id;
    protected $from_user_id;
    protected $chat_message;
    protected $timestamp;
    protected $status;

    protected function DefineTableName() { //hacemos función que nos devuelve el system user
                return("chat_message");
    }

    protected function DefineRelationMap() { //hacemos una función que nos devolverá la siguiente array
                return(array(
                        "chat_message_id" => "ID",
                        "to_user_id" => "To_user_id",
                        "from_user_id" => "From_user_id",
                        "chat_message" => "Chat_message",
                        "timestamp" => "Timestamp",
                        "status" => "Status"));
    }
}

?>