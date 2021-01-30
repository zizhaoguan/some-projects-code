<?php
    require_once 'login.php';
    require_once 'validate_form.php';
    
    
    $conn = connect_mysql($hn, $un, $pw, $db);
    
    if(isset($_POST['signup'])){
        //php validate
        if(isset($_POST['signup_username'])){
            //sanitize signup_username
            $signup_username = mysql_entities_fix_string($conn, $_POST['signup_username']); 
        }
        if(isset($_POST['signup_pw'])){
            //sanitize signup_pw
            $signup_pw = mysql_entities_fix_string($conn, $_POST['signup_pw']);
        }
        if(isset($_POST['confirm_pw'])){
            //sanitize confirm_pw
            $confirm_pw = mysql_entities_fix_string($conn, $_POST['confirm_pw']);
        }
        
        $fail = validate_username($signup_username);
        $fail = $fail.validate_password($signup_pw);
        if($signup_pw !== $confirm_pw){
            $fail = $fail."<br>confirm password should be the same with the first enter<br>";
        }
        
        if($fail === ""){
            //if php validation is good
            signup($conn, $signup_username, $signup_pw, $confirm_pw);
        }
        else{
            header('Refresh:3');
            die(show_msg("<br>".$fail."<br>It will go back to prevoius page in 3 secs...<br>"));
        }
    }
    else{
        $_POST = array();
        display_signup();
    }
    //lead to Online Virus Check page
    echo "<p><a href=online_virus_check.php>Already have username and password? Click here to Login the admin</a></p>";
    
    $conn->close();
    

    //display the admin signup page
    function display_signup(){
       echo<<<_END
        <html><body>
        
        <script type="text/javascript" src="validate_form.js"></script>
        
        <form action='admin_signup.php' method='post' onSubmit='return validateSignup(this)' enctype='multipart/form-data'>
        <b>You can signup here if you don't have an admin account:</b>
        <br><br>Username:
        <br>(username must be longer than 6, don't contain any space, and only contain a-z, A-Z, 0-9, - and _)
        <br><input type='text' name='signup_username' size=10>
        <br>Password:
        <br>password must be longer than 6, and as least contain one lowercase, one uppercase, and one digital number (can contain special character)
        <br><input type='password', name='signup_pw', size=10>
        <br>Confirm Passoword:
        <br><input type='password', name='confirm_pw', size=10>
        <br><input type='submit' name='signup' value='signup'>
        </body></html>
_END;
    }
    
    
    
    //method to signup user
    function signup($conn, $username_temp, $signup_pw_temp,$confirm_pw_temp ){
        
//        $username_temp = mysql_entities_fix_string($conn, $_POST['signup_username']);
//        $signup_pw_temp = mysql_entities_fix_string($conn, $_POST['signup_pw']);
//        $confirm_pw_temp = mysql_entities_fix_string($conn, $_POST['confirm_pw']);
        
        $saltstring_1 = generate_saltstring();
        $saltstring_2 = generate_saltstring();
        //echo "<br>".$saltstring_1.$signup_pw_temp.$saltstring_2."<br>";
        
        $token = hash('ripemd128', $saltstring_1.$signup_pw_temp.$saltstring_2);
        //echo "<br>$token<br>";
        $stmt = $conn->prepare("INSERT INTO users VALUES(?,?,?,?)");
        $stmt->bind_param('ssss', $username_temp, $token, $saltstring_1, $saltstring_2);
        $stmt->execute();
        
        if($stmt->affected_rows<1){
            header('Refresh:3');
            $stmt->close();
            die(show_msg("it doesn't work...It will go back to prevoius page in 3 secs..."));
        }
        else{
            echo "<br><b>account created successfully!!! Now, It will go back to prevoius page in 3 secs</b><br>";
            header('Refresh:3');
            $stmt->close();
            die();
        }       
    }
    

    
    //connect to database
    function connect_mysql($hn, $un, $pw, $db){
        $conn = new mysqli($hn, $un, $pw, $db);
        if($conn->connect_error) { die(show_msg("it doesn't work")); }
        return $conn;
    }
    
    function show_msg($msg){
        echo<<<_END
        <br>we are sorry, $msg<br>
        <img src="https://i.ibb.co/Wx5nwnz/error-pixil.gif">
_END;
    }
    
    //sanitize the input
    function mysql_entities_fix_string($conn, $str){
        return htmlentities(mysql_fix_string($conn, $str));
    }
    
    //escape the some charactors
    function mysql_fix_string($conn, $str){
        if(get_magic_quotes_gpc()){ $str= stripslashes($str); }
        return $conn->real_escape_string($str);
    }
    
    //sanitize the string/text in txt file
    function sanitizeString($str) {
        $str = stripslashes($str);
        $str = strip_tags($str);
        $str = htmlentities($str);
        return $str;
    }
    
    function generate_saltstring(){
        $salt_arr =array('a','b','c','d','e','f','g','h','i','j','k','l','m','n'
            ,'o','p','q','r','s','t','u','v','w','x','y','z','0','1','2','3','4'
            ,'5','6','7','8','9','!','@','#','%','^','&','*');
        
        $salt_str = "";
        for($i=0; $i<5; $i++){
            $rand_num = rand(0, sizeof($salt_arr)-1);
            $salt_str = $salt_str.$salt_arr[$rand_num];
        }
        return $salt_str;
    }
    
?>