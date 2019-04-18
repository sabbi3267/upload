// code partially copied from https://csis.svsu.edu/~gpcorser/cis355wi19/crud_oo_complete
<?php

class Customer { 
	public $id;
    	public $name;
    	public $email;
    	public $mobile;
    	public $username;
	public $password; //text from HTML form
	public $password_hash; //hased password
	private $sessionid = null;
    	private $noerrors = true;
    	private $nameError = null;
    	private $emailError = null;
    	private $mobileError = null;
	private $title = "Customer";
    	private $tableName = "customers";
	/*
     * This method displays the create page form, 
     * - Input: click incedent
     * - Processing: process HTML code
     * - Output: HTML code for create page
     * - Pre-condition: If there is nothing in the list takes it to it this page automatically
     * - Post-conditon: After the input goes back to customers.php
     */
    
    function create_record() { // displays "create" form on page
        $this->generate_html_top (1);
        $this->generate_form_group("name", $this->nameError, $this->name, "autofocus");
        $this->generate_form_group("email", $this->emailError, $this->email);
        $this->generate_form_group("mobile", $this->mobileError, $this->mobile);
        $this->generate_form_group("password", $this->passwordError, $this->password, "", "password");
        $this->generate_html_bottom (1);
		$this->display_file_upload();
    } // end function create_record()
    
	function login_view() { // dsiplay login page
		 header("Location: $this->login.php");
	}
	
	/*
     * This method displays the read page form, 
     * - Input: click incedent
     * - Processing: process HTML code
     * - Output: HTML code for display page
     * - Pre-condition: If there is nothing in the list wouldnot show the button
     * - Post-conditon: The back button would take back to main page
     */
    function read_record($id) { // displays "read" form on page
        $this->select_db_record($id);
        $this->generate_html_top(2);
        $this->generate_form_group("name", $this->nameError, $this->name, "disabled");
        $this->generate_form_group("email", $this->emailError, $this->email, "disabled");
        $this->generate_form_group("mobile", $this->mobileError, $this->mobile, "disabled");
        $this->generate_html_bottom(2);
		
    } // end function read_record()
    
	/*
     * This method displays the update page form, 
     * - Input: click incedent
     * - Processing: process HTML code
     * - Output: HTML code for update page
     * - Pre-condition: If there is nothing in the list takes it to it this page automatically
     * - Post-conditon: After the input goes back to customers.php (main page)
     */
    function update_record($id) { // display "update" form
        if($this->noerrors) $this->select_db_record($id);
        $this->generate_html_top(3, $id);
        $this->generate_form_group("name", $this->nameError, $this->name, "autofocus onfocus='this.select()'");
        $this->generate_form_group("email", $this->emailError, $this->email);
        $this->generate_form_group("mobile", $this->mobileError, $this->mobile);
		this->display_file_upload();
        $this->generate_html_bottom(3);
    } // end function update_record()
    
	/*
     * This method displays the delete page form, 
     * - Input: click incedent
     * - Processing: process HTML code
     * - Output: HTML code for delete page
     * - Pre-condition: If there is nothing in the list the button wouldnt showup
     * - Post-conditon: After the input goes back to customers.php
     */
    function delete_record($id) { // displays "delete" form on page
        $this->select_db_record($id);
        $this->generate_html_top(4, $id);
        $this->generate_form_group("name", $this->nameError, $this->name, "disabled");
        $this->generate_form_group("email", $this->emailError, $this->email, "disabled");
        $this->generate_form_group("mobile", $this->mobileError, $this->mobile, "disabled");
        $this->generate_html_bottom(4);
    } // end function delete_record()
    
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
        if ($this->fieldsAllValid()) { 
            
            $pdo = Database::connect();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->password_hashed = MD5($this->password);
			$sql = "INSERT INTO $this->tableName (name,email,mobile,password_hash,filename,filepath,filedescription,filetype,filesize,filecontent) values(?, ?, ?, ?, ?, ?, ?, ? ,? ,?)";
				$q = $pdo->prepare($sql);
				$q->execute(array($this->name,$this->email,$this->mobile,$this->password_hash,$fileName,$filePath,$fileDescription,$fileType,$fileSize,$content));
				
            Database::disconnect();
            header("Location: $this->tableName.php"); 
        }
        else {
            
            $this->create_record(); 
        }
    } // end function insert_db_record
	
	/*
     * This method displays the selected input in to a form,  
     * - Input: click event
     * - Processing: select (SQL)
     * - Output:fills up the data fields in the form
     * - Precondition: Public variables set (name, email, mobile)
     *   and database connection variables are set in datase.php.
     * - Postcondition: Records are selected and inserted into the data fields of the page
     */ 
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
	
    /*
     * This method updates one record into the table, 
     * and redirects user to List, IF user input is valid, 
     * OTHERWISE it redirects user back to update form, with errors
     * - Input: user data from update form
     * - Processing: update (SQL)
     * - Output: None (This method does not generate HTML code,
     *   it only changes the content of the database)
     * - Precondition: Public variables set (name, email, mobile)
     *   and database connection variables are set in datase.php.
     * - Postcondition: Any changes made are updated into the database and sends back to main page
     */
    function update_db_record ($id) {
		$this->id = $id;
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
        
        if ($this->fieldsAllValid()) {
            $this->noerrors = true;
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
            header("Location: $this->tableName.php");
        }
        else {
            $this->noerrors = false;
            $this->update_record($id);  
        }
    } // end function update_db_record 
    
	/*
     * This method deletes one record into the table, 
     * and redirects user to List, IF user input is valid, 
     * OTHERWISE it redirects user back to delete form, with errors
     * - Input: user data from Create form
     * - Processing: delete (SQL)
     * - Output: None (This method does not generate HTML code,
     *   it only changes the content of the database)
     * - Precondition: Public variables set (name, email, mobile)
     *   and database connection variables are set in datase.php.
     * - Postcondition: The record is removed from the database table
     *   and user is redirected to the List screen (if no errors)
     */
    function delete_db_record($id) {
        $pdo = Database::connect();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "DELETE FROM $this->tableName WHERE id = ?";
        $q = $pdo->prepare($sql);
        $q->execute(array($id));
        Database::disconnect();
        header("Location: $this->tableName.php");
    } // end function delete_db_record()
    
	/*
     * This method displays the create page form, 
     * - Input: click incedent
     * - Processing: process HTML code
     * - Output: HTML code for create page
     * - Pre-condition: If there is nothing in the list takes it to it this page automatically
     * - Post-conditon: After the input goes back to customers.php
     */
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
	
	/*
     * This method displays the create page form, 
     * - Input: click incedent
     * - Processing: process HTML code
     * - Output: HTML code for create page
     * - Pre-condition: If there is nothing in the list takes it to it this page automatically
     * - Post-conditon: After the input goes back to customers.php
     */
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
	
	/*
     * This method displays the create page form, 
     * - Input: click incedent
     * - Processing: process HTML code
     * - Output: HTML code for create page
     * - Pre-condition: If there is nothing in the list takes it to it this page automatically
     * - Post-conditon: After the input goes back to customers.php
     */
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
	
	/*
     * This method displays the create page form, 
     * - Input: click incedent
     * - Processing: process HTML code
     * - Output: HTML code for create page
     * - Pre-condition: If there is nothing in the list takes it to it this page automatically
     * - Post-conditon: After the input goes back to customers.php
     */
	function display_file_upload() {
		echo "
				<p>File</p>
				<input type='file' name='Filename'> 
				<p>Description</p>
				<textarea rows='10' cols='35' name='Description'></textarea>
				<p>*Min of 255 characters</p>
				<br/>";
	}
	
	/*
     * This method generates the buttons to act on a list,
     * - Input: opening the home page
     * - Processing: php
     * - Output: creates the button to redirect to pages
     * - Pre-condition: If there are data in the database would create a table with the list
     * - Post-conditon: displays all the lists in the database in the table and the buttons for CRUD
     */
    private function generate_html_top ($fun, $id=null) {
        switch ($fun) {
            case 1: // create
                $funWord = "Create"; $funNext = "insert_db_record"; 
                break;
            case 2: // read
                $funWord = "Read"; $funNext = "none"; 
                break;
            case 3: // update
                $funWord = "Update"; $funNext = "update_db_record&id=" . $id; 
                break;
            case 4: // delete
                $funWord = "Delete"; $funNext = "delete_db_record&id=" . $id; 
                break;
            default: 
                echo "Error: Invalid function: generate_html_top()"; 
                exit();
                break;
        }
        echo "<!DOCTYPE html>
        <html>
            <head>
                <title>$funWord a $this->title</title>
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
                            <h3>$funWord a $this->title</h3>
                        </p>
                        <form class='form-horizontal' action='$this->tableName.php?fun=$funNext' method='post'>                        
                    ";
    } // end function generate_html_top()
    
	/*
     * This method inserts generated the button the create, update and delete forms, 
     * - Input: click event
     * - Processing: php
     * - Output: creats the buttons in the respective CRUD pages
     * - Pre-condition: If there are no data then would show up at the create page
     * - Post-conditon: displays the buttons for CRUD pages
     */
    private function generate_html_bottom ($fun) {
        switch ($fun) {
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
            default: 
                echo "Error: Invalid function: generate_html_bottom()"; 
                exit();
                break;
        }
        echo " 
                            <div class='form-actions'>
                                $funButton
                                <a class='btn btn-secondary' href='$this->tableName.php'>Back</a>
                            </div>
                        </form>
                    </div>

                </div> <!-- /container -->
            </body>
        </html>
                    ";
    } // end function generate_html_bottom()
    
	/*
     * This method generates the label for the tables, 
     * - Input: opening the page
     * - Processing: php
     * - Output: creates the labes for the table
     * - Pre-condition: If there are data in the database would create a table labels
     * - Post-conditon: displays the table labels
     */
    private function generate_form_group ($label, $labelError, $val, $modifier="") {
        echo "<div class='form-group'";
        echo !empty($labelError) ? ' alert alert-danger ' : '';
        echo "'>";
        echo "<label class='control-label'>$label &nbsp;</label>";
        //echo "<div class='controls'>";
        echo "<input "
            . "name='$label' "
            . "type='text' "
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
    
	/*
     * This method checks if all the fields are valid
     * - Input: name, email and mobile number
     * - Processing: php
     * - Output: checks if they are valid
     * - Pre-condition: Data type has to be same sathe type chosen for type checking
     * - Post-conditon: If the type is valid the redirect the funciton as valid
     */
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
        return $valid;
    } // end function fieldsAllValid() 
    
	/*
     * This method list all the records there is in the database 
     * - Input: loading the page
     * - Processing: query (SQL)
     * - Output: displays all the records in the database in the table
     * - Precondition: if there is any record in the database it will bring the 
     * - Postcondition: 
     */
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
               <a button type='submit' class='btn btn-success'href='https://github.com/sabbi3267/uploads' target='_blank'>Github</a><br />
				<a button type='submit' class='btn btn-success'href='https://github.com/sabbi3267/uploads/uml' target='_blank'>Uml</a><br />
				
				<a button type='submit' class='btn btn-success' href='https://github.com/sabbi3267/uploads/ufg.png' target='_blank'>User Flow Diagram</a><br />
                		<a href='$this->logout.php?fun=logout' class='btn btn-warning'>Log Out</a>
		<div class='container'>
                    <p class='row'>
                        <h3>$this->title" . "s" . "</h3>
                    </p>
                    <p>
                        <a href='$this->tableName.php?fun=display_create_form' class='btn btn-success'>Create</a>
                        <a href='logout.php' class='btn btn-danger'>Logout</a> 
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
