<?php
    require_once 'login.php';
    require_once 'validate_form.php';
    $conn = connect_mysql($hn, $un, $pw, $db);
    
    ini_set('session.gc_maxlifetime', 60*60*24);
    session_start();
    session_security();
     
    if(isset($_POST['logout'])){
        destroy_session_and_data();
        echo "<b>log out successfully, the page will be back to login in 3 secs</b><br>";
        $_POST=array();
        header("refresh:3;url=online_virus_check.php");
        
        exit;
    }
    else{ 
        if(isset($_POST['upload_page'])|| isset($_POST['upload'])){
            echo "<b>Upload Page</b><br><br>";
            display_upload_file();
            echo"<p><br><a href=admin_upload_files.php>Click here to go back to main menu</a></p>";
            if(isset($_POST['upload'])){
                if( !empty($_POST['file_name']) or die(show_msg("the name should not be empty"))){
                    //sanitizer the virus name input by user
                    $virus_name = mysql_entities_fix_string($conn, $_POST['file_name']);
                }
       
                if(!$_FILES || empty($_FILES['upload_file']['tmp_name'])){
                    die(show_msg("the file should not be empty"));
                }
                $fail = validate_file_name($virus_name);
                $fail.= validate_file_size($_FILES['upload_file']['size']);
                
                if($fail===""){
                    store_content_to_database($conn, $virus_name);
                }
                else{
                    echo $fail;
                }
            }
            fetch_contents($conn); 
        }
        else{
            echo "<b>Main Menu</b><br><br>";
            $username = $_SESSION['username'];
            echo "Welcome back! ".$username; 
            display_upload_virus_page();
            echo "<br>";
            display_logout();
            $_POST=array();
        }
    }
    
    $conn->close();

    //destroy the session and data
    function destroy_session_and_data(){
        $_SESSION = array();
        setcookie(session_name(), '', time()-259000, '/');
        session_destroy();
        echo "<br>session is destroyed<br>";
    }
    
    //prevent hijacking and fixation
    function session_security(){

        if(!isset($_SESSION['initiated'])){
            
            session_regenerate_id();
            $_SESSION['initiated'] = 1;
        }
          
        if(!isset($_SESSION['check']) || $_SESSION['check'] !== hash('ripemd128', $_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']) ){
            show_msg("due to the technical problem...please login again...it will be back to login in 3 secs");
            destroy_session_and_data();
            header("refresh:3;url=online_virus_check.php");
            exit;
        }
    }
    
    //display the button for uploading virus page
    function display_upload_virus_page(){
        echo<<<_END
        <html><body>    
        <form action='admin_upload_files.php'  method='post' enctype='multipart/form-data'> 
        <br><br><input type='submit' name='upload_page' value='Upload Virus Files'>
        
        </body></html>
_END;
    }
    
    //display the unload form
    function display_upload_file(){
       echo<<<_END
        <html><body>
        <b>You can upload malware file here:</b>
        
        <script type="text/javascript" src="validate_form.js"></script>
        <form action='admin_upload_files.php'  method='post' onSubmit='return validateUploadFile1(this)'  enctype='multipart/form-data'>
        <br>(file name can only contain number, English letter, and no space)<br>
        <br>file name:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='text' name='file_name' size=10>
        <br><br>Select File: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='file' id='upload_file' name='upload_file' size=10>
        <br><br><input type='submit' name='upload' value='upload'>
        </body></html>
_END;
    }
    
    //display the logout
     function display_logout(){
        echo<<<_END
        <html><body>
        <form action='admin_upload_files.php' method='post' enctype='multipart/form-data'>
        <br><input type='submit' id="logout" name='logout' value='Log Out'>
        </body></html>
_END;
    }
   
    
    //save the virus name and signature to the database
    function store_content_to_database($conn, $virus_name){
        if($_FILES['upload_file']['error']===0 or die(show_msg("the file doesn't work"))){
            //sanitize the temp name of the file
            $file_temp_name = mysql_entities_fix_string($conn, $_FILES['upload_file']['tmp_name']);
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
                
                //$bytes[] = sanitizeString(dechex(ord($file_content[$i])));
                //echo $bytes[$i]."<br>";
                $bytes_string.= dechex(ord($file_content[$i]));  
            }
            //convert the bytes array into storable data string
            //$bytes_str = serialize($bytes);
            //unserialize($bytes_str);
            //$bytes_storable = sanitizeString($bytes_str); //sanitize the string before store it to the database
            //unserialize($bytes_storable);
            
            $stmt = $conn->prepare('INSERT INTO infected_files VALUES(?,?)');
            $stmt->bind_param('ss', $virus_name, $bytes_string);
            $stmt->execute();
            
            if($stmt->affected_rows<1){
                $stmt->close();
                //header('Refresh:3');
                die(show_msg("something went wrong......"));
            }
            else{
                $stmt->close();
                echo "<br><b>added file successfully</b><br>";
                //header('Refresh:3');
            }     
        }
    }
    
    //fetch the contents of that logined user
    function fetch_contents($conn){
        $query = "SELECT * FROM infected_files";
        $sql = $conn->query($query);
        if(!$sql){
            $sql->close();
            header('Refresh:3');
            die(show_msg("there are some technical problems...Pleas visit our website later..."));
        }
        
        $rows = $sql->num_rows;
        
        for($i=0; $i<$rows; $i++){
            $sql->data_seek($i);
            $row = $sql->fetch_array(MYSQLI_BOTH);
            echo "<br><br><b>Virus Name:</b> ".$row['files_name']."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
            
            //$file_signature = unserialize($row['files_signature']);
            echo "<b>Virus Signature:</b>".$row['files_signature']."<br>";
        }
        $sql->close();
    }
    
    
    //connect to database
    function connect_mysql($hn, $un, $pw, $db){
        $conn = new mysqli($hn, $un, $pw, $db);
        if($conn->connect_error) { die(show_msg("it doesn't work")); }
        return $conn;
    }
    
    //show the error page
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