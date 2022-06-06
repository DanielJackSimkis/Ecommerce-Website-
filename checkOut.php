<!DOCTYPE html>
<head>
    <title>TechTree.com</title>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="css/bootstrap.css"/>
    <link rel="stylesheet" type="text/css" href="css/checkOutStyle.css"/>
    <link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Pacifico"/>
    <link rel="stylesheet" href="css/bootstrap-responsive.css"/>
    <link rel="stylesheet" href="css/font-awesome.css"/>
    <link rel="stylesheet" href="css/component.css"/>
    <link rel="stylesheet" href="css/font-awesome-ie7.css"/>

    <?php
            include('classes/DB.php');
            include('classes/isLoggedIn.php');
            session_start();
            $items = array();
            $total = 0.00;
            $cartAmount = 0;
            $userid  = Login::isLoggedIn();
            $username = "";

            // Login funtionality see index for more information
    
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

            if($userid != false){
                $username = DB::query('SELECT username FROM users WHERE userid = :userid', array(':userid' => $userid))[0]['username'];
            }

            // Check out functionality
            if(isset($_POST['buyNow']) && LogIn::isLoggedIn()){ // checks to see if the user has clicked the buy now button and that they are logged in
                $items = DB::query('SELECT * FROM cart'); // gets all the item from the cart
                foreach ($items as &$value) { // loops through all prosucts in the items array
                   DB::query('DELETE FROM cart WHERE productCartid = :productCartid', array(':productCartid' => $value['productCartid'])); // Delets all items from the cart
                }
            }else if(isset($_POST['buyNow'])){
                $items = DB::query('SELECT * FROM cart');
                foreach ($items as &$value) {
                   DB::query('DELETE FROM cart WHERE productCartid = :productCartid', array(':productCartid' => $value['productCartid']));
                }
            }

            // if the user clicks the continue shopping button then take them back to the home page
            if(isset($_POST['continueShopping'])){
                header('Location: /techtree/index.php');
            }

            // Log the user out functionality
            if(isset($_POST['logout'])){
                DB::query('DELETE FROM login_tokens WHERE user_id=:userid', array(':userid'=>Login::isLoggedIn()));
                $items = DB::query('SELECT * FROM cart');
                foreach ($items as &$value) {
                    DB::query('DELETE FROM cart WHERE productCartid = :productCartid', array(':productCartid' => $value['productCartid'])); 
                }
                header("Refresh:0"); // Refresh the current page
            }
        ?>

</head>

<body>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <div id="logo"><center><img src="images/logo.png"></center></div>

    <!-- Start of naviagation see index for more information -->
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
                            <input type="text" class="form-control" placeholder="Username" name="username" required>
                            <label class="formLabel" for="Password">Password:</label>
                            <input type="password" class="form-control" placeholder="Password" name="password" required>
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
                            DB::query('INSERT INTO cart VALUES (:cartid, :productName, :totalPrice, :productCartid)', array(':cartid'=> NULL, ':productName'=>$name, ':totalPrice'=> $price, ':productCartid'=> $_POST['productid']));
                        }
                       
                    }
                
                    if(isset ($_POST['removeFromCart']) || isset($_POST['remove'])){
                        $productid = $_POST['productid'];
                        DB::query('DELETE FROM cart WHERE productCartid = :productCartid', array(':productCartid'=>$productid));
                    }

                    $items = DB::query('SELECT * FROM cart');
                    foreach($items as $result){
                        $total = $result['totalPrice'] + $total;
                        ?><div class="item">
                            <form method="POST">
                        <?php
                                echo '<input type="hidden" name="productid" value="'.$result['productCartid'].'">';
                                echo '<h4>'.$result['productCartName'].'</h4>';
                                echo '<p> Price = £'.number_format($result['totalPrice'], 2).'</p>';
                                echo '<form method="POST"><center><button type="submit" class="btn btn-warning" name="remove">Remove</button></center></form>';
                        ?></form>
                        </div><?php
                    }
                ?>
                <div class="item">
                    <?php echo "<h1>Total = £" .number_format($total, 2)."</h1>"; ?>
                </div>
              <center><button type="submit" class="btn btn-success" name="checkout"><span class="glyphicon glyphicon glyphicon-gbp"></span> Checkout</button></center>
            </ul>
          </li>
        </ul>
      </div>
    </nav>
<!-- End of navigation-->
        <?php 
            if(isset($_POST['buyNow'])){ // Checks to see if the buy now button has been clicked if it has then dispays a thank you message
        ?>
                <div class="purchaseComplete">
                    <center><h1 id="purchaseCompleteHeading">Purchase Complete</h1>
                    <h2>Thank you for shopping at Tech Tree please comback again soon!</h2></center>
                    <form method="POST"><center><button type="submit" class="btn btn-success" name="continueShopping">Continue Shopping</button></center></form>
                </div>
        <?php
            }else{
        ?>      <center><h1>Address</h1></center>
        <?php 
            if($userid != false){ // Checks if the user is logged in if they are it displays the users address information
        ?>      <div class="address"><?php
                    if(!isset($_POST['changeAddress']) || isset($_POST['currentAddress'])){
                        $fName = DB::query('SELECT fName FROM users WHERE userid = :userid', array(':userid' => $userid))[0]['fName'];
                        $sName = DB::query('SELECT sName FROM users WHERE userid = :userid', array(':userid' => $userid))[0]['sName'];
                        $doorNumber = DB::query('SELECT doorNumber FROM users WHERE userid = :userid', array(':userid' => $userid))[0]['doorNumber'];
                        $street = DB::query('SELECT street FROM users WHERE userid = :userid', array(':userid' => $userid))[0]['street'];
                        $town = DB::query('SELECT town FROM users WHERE userid = :userid', array(':userid' => $userid))[0]['town'];
                        $postcode = DB::query('SELECT postcode FROM users WHERE userid = :userid', array(':userid' => $userid))[0]['postcode'];
                        echo "<h2>Your Address</h2>";
                        echo "<label>Name: </label> <p>". $fName . " " . $sName . "</p>";
                        echo "<label>Address: </label> <p>". $doorNumber . " " . $street . "</p>";
                        echo "<label>Town: </label> <p>". $town ."</p>";
                        echo "<label>Postcode: </label> <p>". $postcode ."</p>";?>
                        <form method="POST">
                            <center><button class="btn btn-warning" name="changeAddress">Use Different Address</button></center> <!-- Button to check if the user wants to user a differnt address -->
                        </form>
                        <?php
                    }else{ // If the user is not logged then display inputs to get the users address
                        ?>
                            <label for="doorNumber">Door Number: </label><input type="text" name="doorNumber" placeholder="Enter door number..." required><br/>
                            <label for="street">Street: </label><input type="text" name="street" placeholder="Enter name of street..." required><br/>
                            <label for="town">Town: </label><input type="text" name="town" placeholder="Enter name of town..." required><br/>
                            <label for="postcode">Postcode: </label><input type="text" name="postcode" placeholder="Enter postcode..." required>
                            <form method="POST">
                                <center><button class="btn btn-warning" name="currentAddress">Use Current Address</button></center>
                            </form>
                <?php

                    }
                ?>
                </div>
            <?php
            }else{?> <!-- If the user clicks the use differnt address buton then display the address input fields -->
                <div class="address">
                        <label for="doorNumber">Door Number: </label><input type="text" name="doorNumber" placeholder="Enter door number..." required><br/>
                        <label for="street">Street: </label><input type="text" name="street" placeholder="Enter name of street..." required><br/>
                        <label for="town">Town: </label><input type="text" name="town" placeholder="Enter name of town..." required><br/>
                        <label for="postcode">Postcode: </label><input type="text" name="postcode" placeholder="Enter postcode..." required>
                </div><?php
                }
                ?>

        <div class="cartItems">
            <center><h1 id="cartHeading">Cart</h1></center>

        <?php 
            foreach ($items as &$value) { // Get all relevent inforamtion about all the products in the cart and display them to the screen
                $description = DB::query('SELECT description FROM products WHERE productid = :productid', array(':productid' => $value['productCartid']))[0]['description'];
                $image = DB::query('SELECT image FROM product_images WHERE productImageid = :productImageid', array(':productImageid' => $value['productCartid']))[0]['image'];
                $name = DB::query('SELECT name FROM products WHERE productid = :productid', array(':productid' => $value['productCartid']))[0]['name'];
                $price = DB::query('SELECT price FROM products WHERE productid = :productid', array(':productid' => $value['productCartid']))[0]['price'];
                ?><form method="POST">
                    <div class="ItemsInCart">
                    <?php echo '<input  type="hidden" name="productid" value="'.$value['productCartid'].'">';
                          echo '<center><h3 name="productName">'.$name.'</h3> <h5>Price: £'.number_format($price, 2).'</h5></center>';
                          echo '<h1>Description</h1>';
                          echo '<p>'.$description.'</p>';
                          echo '<img class="productImage" src="data:image;base64,'.$image.'">'; 
                        
                        if(!DB::query('SELECT productCartid FROM cart WHERE productCartid = :productCartid', array(':productCartid'=> $value['productCartid']))){
                            ?><center><button type="submit" class="btn btn-success" name="buyNow"><span class="glyphicon glyphicon-gbp"></span> Buy Now</button>
                            <button type="submit" class="btn btn-success " name="addToCart"><span class="glyphicon glyphicon-shopping-cart"></span> Add to Cart</button></center><?php
                        }else{
                            ?>
                            <center><button type="submit" class="btn btn-warning " name="removeFromCart"><span class="glyphicon glyphicon-shopping-cart"></span> Remove from Cart</button></center><?php
                        }?>
                    </div>
                </form>
        <?php
            }
        ?>
    </div>

    <div class="buyNow">
        
        <?php echo '<h1>Amount of items</h1><h2>' .count($items).'</h2>';?>
        <hr/>
        <?php echo '<h1>Total Cost</h1><h2> £' .number_format($total, 2).'</h2>';?>
        <hr/>
        <form method="POST">
            <center><button type="submit" class="btn btn-success" name="buyNow">Buy Now</button></center>
        </form>
    </div><?php
            }
        ?>
    
</body>

<footer>
    <p id="copyRight"> Copyright &copy Daniel Jack Simkiss 2017</p>
</footer> 
