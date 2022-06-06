<!DOCTYPE html>
<head>
	<title>TechTree.com</title>
	<meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="css/bootstrap.css"/>
	<link rel="stylesheet" type="text/css" href="css/helpStyle.css"/>
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
            $featuredProducts = DB::query('SELECT products.productid, products.name, products.description, products.stock, products.price, product_images.image FROM products INNER JOIN product_images ON products.productid = product_images.productImageid WHERE amountSold > 50 ORDER BY amountSold DESC');

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
          <li class="active"><a href="#">Help</a></li>
        </ul>
        <ul class="nav navbar-nav navbar-right">
          <li><a class="dropdown-toggle" data-toggle="dropdown" href="#"><span class="glyphicon glyphicon-log-in"></span><?php if($userid != false){echo " " . $username;}else{echo " Login";}?></a>
            <ul class="dropdown-menu">
                <?php
                    if($userid == false){
                ?>
                        <form method="POST">
                            <label class="formLabel" for="Username">Username:</label>
                            <input type="text" class="form-control" placeholder="Username" name="username"/>
                            <label class="formLabel" for="Password">Password:</label>
                            <input type="password" class="form-control" placeholder="Password" name="password"/>
                            <button type="submit" class="btn btn-default" name="login">Login</button>
                            <button type="button" onClick="location.href='createAccount.php'" class="btn btn-warning">Create Account</button>
                        </form>
                <?php
                    }else{
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
                            <form method="POST"><?php
                                echo '<input  type="hidden" name="productid" value="'.$result['productCartid'].'">';
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
                <form method="POST">
                    <center><button type="submit" class="btn btn-success" name="checkout"><span class="glyphicon glyphicon glyphicon-gbp"></span> Checkout</button></center>
                </form>
            </ul>
          </li>
        </ul>
      </div>
    </nav>
    <div class="container">
        <center><h1>User Help</h1></center>
        <div class="help1">
            <h1>Logging in/Creating account/Viewing account details/Logging out</h1><br/>
            <p>In order to login to the website you first need an account if you do not have an account you will first need to click on the login button in the navigation bar. After this, a dropdown menu appear, a screenshot of this can be seen below. </p><br/>
            <img src="images/help/img1.jpg"><br/>
            <p>From there you will need to click on the create account button which will navigate you to the create account page which can be seen below, </p><br/>
            <img src="images/help/img2.jpg"><br/>
            <p>When on this page simply fill out all of the fields and click the create account button at the bottom of the page, if you have filled out all the details correctly you will be greeted with a welcome message as shown below. </p><br/>
            <img src="images/help/img3.jpg"><br/>
            <p>When you have an account to login click the login button in the navigation menu like before, but this time instead of clicking the account button enter your username and password in their respective input fields and click login. If you have entered your details correctly, the login should change to your user name as shown in the screenshot bellow.</p>
            <img src="images/help/img4.jpg"><br/>
            <p>In order to view account details click on your username in the navigation, this time the dropdown will show a different menu than before you logged in. In this menu, you should see an account option and a logout button; in order to logout simply click that button. However, to view your account details click the account option as shown in the screenshot below. </p><br/>
            <img src="images/help/img5.jpg"><br/>
            <p>When you click the account button, it will take you to a page where you can see your account details as shown below, to get back simply click the home option in the navigation.</p><br/>
            <img src="images/help/img6.jpg">
        </div>
        
        <div class="help2">
            <h1>Adding to the cart & Checking out</h1><br/>
            <p>On the home page, you will notice some products in the featured list; you may also notice that they have a button called add to cart. In order to add that item simply click on this items, if the item is clicked the button should change to remove form cart as shown below.</p><br/>
            <img src="images/help/img7.jpg"><br/>
            <p> After the item has been added to, view this product click on the cart option in the navigation, a drop down menu will then pop up shown all the items in the cart as well as their total price as shown below. </p><br/>
            <img src="images/help/img8.jpg"><br/>
            <p>When you click on the cart option in the navigation, you may notice a check out button at the bottom of the drop down. If you click this button, it will take you to the checkout page as shown below. On this page it will show all the items in the cart, on the right it will show the amount of items in the cart as well as their total price. It will have an option to enter your address, note that this option will be different if you are logged in. if you are logged it will display the address that you entered when you signed up, to use a different address click the use different address button and fill out the input fields. If you are not logged in these input fields will be there by default, fill them and click buy now to check out.</p><br/>
            <b><p>Logged in</p></b><br/>
            <img src="images/help/img9.jpg"><br/>
            <b><p>Not logged in</p></b><br/>
            <img src="images/help/img10.jpg"><br/>
        </div>

        <div class="help3">
            <h1>View/search Products</h1><br/>
            <p>In order to find a specific item navigate to the home page, on the left hand side of the page you will notice a search box as shown in the screenshot below</p><br/>
            <img src="images/help/img11.jpg"><br/>
            <p>Type the name of the product in this box and click enter or click the button with the magnifying glass. This will then take you the products page and display the products that match the name you typed in as seen below. </p><br/>
            <img src="images/help/img12.jpg"><br/>
            <p>To view details of the product simply click on the box with the red border, this will then take you to the product info page. On this page, all the details of the product will be displayed as shown below. </p><br/>
            <img src="images/help/img13.jpg"><br/>
        </div>

            
    </div>
</body>


    