<?php
    require_once 'login.php';
    require_once 'validate_form.php';
    $conn = connect_mysql($hn, $un, $pw, $db);
   
    if(isset($_POST['admin_login_page'])|| isset($_POST['login'])){
        if(isset($_POST['login'])){
       
            if(isset($_POST['login_username'])){
                //sanitize login_username
                $login_username = mysql_entities_fix_string($conn, $_POST['login_username']); 
            }
            if(isset($_POST['login_pw'])){
                //sanitize login_pw
                $login_pw = mysql_entities_fix_string($conn, $_POST['login_pw']);
            }
        
            $fail = validate_username($login_username);
            $fail = $fail.validate_password($login_pw);
        
        
            if($fail === ""){
                //if php validation is good
                $login_success = check_login_info($conn, $login_username, $login_pw);
            
                if($login_success){
                    //if login successfully
                    session_start();
                    $_SESSION['username'] = $login_username;
                    $_SESSION['check'] = hash('ripemd128', $_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
                    die("<p><a href=admin_upload_files.php>Click here to Login the admin</a></p>");            
                }
                else{
                    //if fail to login
                    header('Refresh:3');
                    die(show_msg("it doesn't work<br><br>It will go back to prevoius page in 3 secs..."));
                }
            }
            else{
                header('Refresh:3');
                die(show_msg("<br>".$fail."<br>It will go back to prevoius page in 3 secs...<br>"));
            }
        }
        else{
            $_POST = array();
            display_login_page();
            echo"<p><br><a href=online_virus_check.php>Click here to go back to previous menu</a></p>";
        }
    }
    else if(isset($_POST['file_check_page'])|| isset($_POST['check'])){
        display_check_file_page();
        echo"<p><br><a href=online_virus_check.php>Click here to go back to previous menu</a></p>";
        if(isset($_POST['check'])){
            if(!$_FILES || empty($_FILES['putative_file']['tmp_name'])){
                die(show_msg("the file should not be empty"));
            }
            
            $fail = validate_file_size($_FILES['putative_file']['size']);
            if($fail===""){
                check_putative_file($conn);
            }
            else{
                echo $fail;
            }
        }
    }
    else{
        display_login_button();
        display_check_button();
        $_POST=array();
    }
    
    $conn->close();
    
    
    
    
     function display_login_button(){
        echo<<<_END
        <html><body>    
        <form action='online_virus_check.php'  method='post' enctype='multipart/form-data'> 
        <b>If you want to uploud virus files, you have to login your account first</b><br>
        <br><input type='submit' name='admin_login_page' value='Click Here to Login'><br>
        
        </body></html>
_END;
    }
    
    
    function display_check_button(){
        echo<<<_END
        <html><body>    
        <form action='online_virus_check.php'  method='post' enctype='multipart/form-data'>
        <br><b>If you just want to check a putative infected files, you can click the button below</b><br>
        <br><input type='submit' name='file_check_page' value='Click Here to Check'>
        
        </body></html>
_END;
    }
    
    //display the admin signup page
    function display_login_page(){
       echo<<<_END
        <html><body>
        
        <script type="text/javascript" src="validate_form.js"></script>
        
        <form action='online_virus_check.php' method='post' onSubmit='return validateLogin(this)' enctype='multipart/form-data'>
        <b>You can login the admin account and upload files here if you have an admin account:</b>
        <br><br>Username:
        <br>(username must be longer than 6, don't contain any space, and only contain a-z, A-Z, 0-9, - and _)
        <br><input type='text' name='login_username' size=10>
        <br><br>Password:
        <br>password must be longer than 6, and as least contain one lowercase, one uppercase, and one digital number
        <br><input type='password', name='login_pw', size=10>
        <br><input type='submit' name='login' value='login'>
        </body></html>
_END;
    }

    //display the check file page
    function display_check_file_page(){
        echo<<<_END
        <html><body>
        <script type="text/javascript" src="validate_form.js"></script>
        <form action='online_virus_check.php' method='post' onSubmit='return validateUploadFile2()' enctype='multipart/form-data'>
        <b>You can submit the putative file you want to check here</b><br>
        <br>Select File: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='file' id='putative_file' name='putative_file' size=10>
        <br><br><input type='submit' name='check' value='Check'>   
        </body></html>
_END;
    }
    
    
    //check if the username or password contains space, if the username exists, if the password matches
    function check_login_info($conn, $username, $pw){
        
        $query = "SELECT * FROM users WHERE username='$username'";
        $sql = mysqli_query($conn, $query);
        if(!$sql){ 
            $sql->close();
            return false;
        }
        
        $row = mysqli_fetch_array($sql, MYSQLI_BOTH);
        $sql->close();
        //check if the username exists
        if($row){
            if($row['password'] !== hash('ripemd128', $row['saltstring_1'].$pw.$row['saltstring_2'])){ return false; }
        }
        else{
            return false;
        }
        return true;
    }
    
    function check_putative_file($conn){
        if($_FILES['putative_file']['error']===0 or die(show_msg("the file doesn't work"))){
            //sanitize the temp name of the file
            $file_temp_name = mysql_entities_fix_string($conn, $_FILES['putative_file']['tmp_name']);
            $file_handler = fopen($file_temp_name, 'rb');
            
            //read the bytes from the file
            if($file_handler){
                $file_content = file_get_contents($file_temp_name);
                fclose($file_handler);
            }
            else{ die(show_msg("it doesn't work"));}
            
            $bytes_string = "";
            //get the first 20 bytes
            for($i=0; $i<20; $i++){
                $bytes_string.= dechex(ord($file_content[$i]));  
            }
            echo "<be>putative file signature: ".$bytes_string."<br>";
            
            $query = "SELECT * FROM infected_files WHERE files_signature = '$bytes_string' ";
            $sql = $conn->query($query);
            if(!$sql){
                $sql->close();
                header('Refresh:3');
                die(show_msg("there are some technical problems...Pleas visit our website later..."));
            }
            //if any virus file uploaded by admin has the same signature with the putative file, the $rows should >0
            $rows = $sql->num_rows;
            
            if($rows){
                echo "<br><b>The putative file is infected!!!</b><br>";
            }
            else{
                echo "<br><b>It's a benign file:)</b><br>";
            }
            
            for($i=0; $i<$rows; $i++){
                $sql->data_seek($i);
                $row = $sql->fetch_array(MYSQLI_BOTH);
                echo "<br><br><b>Virus name:</b> ".$row['files_name']."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                echo "<b>Virus Signature:</b>".$row['files_signature']."<br>";
            }
            $sql->close();
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
    
   
?>

