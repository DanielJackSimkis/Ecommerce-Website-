<!DOCTYPE html>
<head>
    <title>TechTree.com</title>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="css/bootstrap.css"/>
    <link rel="stylesheet" type="text/css" href="css/productInfoStyle.css"/>
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
    
            if(isset($_POST['checkout'])){
                header('Location: /techtree/checkOut.php'); // Checks if the user has clicked the checkout button if they have navigates them to the checkout page
            }

            if(isset($_POST['buyNow']) && LogIn::isLoggedIn()){
                $items = DB::query('SELECT * FROM cart');
                foreach ($items as &$value) {
                   DB::query('INSERT INTO purchases VALUES(:pKey, :purchaseProductid, :purchaseUserid)', array(':pKey' => NULL, ':purchaseProductid' => $value['productCartid'], ':purchaseUserid' => $userid));
                   DB::query('DELETE FROM cart WHERE productCartid = :productCartid', array(':productCartid' => $value['productCartid']));
                }
            }else if(isset($_POST['buyNow'])){
                $items = DB::query('SELECT * FROM cart');
                foreach ($items as &$value) {
                   DB::query('DELETE FROM cart WHERE productCartid = :productCartid', array(':productCartid' => $value['productCartid']));
                }
            }

            if(isset($_POST['continueShopping'])){
                header('Location: /techtree/index.php');
            }

            if(isset($_POST['checkout'])){
                header('Location: /techtree/checkOut.php');
            }

            if(isset($_POST['logout'])){
                DB::query('DELETE FROM login_tokens WHERE user_id=:userid', array(':userid'=>Login::isLoggedIn()));
                $items = DB::query('SELECT * FROM cart');
                foreach ($items as &$value) {
                    DB::query('DELETE FROM cart WHERE productCartid = :productCartid', array(':productCartid' => $value['productCartid'])); 
                }
                header("Refresh:0");
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
                        $price = DB::query('SELECT price FROM products WHERE productid=:productid', array(':productid'=>$_GET['productid']))[0]['price'];
                        $name = DB::query('SELECT name FROM products WHERE productid=:productid', array(':productid'=>$_GET['productid']))[0]['name'];
                        $productid = $_GET['productid'];
                        if(!DB::query('SELECT productCartid FROM cart WHERE productCartid = :productCartid', array(':productCartid'=> $_GET['productid']))){
                            DB::query('INSERT INTO cart VALUES (:cartid, :productName, :totalPrice, :productCartid)', array(':cartid'=> NULL, ':productName'=>$name, ':totalPrice'=> $price, ':productCartid'=> $_GET['productid']));
                        }
                       
                    }
                
                    if(isset ($_POST['removeFromCart']) || isset($_POST['remove'])){
                        $productid = $_GET['productid'];
                        DB::query('DELETE FROM cart WHERE productCartid = :productCartid', array(':productCartid'=>$productid));
                    }

                    $items = DB::query('SELECT * FROM cart');
                    foreach($items as $result){
                        $total = $result['totalPrice'] + $total;
                        ?><div class="item"><?php
                            echo '<input type="hidden" name="productid" value="'.$result['productCartid'].'">';
                            echo '<h4>'.$result['productCartName'].'</h4>';
                            echo '<p> Price = £'.number_format($result['totalPrice'], 2).'</p>';
                            echo '<form method="POST"><center><button type="submit" class="btn btn-warning" name="remove">Remove</button></center></form>';
                        ?></div><?php
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

    <div class="container">
        <div class="imageContainer">
            <?php $image = DB::query('SELECT image FROM product_images WHERE productImageid = :productImageid', array(':productImageid' => $_GET['productid']))[0]['image']; 
            echo '<img src="data:image;base64,'.$image.'" id="productImage">'?> 
        </div>
        <div class="headingContainer">
            <h1 id="productHeading"><?php echo $name = DB::query('SELECT name FROM products WHERE productid = :productid', array(':productid' => $_GET['productid']))[0]['name']; ?></h1><br/>
        </div>  
        <div class="priceContainer">
            <h1><?php $price = DB::query('SELECT price FROM products WHERE productid = :productid', array(':productid' => $_GET['productid']))[0]['price']; echo "Price: £" . number_format($price, 2);?></h1><br/> 
        </div>
        <div class="descriptionContainer">
            <p><?php echo $description = DB::query('SELECT description FROM products WHERE productid = :productid', array(':productid' => $_GET['productid']))[0]['description']?></p>
        </div>
        <div class="buttonContainer">
            <?php
            if(!DB::query('SELECT productCartid FROM cart WHERE productCartid = :productCartid', array(':productCartid'=> $_GET['productid']))){
                ?><form method="POST">
                    <center><button type="submit" class="btn btn-success " name="addToCart"><span class="glyphicon glyphicon-shopping-cart"></span> Add to Cart</button></center>
                </form><?php
            }else{
                ?>
                <form method="POST">
                    <center><button type="submit" class="btn btn-warning " name="removeFromCart"><span class="glyphicon glyphicon-shopping-cart"></span> Remove from Cart</button></center>  
                </form>
                <?php
            }
        ?>
        </div>
        
    </div>
    
</body>

<footer>
    <p id="copyRight"> Copyright &copy Daniel Jack Simkiss 2017</p>
</footer> 
