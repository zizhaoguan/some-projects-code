<?php

    
    /**
     * username must be longer than 6, don't contain any space, and only contain a-z, A-Z, 0-9, - and _
     * @param string $username
     * @return string
     */
    function validate_username($username){
        if(strpos($username, " ")!== false ){ return "username/password format error.<br>"; }
        else if(strlen($username) < 6){ return "username/password format error.<br>"; }
        else if(preg_match ("/[^a-zA-Z0-9_-]/", $username)){ return "username/password format error.<br>";}
        return "";
    }
    
    /**
     * password must be longer than 6, no space, and as least contain one lowercase, 
     * one uppercase, and one digital number
     * @param string $pw
     * @return string
     */
    function validate_password($pw){
        if(strpos($pw, " ")!== false){ return "username/password format error.<br>"; }
        else if(strlen($pw) < 6){ return "username/password format error.<br>"; }
        else if(!(preg_match ("/[a-z]/", $pw) && preg_match ("/[A-Z]/", $pw) && preg_match ("/[0-9]/", $pw))){
            return "username/password format error.<br>";
        }
        return "";
    }
    
    /**
     * file name can only contain a-z, A-Z, 0-9
     * @param string $file_name
     * @return string
     */
    function validate_file_name($file_name){
        if(strpos($file_name, " ")!== false){ return "should not contain space.<br>"; }
        else if($file_name === ""){return "should not empty.<br>";}
        else if(preg_match ("/[^a-zA-Z0-9]/", $file_name)){ return "file name can only contain a-z, A-Z, 0-9.<br>" ;}
        return "";
    }
    

    function validate_file_size($upload_file_size){
        if($upload_file_size < 20 ){ return "the file is too small.<br>";}
        return "";
    }

?>