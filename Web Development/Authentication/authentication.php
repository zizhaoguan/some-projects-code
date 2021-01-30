<?php
    require_once 'login.php';
    
    $conn = connect_mysql($hn, $un, $pw, $db);
   
    if(  isset($_POST['login']) && !empty($_POST['login_username']) && !empty($_POST['login_pw'])){
        //if 'login' button is clicked, and the information is filled
        login($conn);
    }
    else if(isset($_POST['login']) &&( empty($_POST['login_username']) || empty($_POST['login_pw'])) ){
        //if 'login' button is clicked, but the information is empty
        echo "<br>some field cannot be empty<br>";
        display_login();
        echo "<br><br>";
        display_signup();   
    }
    else if( isset($_POST['signup']) && !empty($_POST['signup_username']) && !empty($_POST['signup_pw']) && !empty($_POST['confirm_pw']) ){
        //if 'signup' button is clicked, and the infomation is filled
        signup($conn);
    }
    else if( isset($_POST['signup']) && (empty($_POST['signup_username']) || empty($_POST['signup_pw'])|| empty($_POST['confirm_pw']) ) ){
        //if 'signup' button is clicked, and the infomation is empty
        echo "<br>some field cannot be empty<br>";
        display_login();
        echo "<br><br>";
        display_signup();
    }
    else if(isset($_POST['add_content'])){
        //if 'add content' button is clicked
        login($conn);
    }
    else if(isset($_POST['logout'])){
        //if 'logout' button is clicked
        $_POST = array();
        display_login();
        echo "<br><br>";
        display_signup();
    }
    else{
        //defualt display of homepage
        display_login();
        echo "<br><br>";
        display_signup();
    }
    echo "<br><br>";
    
    $conn->close();

    //connect the database and check
    function connect_mysql($hn, $un, $pw, $db){
        $conn = new mysqli($hn, $un, $pw, $db);
        if($conn->connect_error) { die(show_msg("it doesn't work")); }
        return $conn;
    }
    
    
    //print an error img if meet an error
    function show_msg($msg){
        echo<<<_END
        <br>we are sorry, $msg<br>
        <img src="https://i.ibb.co/Wx5nwnz/error-pixil.gif">
_END;
    }
    
    
    //display login box
    function display_login(){
       echo<<<_END
        <html><body>
        <form action='authentication.php' method='post' enctype='multipart/form-data'>
        <b>Please use username and password to login:</b>
        <br><br>Username:
        <br><input type='text' name='login_username' size=10>
        <br>Password:
        <br><input type='password', name='login_pw', size=10>
        <br><input type='submit' name='login' value='login'>
        </body></html>
_END;
    }
    
    //display signup box
    function display_signup(){
       echo<<<_END
        <html><body>
        <form action='authentication.php' method='post' enctype='multipart/form-data'>
        <b>You can signup here if you don't have an account:</b>
        <br><br>Username:
        <br><input type='text' name='signup_username' size=10>
        <br>Password:
        <br><input type='password', name='signup_pw', size=10>
        <br>Confirm Passoword:
        <br><input type='password', name='confirm_pw', size=10>
        <br><input type='submit' name='signup' value='signup'>
        </body></html>
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
    
    //method to signup user
    function signup($conn){
        
        $username_temp = mysql_entities_fix_string($conn, $_POST['signup_username']);
        $signup_pw_temp = mysql_entities_fix_string($conn, $_POST['signup_pw']);
        $confirm_pw_temp = mysql_entities_fix_string($conn, $_POST['confirm_pw']);
        $good_signup_info = check_signup_info($username_temp, $signup_pw_temp, $confirm_pw_temp);
        
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
            die(show_msg("it doesn't work"));
        }
        else{
            echo "<br><b>account created successfully!!! Now, go back to homepage and login</b><br>";
            header('Refresh:3');
            $stmt->close();
            die();
        }       
    }
    
    //method to login user
    function login($conn){
        
        $username_temp = mysql_entities_fix_string($conn, $_POST['login_username']);
        $login_pw_temp = mysql_entities_fix_string($conn, $_POST['login_pw']);
        
        $check_if_login = check_login_info($conn, $username_temp, $login_pw_temp);
        if($check_if_login){
            add_content($conn, $username_temp, $login_pw_temp);
            fetch_contents($conn, $username_temp);
        }
        
    }
    
    //check if the password and confirm password are the same
    //check if the username, password, and confirm password have space
    function check_signup_info($username, $pw, $confirm_pw){
        if($pw != $confirm_pw){
            //echo "<br><b>it doesn't work</b><br>";
            header('Refresh:3');
            die(show_msg("the password and confirm password are not the same<br><br>you will go back to the previous page"));
        }
        
        if(strpos($username,' ')>0 || strpos($pw,' ')>0 ||strpos($confirm_pw,' ')>0){
            //echo "<br><b>it doesn't work</b><br>";
            header('Refresh:3');
            die(show_msg("it shouldn't contain any space<br><br>you will go back to the previous page"));
        }
    }
    
    //check if the username or password contains space, if the username exists, if the password matches
    function check_login_info($conn, $username, $pw){
        
        //check if the username or password contains space
        if(strpos($username,' ')>0 || strpos($pw,' ')>0){
            //echo "<br><b>it doesn't work</b><br>";
            header('Refresh:3');
            die(show_msg("it shouldn't contain space<br><br>you will go back to the previous page"));
        }
       
        $query = "SELECT * FROM users WHERE username='$username'";
        $sql = mysqli_query($conn, $query);
        if(!$sql){ 
            header('Refresh:3');
            $sql->close();
            die(show_msg("it doesn't work<br><br>you will go back to the previous page"));
        }
        
        $row = mysqli_fetch_array($sql, MYSQLI_BOTH);
        $sql->close();
        //check if the username exists
        if($row){
            if($row['password'] != hash('ripemd128', $row['saltstring_1'].$pw.$row['saltstring_2'])){
                header('Refresh:3');
                die(show_msg("failed to login<br><br>you will go back to the previous page"));
            }
            echo "<br>welcome!  ".$row['username'];
            logout();
            echo "<br><br>";
        }
        else{
            header('Refresh:3');
            die(show_msg("failed to login<br><br>you will go back to the previous page"));
        }
        return true;
    }
    
    function add_content($conn, $username, $pw){
        echo<<<_END
        <html><body>
        <form action='authentication.php' method='post' enctype='multipart/form-data'>
        <b>You can add content here:</b>
        <br><br>Content Name: <input type='text' name='content_name' size=10>
        
        <br><br>Select File: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='file' name='filename' size=10>
        <input type='hidden' name='login_username', value='$username'>
        <input type='hidden' name='login_pw', value='$pw'>
        <br><br><input type='submit' name='add_content' value='Add Content'>
        </body></html>
_END;
        if(isset($_POST['add_content'])){
            
            if(empty($_POST['content_name'])){
                die(show_msg("it shouldn't be empty"));
            }
            if(!$_FILES || empty($_FILES['filename']['tmp_name'])){
                die(show_msg("it doesn't work"));
            }
            $content_name = trim(mysql_entities_fix_string($conn, $_POST['content_name']));
            $username = mysql_entities_fix_string($conn, $username);
            $file_content = upload_file($conn);
            //echo "<br>".$file_content."<br>";

            $stmt = $conn->prepare('INSERT INTO contents VALUES(?,?,?)');
            $stmt->bind_param('sss', $content_name, $file_content, $username);
            $stmt->execute();
            if($stmt->affected_rows<1){
                $stmt->close();
                //header('Refresh:3');
                die(show_msg("it doesn't work"));
            }
            else{
                $stmt->close();
                echo "<br><b>added content successfully</b><br>";
                //header('Refresh:3');
            }     
        }
    }
    
    function upload_file($conn){
        if($_FILES['filename']['error']===0 or die(show_msg("the file doesn't work"))){
            if($_FILES['filename']['type']=='text/plain' 
                    or die(show_msg("the file tpye you uploaded should be txt"))){
                echo "<br>checked: It's a txt file<br><br>";
                $file_name = mysql_entities_fix_string($conn, $_FILES['filename']['tmp_name']);
                
                $file_handler = fopen($file_name, 'r');
                if($file_handler){
                    $file_content = file_get_contents($file_name);
                    fclose($file_handler);
                    //echo "<br>".$file_content."<br>";
                    return mysql_entities_fix_string($conn, $file_content);
                    //return sanitizeString($file_content);
                }
                else{
                    die(show_msg("it doesn't work"));
                }
               
            }
        }
    }
    
    //fetch the contents of that logined user
    function fetch_contents($conn, $username){
        $query = "SELECT * FROM contents WHERE username = '$username' ";
        $sql = $conn->query($query);
        if(!$sql){
            $sql->close();
            header('Refresh:3');
            die(show_msg("it doesn't work"));
        }
        
        $rows = $sql->num_rows;
        
        for($i=0; $i<$rows; $i++){
            $sql->data_seek($i);
            $row = $sql->fetch_array(MYSQLI_BOTH);
            echo "<br><br><b>Content Name:</b> ".$row['content_name']."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
            echo "<b>Username:</b>".$row['username']."<br>";
            echo "<br><b>Content File:</b><br>".$row['content_file']."<br>";
        }
        $sql->close();
    }
    
    function logout(){
        echo<<<_END
        <html><body>
        <form action='authentication.php' method='post' enctype='multipart/form-data'>
        <br><input type='submit' name='logout' value='Logout'>
        </body></html>
_END;
    }
    
?>
