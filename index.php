<!DOCTYPE html>
<head>   
	<title>TechTree.com</title>
	<meta charset="utf-8"/><!--   -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="css/bootstrap.css"/>
	<link rel="stylesheet" type="text/css" href="css/indexStyle.css"/>
	<link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Pacifico"/>
    <link rel="stylesheet" href="css/bootstrap-responsive.css"/>
    <link rel="stylesheet" href="css/font-awesome.css"/>
    <link rel="stylesheet" href="css/component.css"/>
    <link rel="stylesheet" href="css/font-awesome-ie7.css"/>
    <?php
            include('classes/DB.php'); //Database class allows for databse connection and queries to be ran
            include('classes/isLoggedIn.php'); // isLoggedIn class allows me to check if the user is logged in and aslo retrive thier ID if they are
            $items = array(); // Used to store items form the cart table in the database.
            $total = 0.00; // Stores the amount that all the items in the cart come to
            $userid  = Login::isLoggedIn(); // Gets the users ID if they are logged in
            $username = ""; // Used to store the user's username
            $featuredProducts = DB::query('SELECT products.productid, products.name, products.description, products.stock, products.price, product_images.image FROM products INNER JOIN product_images ON products.productid = product_images.productImageid WHERE amountSold > 50 ORDER BY amountSold DESC'); // Retireves the most popualr products in the products table in the database.

            if(isset($_POST['login'])){ // Check if the user is logged in  or not
                $username = $_POST['username']; // Gets the username that the user entered when logging in 
                $password = $_POST['password']; // Gets the password that the user entered when logging in 

                if(DB::query('SELECT username FROM users WHERE username=:username', array(':username'=>$username))){ // Checks if the username entered exists
                    if(password_verify($password, DB::query('SELECT password FROM users WHERE username=:username', array(':username'=>$username))[0]['password'])){ // Checks if the password entered is correct

                        $cstrong = true;
                        $token = bin2hex(openssl_random_pseudo_bytes(64, $cstrong)); // Generates a random 64 character token 

                        $userid = DB::query('SELECT userid FROM users WHERE username=:username', array(':username' => $username))[0]['userid']; // Get the user's ID from the database

                        DB::query('INSERT INTO login_tokens VALUES(NULL, :token, :userid)', array(':token'=>sha1($token), ':userid'=>$userid)); // Inserts the generated token into the login_tokens table in the database

                        setcookie("CQID", $token, time() + 60 * 60 * 24 * 7, '/', NULL, NULL, true); // Creates a cookie lasting one week
                        setcookie("CQID_", '1', time() + 60 * 60 * 24 * 3, '/', NULL, NULL, true); // Creates a cookie lasting three days

                    }else{
                        echo "Incorrect password!"; // Error message
                    }
                }else{
                    echo "User not registered"; // Error message
                }
            }

            if(isset($_POST['checkout'])){
                header('Location: /techtree/checkOut.php'); // Checks if the user has clicked the checkout button if they have navigates them to the checkout page
            }

            if($userid != false){ // Checks if user is logged in
                $username = DB::query('SELECT username FROM users WHERE userid = :userid', array(':userid' => $userid))[0]['username']; // Gets username of the user that is logged in.
            } 

            if(isset($_POST['logout'])){ // Cjecks if logout button has been clicked 
                DB::query('DELETE FROM login_tokens WHERE user_id=:userid', array(':userid'=>Login::isLoggedIn())); // Deletes the login token of the user when they log out
                $items = DB::query('SELECT * FROM cart'); // Gets all thge items currently in the cart table.
                foreach ($items as &$value) {
                    DB::query('DELETE FROM cart WHERE productCartid = :productCartid', array(':productCartid' => $value['productCartid']));  // Delets all items from the cart when user logs out
                }
                header("Refresh:0"); // refreshes the current page
            }

        ?>

</head>

<body>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
	<div id="logo"><center><img src="images/logo.png"></center></div> <!-- Gets the image for the sites logo-->
    
    <!--    Start of Naviagation-->
     <nav class="navbar navbar-default">
      <div class="container-fluid">
        <ul class="nav navbar-nav">
          <li class="active"><a href="#">Home</a></li>
          <li><a href="products.php">Products</a></li>
          <li><a href="help.php">Help</a></li>
        </ul>
        <ul class="nav navbar-nav navbar-right">
          <li><a class="dropdown-toggle" data-toggle="dropdown" href="#"><span class="glyphicon glyphicon-log-in"></span><?php if($userid != false){echo " " . $username;}else{echo " Login";}?></a>
            <ul class="dropdown-menu">
                <?php
                    if($userid == false){ // Checks if the user is logged in
                ?>
                        <form method="POST"> <!-- Created a form for the login inputs -->
                            <label class="formLabel" for="Username">Username:</label> <!-- Username Label -->
                            <input type="text" class="form-control" placeholder="Username" name="username"/> <!-- Username input -->
                            <label class="formLabel" for="Password">Password:</label> <!-- Password Label -->
                            <input type="password" class="form-control" placeholder="Password" name="password"/> <!-- Password input -->
                            <button type="submit" class="btn btn-default" name="login">Login</button>
                            <button type="button" onClick="location.href='createAccount.php'" class="btn btn-warning">Create Account</button>
                        </form>
                <?php
                    }else{ // If the user is logged in then display account and log out options instead of login and create account options
                ?>
                        <li><a href="account.php">Account</a></li>
                        <br/>
                        <form method="POST"><button type="submit" class="btn btn-warning" name="logout">Log Out</button></form>
                <?php
                    }
                ?>
            </ul>
          </li>
          <li><a class="dropdown-toggle" data-toggle="dropdown" href="#"><span class="glyphicon glyphicon-shopping-cart"></span> Cart</a>
            <ul class="dropdown-menu">    
                <?php
                
                    if(isset($_POST['addToCart'])){ // Checks if the add to cart button has been clicked
                        $price = DB::query('SELECT price FROM products WHERE productid=:productid', array(':productid'=>$_POST['productid']))[0]['price']; // Gets the price of the product form the database
                        $name = DB::query('SELECT name FROM products WHERE productid=:productid', array(':productid'=>$_POST['productid']))[0]['name']; // Gets the name of the product from the database
                        $productid = $_POST['productid']; // Gets the ID of the product form the database
                        if(!DB::query('SELECT productCartid FROM cart WHERE productCartid = :productCartid', array(':productCartid'=> $_POST['productid']))){ // Check to see the product is already in the cart table
                            DB::query('INSERT INTO cart VALUES (:cartid, :productName, :totalPrice, :productCartid)', array(':cartid'=> NULL, ':productName'=>$name, ':totalPrice'=> number_format($price, 2), ':productCartid'=> $_POST['productid'])); // Adds the product to the cart table.
                        }
                       
                    }
                
                    if(isset ($_POST['removeFromCart']) || isset($_POST['remove'])){ // Checks to see if the remove from cart button has been clicked 
                        DB::query('DELETE FROM cart WHERE productCartid = :productCartid', array(':productCartid'=>$_POST['productid'])); // Delets the slected item from the cart 
                    }

                    $items = DB::query('SELECT * FROM cart'); // Gets all the items in the cart 
                    foreach($items as $result){ // loops through all items in the cart
                        $total = number_format($result['totalPrice'], 2) + number_format($total, 2); // Calcualtes the total price of all products in the cart add puts into a deciamal number format.
                        ?><div class="item"> <!-- Crates a box in order to display the items in the cart -->
                            <form method="POST"><?php
                                echo '<input  type="hidden" name="productid" value="'.$result['productCartid'].'">'; // A hidden input in order to store the products ID 
                                echo '<h4>'.$result['productCartName'].'</h4>'; // Displays the products name in the cart
                                echo '<p> Price = £'.number_format($result['totalPrice'], 2).'</p>'; // Displays the products price in the cart
                                echo '<form method="POST"><center><button type="submit" class="btn btn-warning" name="remove">Remove</button></center></form>'; // A button to allow the user to remove the item from the cart
                        ?></form>
                        </div><?php
                    }
                ?>
                <div class="item">
                    <?php echo "<h1>Total = £" .number_format($total, 2)."</h1>"; ?> <!-- Displays the total price in the cart -->
                </div>
                <form method="POST">
                    <center><button type="submit" class="btn btn-success" name="checkout"><span class="glyphicon glyphicon glyphicon-gbp"></span> Checkout</button></center> <!-- Check out button when clicked navigated the user to the check out page -->
                </form>
            </ul>
          </li>
        </ul>
      </div>
    </nav>
    <!-- End of Navigation -->

    <div class="row">
        <div class="search">
            <h1 id="searchHeading">Search</h1>
            <div class="form-group" id="search">
                <form action="products.php" method="POST">
                    <input type="text" class="form-control" id="searchBox" maxlength="50" placeholder="Search" name="search">
                    <button type="submit" class="btn btn-default" id="searchButton" name="searchBtn"><span class="glyphicon glyphicon-search"></span></button>  
                </form>
            </div>
        </div>
        <div class="container">
            <center><h1 id="featuredHeading">Featured</h1></center> <!-- Heading for featured list --> 
        
             <?php
                for ($i = 0; $i < 9; $i++) { // for loop to display nine items in the featured list
             ?>     
                    <a href="productInfo.php?productid=<?php echo $featuredProducts[$i]['productid'];?>"><div class="featuredProducts"> <!-- Makes the div a link which when clicked navigates the user to the products info page and [passes the ID of the selected priduct. --> 
                        <form method="POST">
                        <?php
                            echo '<input  type="hidden" name="productid" value="'.$featuredProducts[$i]['productid'].'">';// A hidden input in order to store the products ID 
                            echo '<center><h3 name="productName">'.$featuredProducts[$i]['name'].'</h3> <h5>Price: £'.number_format($featuredProducts[$i]['price'], 2).'</h5></center>'; // Displays the products name
                            echo '<center><img class="productImage" src="data:image;base64,'.$featuredProducts[$i]['image'].'"></center>';  // Displays the products image
                        ?>
                           <?php
                                if(!DB::query('SELECT productCartid FROM cart WHERE productCartid = :productCartid', array(':productCartid'=> $featuredProducts[$i]['productid']))){
                                    ?><form method="POST">
                                        <?php echo '<input  type="hidden" name="productid" value="'.$featuredProducts[$i]['productid'].'">';?>
                                        <center><button type="submit" class="btn btn-success " name="addToCart"><span class="glyphicon glyphicon-shopping-cart"></span> Add to Cart</button></center>
                                    </form><?php
                                }else{
                                    ?>
                                    <form method="POST">
                                        <?php echo '<input  type="hidden" name="productid" value="'.$featuredProducts[$i]['productid'].'">';?>
                                        <center><button type="submit" class="btn btn-warning " name="removeFromCart"><span class="glyphicon glyphicon-shopping-cart"></span> Remove from Cart</button></center>  
                                    </form>
                                    <?php
                                }
                            ?>         
                        </form>
                    </div></a>
            <?php
                }
            ?>
        </div>
    </div>
    
</body>

<footer>
    <p id="copyRight"> Copyright &copy Daniel Jack Simkiss 2017</p>
</footer> 
    