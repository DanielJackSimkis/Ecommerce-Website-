<?php 

	class LogIn{
	
	 	public static function isLoggedIn(){

	        if(isset($_COOKIE['CQID'])){
	            if(DB::query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token'=>sha1($_COOKIE['CQID'])))){
	                $user_id = DB::query('SELECT user_id FROM login_tokens WHERE token=:token', array(':token'=>sha1($_COOKIE['CQID'])))[0]['user_id'];

	               if(isset($_COOKIE['CQID_'])){
	                  return $user_id;
	               }else{
	                  $cstrong = true;
	                  $token = bin2hex(openssl_random_pseudo_bytes(64, $cstrong));
	                  DB::query('INSERT INTO login_tokens VALUES(NULL, :token, :user_id)', array(':token'=>sha1($token), ':user_id'=>$user_id));
	                  DB::query('DELETE FROM login_tokens WHERE token=:token', array(':token'=>sha1($_COOKIE['CQID'])));

	                  setcookie("CQID", $token, time() + 60 * 60 * 24 * 7, '/', NULL, NULL, true);
	                  setcookie("CQID_", '1', time() + 60 * 60 * 24 * 3, '/', NULL, NULL, true);

	                  return $user_id;
	               }
	            }
	        }

	        return false;
	    }
	}
?>