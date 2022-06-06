<!DOCTYPE html>
<head>
    <title>TechTree.com</title>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="css/bootstrap.css"/>
    <link rel="stylesheet" type="text/css" href="css/accountStyle.css"/>
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

            if(Login::isLoggedIn()){
                $userid = Login::isLoggedIn();
                $userDetails = DB::query('SELECT * FROM users WHERE userid = :userid', array(':userid' => $userid));
            }

            if(isset($_POST['checkout'])){
                session_start();
                $SESSION['cart'] = $items;
                header('Location: /techtree/checkOut.php');
            }

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

            if(Login::isLoggedIn()){
                $username = DB::query('SELECT username FROM users WHERE userid = :userid', array(':userid' => $userid))[0]['username'];
                $password = DB::query('SELECT password FROM users WHERE userid = :userid', array(':userid' => $userid))[0]['password'];
                $title = DB::query('SELECT title FROM users WHERE userid = :userid', array(':userid' => $userid))[0]['title'];
                $fName = DB::query('SELECT fName FROM users WHERE userid = :userid', array(':userid' => $userid))[0]['fName'];
                $sName = DB::query('SELECT sName FROM users WHERE userid = :userid', array(':userid' => $userid))[0]['sName'];
                $email = DB::query('SELECT email FROM users WHERE userid = :userid', array(':userid' => $userid))[0]['email'];
                $doorNumber = DB::query('SELECT doorNumber FROM users WHERE userid = :userid', array(':userid' => $userid))[0]['doorNumber'];
                $street = DB::query('SELECT street FROM users WHERE userid = :userid', array(':userid' => $userid))[0]['street'];
                $town = DB::query('SELECT town FROM users WHERE userid = :userid', array(':userid' => $userid))[0]['town'];
                $postcode = DB::query('SELECT postcode FROM users WHERE userid = :userid', array(':userid' => $userid))[0]['postcode'];
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
          <li class="active"><a class="dropdown-toggle" data-toggle="dropdown" href="#"><span class="glyphicon glyphicon-log-in"></span><?php if($userid != false){echo " " . $username;}else{echo " Login";}?></a>
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
                        ?>  </form>
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
        if(Login::isLoggedIn()){ 
    ?>
            <div class="container">
                <center><h1 id="accountHeading"><?php echo $username . "'s Account"?></h1></center>

                <div class="userDetails"><label for="username">Username:</label><?php echo "<h2 name='username'>". $username . "</h2>"?> <a href="#">Edit</a></div>
                <div class="userDetails"><label for="password">Password:</label><?php  echo "<h2 name='password'>********</h2>"?> <a href="#">Edit</a></div>
                <div class="userDetails"><label for="title">Title:</label><?php echo "<h2 name='title'>" . $title . "</h2>"?> <a href="#">Edit</a></div>
                <div class="userDetails"><label for="fName">First Name:</label><?php echo "<h2 name='fName'>" . $fName . "</h2>"?> <a href="#">Edit</a></div>
                <div class="userDetails"><label for="sName">Surname:</label><?php echo "<h2 name='sName'>" . $sName . "</h2>"?> <a href="#">Edit</a></div>
                <div class="userDetails"><label for="email">Email:</label><?php echo "<h2 name='email'>" . $email . "</h2>"?> <a href="#">Edit</a></div>
                <div class="userDetails"><label for="doorNumber">Door Number: </label><?php echo "<h2 name='doorNumber'>" . $doorNumber . "</h2>"?> <a href="#">Edit</a></div>
                <div class="userDetails"><label for="street">Street: </label><?php echo "<h2 name='street'>" . $street . "</h2>"?> <a href="#">Edit</a></div>
                <div class="userDetails"><label for="town">Town: </label><?php echo "<h2 name='town'>" . $town . "</h2>"?> <a href="#">Edit</a></div>
                <div class="userDetails"><label for="postcode">Postcode: </label><?php echo "<h2 name='postcode'>" . $postcode . "</h2>"?> <a href="#">Edit</a></div>
            </div>
    <?php
        }else{
    ?>
            <div class="container"><center><h1>ooPs it seems you are not logged in please log in and try again...</h1></center></div>
    <?php
        }
    ?>
    
</body>

