<?php

class Customer { 
    public $id;
    public $name;
    public $email;
    public $mobile;
	public $username;
	public $password; //text from HTML form
	public $password_hash; //hased password
	public $newPassword;
	public $confirmNewPassword;
	private $sessionid = null;
    private $noerrors = true;
    private $nameError = null;
    private $emailError = null;
    private $mobileError = null;
	private $usernameError = null;
	private $passwordError = null;
	private $confirmCodeError = null;
	private $confirmPasswordError = 'Changing password is optional';
    private $title = "Customer";
    private $tableName = "customers";
    
	function login_view() { // dsiplay login page
		$this->generate_html_top(0);
		$this->generate_form_group("username", $this->usernameError, $this->username);
		$this->generate_form_group("password", $this->passwordError, $this->password, "", "password");
		$this->generate_html_bottom(0);
	}
	
    function create_record() { // display "create" form
        $this->generate_html_top (1);
        $this->generate_form_group("name", $this->nameError, $this->name, "autofocus");
        $this->generate_form_group("email", $this->emailError, $this->email);
        $this->generate_form_group("mobile", $this->mobileError, $this->mobile);
		$this->generate_form_group("password", $this->passwordError, $this->password, "", "password");
		$this->display_file_upload();
        $this->generate_html_bottom (1);
    } // end function create_record()
    
    function read_record($id) { // display "read" form
        $this->select_db_record($id);
        $this->generate_html_top(2);
		$this->display_photo();
        $this->generate_form_group("name", $this->nameError, $this->name, "disabled");
        $this->generate_form_group("email", $this->emailError, $this->email, "disabled");
        $this->generate_form_group("mobile", $this->mobileError, $this->mobile, "disabled");
        $this->generate_html_bottom(2);
    } // end function read_record()
    
    function update_record($id) { // display "update" form
        if($this->noerrors) $this->select_db_record($id);
        $this->generate_html_top(3, $id);
        $this->generate_form_group("name", $this->nameError, $this->name, "autofocus onfocus='this.select()'");
        $this->generate_form_group("email", $this->emailError, $this->email);
        $this->generate_form_group("mobile", $this->mobileError, $this->mobile);
		$this->generate_form_group("password", $this->passwordError, $this->password, "", "password");
		$this->generate_form_group("NewPassword", null, $this->newPassword, "", "password");
		$this->generate_form_group("ConfirmNewPassword", $this->confirmPasswordError, $this->confirmNewPassword, "", "password");
		$this->display_file_upload();
        $this->generate_html_bottom(3);
    } // end function update_record()
    
    function delete_record($id) { // display "read" form
        $this->select_db_record($id);
        $this->generate_html_top(4, $id);
        $this->generate_form_group("name", $this->nameError, $this->name, "disabled");
        $this->generate_form_group("email", $this->emailError, $this->email, "disabled");
        $this->generate_form_group("mobile", $this->mobileError, $this->mobile, "disabled");
        $this->generate_html_bottom(4);
    } // end function delete_record()
	
	function confirm_page() {
		$this->generate_html_top(5);
		$this->generate_form_group("code", $this->confirmCodeError, "", "autofocus");
		$this->generate_form_group("password", $this->passwordError, $this->password, "", "password");
		$this->generate_html_bottom(5);
	}
    
    /*
     * This method inserts one record into the table, 
     * and redirects user to List, IF user input is valid, 
     * OTHERWISE it redirects user back to Create form, with errors
     * - Input: user data from Create form
     * - Processing: INSERT (SQL)
     * - Output: None (This method does not generate HTML code,
     *   it only changes the content of the database)
     * - Precondition: Public variables set (name, email, mobile)
     *   and database connection variables are set in datase.php.
     *   Note that $id will NOT be set because the record 
     *   will be a new record so the SQL database will "auto-number"
     * - Postcondition: New record is added to the database table, 
     *   and user is redirected to the List screen (if no errors), 
     *   or Create form (if errors)
     */
    function insert_db_record () {
		if (isset($_SESSION['name'])) $this->name = $_SESSION['name'];
		if (isset($_SESSION['email'])) $this->email = $_SESSION['email'];
		if (isset($_SESSION['mobile'])) $this->mobile = $_SESSION['mobile'];
		if (isset($_POST["password"]))   $this->password = htmlspecialchars($_POST["password"]);
		
		$fileDescription = $_POST['Description']; 
		$fileName       = $_FILES['Filename']['name'];
		$tempFileName   = $_FILES['Filename']['tmp_name'];
		$fileSize       = $_FILES['Filename']['size'];
		$fileType       = $_FILES['Filename']['type'];
		
		if($fileSize > 2000000) { echo "Error: file exceeds 2MB."; exit(); }
		
		// put the content of the file into a variable, $content
		$content = file_get_contents($tempFileName);
		if ($fileName != "") {
			$filePath = substr($this->get_current_url(), 0, 44);
			$filePath = $filePath . "images/" . $fileName;
		}
		
        if ($this->fieldsAllValid ()) { // validate user input
			$this->save_file_to_directory();
			if ($this->check_email()) { 
				// if valid data, insert record into table
				$pdo = Database::connect();
				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->password_hash = MD5($this->password);
				$sql = "INSERT INTO $this->tableName (name,email,mobile,password_hash,filename,filepath,filedescription,filetype,filesize,filecontent) values(?, ?, ?, ?, ?, ?, ?, ? ,? ,?)";
				$q = $pdo->prepare($sql);
				$q->execute(array($this->name,$this->email,$this->mobile,$this->password_hash,$fileName,$filePath,$fileDescription,$fileType,$fileSize,$content));
				
				Database::disconnect();
				if (isset($_SESSION["user_id"])){
					header("Location: $this->tableName.php?fun=display_list"); // go back to "list"
					echo  "chodna";
				}
				else header("Location: $this->tableName.php"); //go to login
			}
			else {
				$this->emailError = 'This email has already been registered!';
				$this->create_record();
            }
        }
        else {
            // if not valid data, go back to "create" form, with errors
            // Note: error fields are set in fieldsAllValid ()method
            $this->create_record(); 
        }
    } // end function insert_db_record
    
    private function select_db_record($id) {
        $pdo = Database::connect();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "SELECT * FROM $this->tableName where id = ?";
        $q = $pdo->prepare($sql);
        $q->execute(array($id));
        $data = $q->fetch(PDO::FETCH_ASSOC);
        Database::disconnect();
        $this->name = $data['name'];
        $this->email = $data['email'];
        $this->mobile = $data['mobile'];
    } // function select_db_record()
    
    function update_db_record ($id) {
        $this->id = $id;
		if(isset($_POST["name"]))       $this->name = htmlspecialchars($_POST["name"]);
		if(isset($_POST["email"]))  	$this->email = htmlspecialchars($_POST["email"]);
		if(isset($_POST["mobile"]))     $this->mobile = htmlspecialchars($_POST["mobile"]);
		$this->newPassword = htmlspecialchars($_POST["NewPassword"]);
		$this->confirmNewPassword = htmlspecialchars($_POST["ConfirmNewPassword"]);
		
		$fileDescription = $_POST['Description']; 
		$fileName       = $_FILES['Filename']['name'];
		$tempFileName   = $_FILES['Filename']['tmp_name'];
		$fileSize       = $_FILES['Filename']['size'];
		$fileType       = $_FILES['Filename']['type'];
		
		if($fileSize > 2000000) { echo "Error: file exceeds 2MB."; exit(); }
		
		// put the content of the file into a variable, $content
		$content = file_get_contents($tempFileName);
		if ($fileName != "") {
			$filePath = substr($this->get_current_url(), 0, 44);
			$filePath = $filePath . "images/" . $fileName;
		}
		
        if ($this->fieldsAllValid ()) {
            $this->noerrors = true;
			if ($this->check_password()) {
				
				$pdo = Database::connect();
				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$sql = "UPDATE $this->tableName  set name = ?, email = ?, mobile = ? WHERE id = ?";
				$q = $pdo->prepare($sql);
				$q->execute(array($this->name,$this->email,$this->mobile,$this->id));
				
				if($fileName != "") {
					$this->save_file_to_directory();
					$sql = "UPDATE $this->tableName  set filename = ?,filesize = ?,filetype = ?,filecontent = ?,filepath = ?,filedescription = ? WHERE id = ?";
					$q = $pdo->prepare($sql);
					$q->execute(array($fileName,$fileSize,$fileType,$content,$filePath,$fileDescription,$this->id));
				}
				
				Database::disconnect();
				$this->newPassword = null;
				$this->confirmNewPassword = null;
				header("Location: $this->tableName.php?fun=display_list");
			}
        }
        else {
			$this->newPassword = null;
			$this->confirmNewPassword = null;
            $this->noerrors = false;
            $this->update_record($id);  // go back to "update" form
        }
    } // end function update_db_record 
    
    function delete_db_record($id) {
        $pdo = Database::connect();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "DELETE FROM $this->tableName WHERE id = ?";
        $q = $pdo->prepare($sql);
        $q->execute(array($id));
        Database::disconnect();
        header("Location: $this->tableName.php?fun=display_list");
    } // end function delete_db_record()
	
	function display_photo() {
		if (isset($_GET['id'])){
			$id = $_GET['id'];
			echo "<div> ";
			
			$pdo = Database::connect();
			$sql = "SELECT * FROM $this->tableName WHERE id = $id";
			$data = $pdo->query($sql);
			$row = $data->fetch(PDO::FETCH_ASSOC);
			
			if ($row["filesize"] > 0) {
				echo "<img height='100' width='100' src = 'data:image/jpeg;base64," . base64_encode($row["filecontent"]). "' />";
			}
			else {
				echo "<p>No photo</p>";
			}
			
			echo "</div> <br>";
			Database::disconnect();
		}
		else {
			echo "ID is not set";
		}
	}
	
	function save_file_to_directory(){
		// set PHP variables from data in HTML form 
		$fileName       = $_FILES['Filename']['name'];
		$tempFileName   = $_FILES['Filename']['tmp_name'];
		$fileSize       = $_FILES['Filename']['size'];
		$fileType       = $_FILES['Filename']['type'];
		// $fileDescription = $_POST['Description']; // not used

		// set server location (subdirectory) to store uploaded files
		$fileLocation = "images/";
		$fileFullPath = $fileLocation . $fileName; 
		if (!file_exists($fileLocation))
			mkdir ($fileLocation); // create subdirectory, if necessary
		
		// if file does not already exist, upload it
		if (!file_exists($fileFullPath)) {
			$result = move_uploaded_file($tempFileName, $fileFullPath);
			if ($result) {
				/*
				echo "File <b><i>" . $fileName 
					. "</i></b> has been successfully uploaded.";
				// code below assumes filepath is same as filename of this file
				// minus the 12 characters of this file, "upload01.php"
				// plus the string, $fileLocation, i.e. "uploads/"
				echo "<br>To see all uploaded files, visit: " 
						. "<a href='"
						. substr($this->get_current_url(), 0, 44)
						. "$fileLocation'>" 
						. substr($this->get_current_url(), 0, 44) 
						. "$fileLocation</a>";
						*/
			} else {
				echo "Upload denied for file. " . $fileName 
					. "</i></b>. Verify file size < 2MB. ";
			}
		}
		// otherwise, show error message
		else {
			echo "File <b><i>" . $fileName 
				. "</i></b> already exists. Please rename file.";
		}
	}
	
	function get_current_url($strip = true) {
		$filter = "";
		$scheme; 
		$host;
		if (!$filter) {
			
			// sanitize
			$filter = function($input) use($strip) {
				$input = trim($input);
				if ($input == '/') {
					return $input;
				}

				// add more chars if needed
				$input = str_ireplace(["\0", '%00', "\x0a", '%0a', "\x1a", '%1a'], '', rawurldecode($input));

				// remove markup stuff
				if ($strip) {
					$input = strip_tags($input);
				}

				// encode
				// you can change encoding if you don't use utf-8
				$input = htmlspecialchars($input, ENT_QUOTES, 'utf-8');

				return $input;
			};

			$host = $_SERVER['SERVER_NAME'];
			$scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : ('http' . (($_SERVER['SERVER_PORT'] == '443') ? 's' : ''));
		}

		return sprintf('%s://%s%s', $scheme, $host, $filter($_SERVER['REQUEST_URI']));
	}
	
	function display_file_upload() {
		echo "
				<p>File</p>
				<input type='file' name='Filename'> 
				<p>Description</p>
				<textarea rows='10' cols='35' name='Description'></textarea>
				<p>*Min of 255 characters</p>
				<br/>";
	}
	

	
	function verify_email() {
		if (isset($_POST['code'])) {
			$theirCode = htmlspecialchars($_POST['code']);
			if ($theirCode == htmlspecialchars($_SESSION['conCode'])) {
				$this->insert_db_record();
			}
			else {
				$this->confirmCodeError = "Code does not match!";
				$this->confirm_page();
			}
		}
		else {
			$this->confirmCodeError = "Please enter code!";
			$this->confirm_page();
		}
	}
	
	private function check_email() {
		$valid = false;
		$pdo = Database::connect();
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$sql = $sql = "SELECT * FROM $this->tableName WHERE email = ? ";
		$q = $pdo->prepare($sql);
		$q->execute(array($this->email));
		$data = $q->fetch(PDO::FETCH_ASSOC);
		if (!($data)) {
			$valid = true; // valid email to register/create new user with
		}
		Database::disconnect();
		return $valid;
	}
   
	private function check_password() {
		$valid = true;
		$pdo = Database::connect();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->password_hash = MD5($this->password);
		$sql = "SELECT * FROM $this->tableName WHERE id = ? AND password_hash = ? ";
		$q = $pdo->prepare($sql);
		$q->execute(array($this->id,$this->password_hash));
		$data = $q->fetch(PDO::FETCH_ASSOC);
		
		if (!($data)) {
			Database::disconnect();
			$valid = false;
			$this->passwordError = 'Incorrect password, unable to change user information';
			$this->update_record($this->id);
		}
		if ($this->newPassword == $this->confirmNewPassword) {
			if ($this->newPassword != "" && $this->confirmNewPassword != ""){
				//update password
				$this->password = $this->newPassword;
				$this->password_hash = MD5($this->password);
				$sql = "UPDATE $this->tableName set password_hash = ? WHERE id = ?";
				$q = $pdo->prepare($sql);
				$q->execute(array($this->password_hash,$this->id));
			}
		}
		else {
			Database::disconnect();
			$valid = false;
			$this->confirmPasswordError = 'New passwords do not match';
			$this->update_record($this->id);
		}
		
		Database::disconnect();
		return $valid; // only returns valid, otherwise it exits method through a call of another method(I don't know if this is good practice)
	}
	
	function check_login() {
		if ($this->loginFieldsValid()) { // validate user input
            // if valid data, verify username and password
            $pdo = Database::connect();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->password_hash = MD5($this->password);
            $sql = "SELECT * FROM $this->tableName WHERE email = ? AND password_hash = ? ";
			$q = $pdo->prepare($sql);
			$q->execute(array($this->username,$this->password_hash));
			$data = $q->fetch(PDO::FETCH_ASSOC);
			if ($data) {
				//create session id
				$_SESSION['user_id'] = $data['id'];
				$this->sessionid = $data['id'];
				Database::disconnect();
				header("Location: $this->tableName.php?fun=display_list&id=$this->sessionid");
			}
			else {
				Database::disconnect();
				$this->usernameError = 'Invalid login information';
				$this->login_view();
			}
        }
        else {
            // if not valid data, go back to "login" form, with errors
            $this->login_view(); 
        }
	}
	
	function logout() {
		session_destroy();
		header("Location: $this->tableName.php");
	}
	
    private function generate_html_top ($fun, $id=null) {
        switch ($fun) {
			case 0: // login
				$funWord = "Login: "; $funNext = "check_login";
				break;
            case 1: // create
                $funWord = "Create a $this->title"; $funNext = "insert_db_record"; // change to send_email when server allows mail() function
                break;
            case 2: // read
                $funWord = "Read a $this->title"; $funNext = "none"; 
                break;
            case 3: // update
                $funWord = "Update a $this->title"; $funNext = "update_db_record&id=" . $id; 
                break;
            case 4: // delete
                $funWord = "Delete a $this->title"; $funNext = "delete_db_record&id=" . $id; 
                break;
			case 5: // confirm email
				$funWord = "Confirm a $this->title"; $funNext = "verify_email";
				break;
            default: 
                echo "Error: Invalid function: generate_html_top()"; 
                exit();
                break;
        }
        echo "<!DOCTYPE html>
        <html>
            <head>
                <title>$funWord</title>
                    ";
        echo "
                <meta charset='UTF-8'>
                <link href='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/css/bootstrap.min.css' rel='stylesheet'>
                <script src='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/js/bootstrap.min.js'></script>
                <style>label {width: 5em;}</style>
                    "; 
        echo "
            </head>";
        echo "
            <body>
				
                <div class='container'>
                    <div class='span10 offset1'>
                        <p class='row'>
                            <h3>$funWord</h3>
                        </p>
                        <form class='form-horizontal' action='$this->tableName.php?fun=$funNext' method='post' enctype='multipart/form-data'>                        
                    ";
    } // end function generate_html_top()
    
    private function generate_html_bottom ($fun) {
        switch ($fun) {
			case 0: // login
				$funButton = "<button type='submit' class='btn btn-secondary'>Login</button>";
				break;
            case 1: // create
                $funButton = "<button type='submit' class='btn btn-success'>Create</button>"; 
                break;
            case 2: // read
                $funButton = "";
                break;
            case 3: // update
                $funButton = "<button type='submit' class='btn btn-warning'>Update</button>";
                break;
            case 4: // delete
                $funButton = "<button type='submit' class='btn btn-danger'>Delete</button>"; 
                break;
			case 5: // confirm
				$funButton = "<button type='submit' class='btn btn-info'>Confirm</button>";
				break;
            default: 
                echo "Error: Invalid function: generate_html_bottom()"; 
                exit();
                break;
        }
        echo " 
                            <div class='form-actions'>
                                $funButton ";
		if ($fun == 0) {
			echo 				"<a class='btn btn-success' href='$this->tableName.php?fun=display_create_form'>Join</a>";
		}
		else if ($fun == 5) {
			echo "
                                <a class='btn btn-secondary' href='$this->tableName.php'>Back to Login</a>";
		}
		else {
			echo "
                                <a class='btn btn-secondary' href='$this->tableName.php?fun=display_list'>Back</a>";
		}
		echo "
                            </div>
                        </form>
                    </div>

                </div> <!-- /container -->
            </body>
        </html>
                    ";
    } // end function generate_html_bottom()
    
    private function generate_form_group ($label, $labelError, $val, $modifier="", $fieldType="text") {
        echo "<div class='form-group";
        echo !empty($labelError) ? ' alert alert-danger ' : '';
        echo "'>";
        echo "<label class='control-label'>$label &nbsp;</label>";
        //echo "<div class='controls'>";
        echo "<input "
            . "name='$label' "
            . "type='$fieldType' "
            . "$modifier "
            . "placeholder='$label' "
            . "value='";
        echo !empty($val) ? $val : '';
        echo "'>";
        if (!empty($labelError)) {
            echo "<span class='help-inline'>";
            echo "&nbsp;&nbsp;" . $labelError;
            echo "</span>";
        }
        //echo "</div>"; // end div: class='controls'
        echo "</div>"; // end div: class='form-group'
    } // end function generate_form_group()
    
	private function loginFieldsValid(){
		$valid = true;
		if (empty($this->username)) {
			$this->usernameError = 'Please enter username';
			$valid = false;
		}
		if (empty($this->password)) {
			$this->passwordError = 'Please enter password';
			$valid = false;
		}
		return $valid;
	}
	
    private function fieldsAllValid () {
        $valid = true;
        if (empty($this->name)) {
            $this->nameError = 'Please enter Name';
            $valid = false;
        }
        if (empty($this->email)) {
            $this->emailError = 'Please enter Email Address';
            $valid = false;
        } 
        else if ( !filter_var($this->email,FILTER_VALIDATE_EMAIL) ) {
            $this->emailError = 'Please enter a valid email address: me@mydomain.com';
            $valid = false;
        }
        if (empty($this->mobile)) {
            $this->mobileError = 'Please enter Mobile phone number';
            $valid = false;
        }
		if (empty($this->password)){
			$this->passwordError = 'Please enter a password';
			$valid = false;
		}
        return $valid;
    } // end function fieldsAllValid() 
	
	function list_pics() {
		 echo "<!DOCTYPE html>
        <html>
            <head>
                <title>$this->title" . "s" . "</title>
                    ";
        echo "
                <meta charset='UTF-8'>
                <link href='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/css/bootstrap.min.css' rel='stylesheet'>
                <script src='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/js/bootstrap.min.js'></script>
                    ";  
        echo "
            </head>
            <body>
                <a button type='submit' class='btn btn-success'href='https://github.com/sabbi3267/login' target='_blank'>Github</a><br />
				<a button type='submit' class='btn btn-success' href='https://cis355-noor.000webhostapp.com/?dir=./images/' target='_blank'>files saved here</a><br />
				
                <div class='container'>
                    <p class='row'>
                        <h3>Pictures</h3>
                    </p>
					<p>
						<a href='$this->tableName.php?fun=display_list' class='btn btn-secondary'>Back</a>
					</p>
					
                    <div class='row'>
                        <table class='table table-striped table-bordered'>
                            <thead>
                                <tr>
                                    <th>File Name</th>
                                 
                                    <th>Description</th>
                                    <th>Photo</th>
                                </tr>
                            </thead>
                            <tbody>
                    ";
        $pdo = Database::connect();
        $sql = "SELECT * FROM $this->tableName ORDER BY id DESC";
        foreach ($pdo->query($sql) as $row) {
            echo "<tr>";
            echo "<td>". $row["filename"] . "</td>";
           
            echo "<td>". $row["filedescription"] . "</td>";
            echo "<td>" . $row["name"] . "<br>";
			if ($row["filesize"] > 0) {
				echo "<img height='100' width='100' src = 'data:image/jpeg;base64," . base64_encode($row["filecontent"]). "' />";
			}
			else {
				echo "<p>No photo</p>";
			}
            echo "</td>";
            echo "</tr>";
        }
        Database::disconnect();        
        echo "
                            </tbody>
                        </table>
                    </div>
                </div>

            </body>

        </html>
                    ";  
	}
	
    function list_records() {
        echo "<!DOCTYPE html>
        <html>
            <head>
                <title>$this->title" . "s" . "</title>
                    ";
        echo "
                <meta charset='UTF-8'>
                <link href='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/css/bootstrap.min.css' rel='stylesheet'>
                <script src='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/js/bootstrap.min.js'></script>
                    ";  
        echo "
            </head>
            <body>
                <a button type='submit' class='btn btn-success'href='https://github.com/sabbi3267/login' target='_blank'>Github</a><br />
				<a button type='submit' class='btn btn-success'href='https://github.com/sabbi3267/login/blob/master/uml.jpg' target='_blank'>Uml</a><br />
				
				<a button type='submit' class='btn btn-success' href='https://github.com/sabbi3267/login/blob/master/ufg.png' target='_blank'>User Flow Diagram</a><br /><div class='container'>
                    <p class='row'>
                        <h3>$this->title" . "s" . "</h3>
                    </p>
					<p>
						<a href='$this->tableName.php?fun=logout' class='btn btn-warning'>Log Out</a>
					</p>
					<p>
						
						<a href='$this->tableName.php?fun=list_pics' class='btn btn-info'> User table with Pictures</a>
					</p>
                    <p>
                        <a href='$this->tableName.php?fun=display_create_form' class='btn btn-success'>Create</a>
                    </p>
                    <div class='row'>
                        <table class='table table-striped table-bordered'>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                    ";
        $pdo = Database::connect();
        $sql = "SELECT * FROM $this->tableName ORDER BY id DESC";
        foreach ($pdo->query($sql) as $row) {
            echo "<tr>";
            echo "<td>". $row["name"] . "</td>";
            echo "<td>". $row["email"] . "</td>";
            echo "<td>". $row["mobile"] . "</td>";
            echo "<td width=250>";
            echo "<a class='btn btn-info' href='$this->tableName.php?fun=display_read_form&id=".$row["id"]."'>Read</a>";
            echo "&nbsp;";
            echo "<a class='btn btn-warning' href='$this->tableName.php?fun=display_update_form&id=".$row["id"]."'>Update</a>";
            echo "&nbsp;";
            echo "<a class='btn btn-danger' href='$this->tableName.php?fun=display_delete_form&id=".$row["id"]."'>Delete</a>";
            echo "</td>";
            echo "</tr>";
        }
        Database::disconnect();        
        echo "
                            </tbody>
                        </table>
                    </div>
                </div>

            </body>

        </html>
                    ";  
    } // end function list_records()
    
} // end class Customer