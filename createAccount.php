<!DOCTYPE html>
<head>
	<title>TechTree.com</title>
	<meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="css/bootstrap.css"/>
	<link rel="stylesheet" type="text/css" href="css/createAccountStyle.css"/>
	<link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Pacifico"/>
    <link rel="stylesheet" href="css/bootstrap-responsive.css"/>
    <link rel="stylesheet" href="css/font-awesome.css"/>
    <link rel="stylesheet" href="css/component.css"/>
    <link rel="stylesheet" href="css/font-awesome-ie7.css"/>

    <?php
            include('classes/DB.php');
            include('classes/isLoggedIn.php');
            $items = array();
            $total = 0.00;
            $userid  = Login::isLoggedIn();
            $username = "";
            
            // Login functionality see index for more information
            if(isset($_POST['login'])){
                $username = $_POST['username'];
                $password = $_POST['password'];

                if(DB::query('SELECT username FROM users WHERE username=:username', array(':username'=>$username))){
                    if(password_verify($password, DB::query('SELECT password FROM users WHERE username=:username', array(':username'=>$username))[0]['password'])){

                        $cstrong = true;
                        $token = bin2hex(openssl_random_pseudo_bytes(64, $cstrong));

                        $userid = DB::query('SELECT userid FROM users WHERE username=:username', array(':username' => $username))[0]['userid'];

                        DB::query('INSERT INTO login_tokens VALUES(NULL, :token, :userid)', array(':token'=>sha1($token), ':userid'=>$userid));

                        setcookie("CQID", $token, time() + 60 * 60 * 24 * 7, '/', NULL, NULL, true);
                        setcookie("CQID_", '1', time() + 60 * 60 * 24 * 3, '/', NULL, NULL, true);

                    }else{
                        echo "Incorrect password!";
                    }
                }else{
                    echo "User not registered";
                }
            }

            if(isset($_POST['checkout'])){
            	header('Location: /techtree/checkOut.php');
            }

            if($userid != false){
                $username = DB::query('SELECT username FROM users WHERE userid = :userid', array(':userid' => $userid))[0]['username'];
            }

            if(isset($_POST['logout'])){
            	DB::query('DELETE FROM login_tokens WHERE user_id=:userid', array(':userid'=>Login::isLoggedIn()));
            	$items = DB::query('SELECT * FROM cart');
            	foreach ($items as &$value) {
            		DB::query('DELETE FROM cart WHERE productCartid = :productCartid', array(':productCartid' => $value['productCartid']));	
            	}
            	header("Refresh:0");
            }

    	$usernameErrorMessage = "";
    	$passwordErrorMessage = "";
    	$emailErrorMessage = "";
    	$successfullyCreated = false;

    	if(isset($_POST["createAccount"])){ // Check if user has clicked the create account button
    		$title = $_POST['title']; // Get title that user entered
    		$fName = $_POST['fName']; // Get first name that user entered
    		$sName = $_POST['sName']; // Get surname that user entered
    		$email = $_POST['email']; // Get email that user entered
    		$doorNumber = $_POST['doorNumber']; // Get door number that user entered
    		$street = $_POST['street']; // Get street name that user entered
    		$town = $_POST['town']; // Get town that user entered
    		$postcode = $_POST['postcode']; // Get postcode that user entered
    		$username = $_POST['username']; // Get username that user entered
    		$password = $_POST['password']; // Get password that user entered

    		if(!DB::query('SELECT username FROM users WHERE username = :username', array(':username' => $username))){ // Checks to see if username already exists
    			if(strlen($username) >= 3 && strlen($username) <= 50){ // Chekcs to see if the user name is the correct length
    				if(preg_match('/[a-zA-Z0-9_]+/', $username)){ // Checks to see if username only contains certain letters
    					if(strlen($password) >= 6  && strlen($password) <= 60){ // Checks to see if the password is the correct length
    						if(filter_var($email, FILTER_VALIDATE_EMAIL)){ // Check to see the the email is a valid email address
    							if(!DB::query('SELECT email FROM users WHERE email=:email', array(':email'=>$email))){ // Checks to see if the email address is already being used
    								// Inserts user details into the database
                                    DB::query('INSERT INTO users VALUES(:pKey,
    																	:title,
    																	:fName,
    																	:sName,
    																	:email,
    																	:doorNumber,
    																	:street,
    																	:town,
    																	:postcode,
    																	:username,
    																	:password)',
    																	array(':pKey' => NULL, 
    																		  ':title'  => $title,
    																		  ':fName' => $fName,
    																		  ':sName' => $sName,
    																		  ':email' => $email,
    																		  ':doorNumber' => $doorNumber,
    																		  ':street' => $street,
    																		  ':town' => $town,
    																		  ':postcode' => $postcode,
    																		  ':username' => $username,
    																		  ':password' => password_hash($password,  PASSWORD_BCRYPT)));
    								$successfullyCreated = true;
    							}else{
    								$emailErrorMessage = "Error email address already exists..."; // Error message
    							}
    						}else{
								$emailErrorMessage = "Error invalid email address..."; // Error message
    						}
    					}else{
							$passwordErrorMessage = "Error password needs to be between 6 and 50 characters long"; // Error message
    					}
    				}else{
    					$usernameErrorMessage = "Error username invalid characters used..."; // Error message
    				}
    			}else{
    				$usernameErrorMessage = "Error username should be between 4 and 50 characters long..."; // Error message
    			}
    		}else{
    			$usernameErrorMessage = "Error username already exists..."; // Error message
    		}
    	}
    ?>

</head>

<body>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
	<div id="logo"><center><img src="images/logo.png"></center></div>

	 <nav class="navbar navbar-default">
	  <div class="container-fluid">
	    <ul class="nav navbar-nav">
	      <li><a href="index.php">Home</a></li>
	      <li><a href="products.php">Products</a></li>
	      <li><a href="help.php">Help</a></li>
	    </ul>
	    <ul class="nav navbar-nav navbar-right">
	      <li><a class="dropdown-toggle" data-toggle="dropdown" href="#"><span class="glyphicon glyphicon-log-in"></span><?php if($userid != false){echo " " . $username;}else{echo " Login";}?></a>
			<ul class="dropdown-menu">
				<?php
					if($userid == false){
				?>
						<form method="POST">
							<label class="formLabel" for="Username">Username:</label>
			          		<input type="text" class="form-control" placeholder="Username" name="username">
			          		<label class="formLabel" for="Password">Password:</label>
			          		<input type="password" class="form-control" placeholder="Password" name="password">
			          		<button type="submit" class="btn btn-default" name="login">Login</button>
			          		<button type="button" onClick="location.href='createAccount.php'" class="btn btn-warning">Create Account</button>
						</form>
				<?php
					}else{
				?>
						<li><a href="account.php">Account</a></li>
						<form method="POST"><button type="submit" class="btn btn-warning" name="logout">Log Out</button></form>
				<?php
					}
				?>
	        </ul>
	      </li>
	      <li><a class="dropdown-toggle" data-toggle="dropdown" href="#"><span class="glyphicon glyphicon-shopping-cart"></span> Cart</a>
			<ul class="dropdown-menu">    
                <?php
                
                    if(isset($_POST['addToCart'])){
                        $price = DB::query('SELECT price FROM products WHERE productid=:productid', array(':productid'=>$_POST['productid']))[0]['price'];
                        $name = DB::query('SELECT name FROM products WHERE productid=:productid', array(':productid'=>$_POST['productid']))[0]['name'];
                        $productid = $_POST['productid'];
                        if(!DB::query('SELECT productCartid FROM cart WHERE productCartid = :productCartid', array(':productCartid'=> $_POST['productid']))){
                            DB::query('INSERT INTO cart VALUES (:cartid, :productName, :totalPrice, :productCartid)', array(':cartid'=> NULL, ':productName'=>$name, ':totalPrice'=> number_format($price, 2), ':productCartid'=> $_POST['productid']));
                        }
                       
                    }
                
                    if(isset ($_POST['removeFromCart']) || isset($_POST['remove'])){
                        DB::query('DELETE FROM cart WHERE productCartid = :productCartid', array(':productCartid'=>$_POST['productid']));
                    }

                    $items = DB::query('SELECT * FROM cart');
                    foreach($items as $result){
                        $total = number_format($result['totalPrice'], 2) + number_format($total, 2);
                        ?><div class="item">
                        	<form method="POST">
                        <?php
	                       		echo '<input  type="hidden" name="productid" value="'.$result['productCartid'].'">';
	                            echo '<h4>'.$result['productCartName'].'</h4>';
	                            echo '<p> Price = £'.number_format($result['totalPrice'], 2).'</p>';
                            	echo '<form method="POST"><center><button type="submit" class="btn btn-warning" name="remove">Remove</button></center></form>';
                        ?>	</form>
                        </div><?php
                    }
                ?>
				<div class="item">
					<?php echo "<h1>Total = £" .number_format($total, 2)."</h1>"; ?>
				</div>
				<form method="POST">
			  		<center><button type="submit" class="btn btn-success" name="checkout"><span class="glyphicon glyphicon glyphicon-gbp"></span> Checkout</button></center>
			  	</form>
	        </ul>
	      </li>
	    </ul>
	  </div>
	</nav>
    <?php
    	if($successfullyCreated == false){
    ?>
    		<div class="wrapper">
				<form method="POST">
					<label class="formLabel" for="title">Title</label>
					<select name="title" required>
						<option value="" selected disabled>Title</option>
						<option value="Mr">Mr</option>
						<option value="Master">Master</option>
						<option value="Mrs">Mrs</option>
						<option value="Miss">Miss</option>
						<option value="Ms">Ms</option>
						<option value="Doctor">Doctor</option>
						<option value="Professor">Professor</option>
						<option value="Lord">Lord</option>
						<option value="Lady">Lady</option>
						<option value="Duke">Duke</option>
						<option value="Duchess">Duchess</option>
					</select><br/>
					<label class="formLabel" for="fName">First Name: </label>
					<input class="formInput" type="text" name="fName" placeholder="Enter first name..." maxlength="50" size="50" required><br/>
					<label class="formLabel" for="sName">Surname: </label>
					<input class="formInput" type="text" name="sName" placeholder="Enter surname..." maxlength="50" size="50" required><br/>
					<label class="formLabel" for="email">Email: </label>
					<input class="formInput" type="email" name="email" placeholder="Enter email address..." maxlength="50" size="60" required><?php echo '<p>'.$emailErrorMessage."</p>"?><br/>
					<label class="formLabel" for="doorNumber">Door Number: </label>
					<input class="formInput" type="text" name="doorNumber" placeholder="Enter door number..." required><br/>
					<label class="formLabel" for="street">Street: </label>
					<input class="formInput" type="text" name="street" placeholder="Enter name of street..." maxlength="50" size="60" required><br/>
					<label class="formLabel" for="town">Town: </label>
					<input class="formInput" type="text" name="town" placeholder="Enter name of town..." maxlength="50" size="60" required><br/>
					<label class="formLabel" for="postcode">Postcode: </label>
					<input class="formInput" type="text" name="postcode" placeholder="Enter postcode..." maxlength="7" size="7" required><br/>
					<label class="formLabel" for="username">Username: </label>
					<input class="formInput" type="text" name="username" placeholder="Enter username..." maxlength="50" size="50" required><?php echo '<p>'.$usernameErrorMessage."</p>"?><br/>
					<label class="formLabel" for="password">Password:</label>
					<input class="formInput" type="password" name="password" placeholder="Enter password..." maxlength="50" size="50" required><?php echo '<p>'.$passwordErrorMessage."</p>"?><br/>
					<center><button type="submit" class="btn btn-success" name="createAccount">Create account</button>
					<button class="btn btn-default" name="back">Back</button>
					</center>
				</form>
			</div>
    <?php
    	}else{
    ?>
    		<div class="wrapper">
    			<center><h1><?php echo $_POST['fName'] . " ";?> You have Successfully created an account! Thank you and welcome to TechTree</h1></center>
    			<form action="index.php" method="POST">
    				<center><button type="submit" class="btn btn-success" name="home">Home</button></center>
    			</form>
    		</div>
    <?php
    	}
    ?>
    
    
</body>

<footer>
	<p id="copyRight"> Copyright &copy Daniel Jack Simkiss 2017</p>
</footer> 
