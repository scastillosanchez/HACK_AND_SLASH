<?php
$loc = $err = '';
session_start();
// Redirect if the user's session exists and they typed the link to the login page
if (isset($_SESSION['user-type']) && isset($_SESSION['username']))
{
  header("Location: http://" . $_SERVER['SERVER_NAME'] . "/" . $_SESSION['user-type'] . "/" . $_SESSION['user-type'] . "_home_page.php");
  exit();
}
else if (isset($_POST['username']) && isset($_POST['password']))
{ // If there was a valid username entered, do login procedure
  function clean_input_org($data)
  {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
  }

  function exists_and_correct($user, $pass, $pdo_obj)
  {
    // Checks both users and admins table in mysql db to see if 
    // 1) There is a user with that username
    // 2) If the password matches
    // 3) If that user is an admin
    // Returns an array of [#, user/admin] depending on what happens and whether they are a user or admin
    // Returns 0 if it successfully authenticates
    // Returns 1 if it is the wrong password
    // Returns 2 if it the user doesn't exist
    $result = [2, 'user'];  // Default is doesn't exist and is user

    //Look through users and admins
    $request = $pdo_obj->prepare("SELECT username, hashed_password, salt, userid FROM `users` WHERE username = :username");
    if (($request->execute(array(':username' => $user)) === True) && $request->rowCount())
    {
      $user = $request->fetch();
      // Check password that is hashed with sha256 and salted with the stored salt
  	  if (hash("sha256", $pass . $user['salt']) === $user['hashed_password'])
      { // Password matches
  	    $result[0] = 0;
        // User exists so check if it is admin
        $request = $pdo_obj->prepare("SELECT userid FROM `admins` WHERE userid = :userid");
        $request->execute(array(":userid" => $user['userid']));
        if ($request->fetch()[0] == $user['userid'])
        { // If they are admin, set the array[1] to admin
          $result[1] = 'admin';
        }
  	  }
  	  else
  	  { // Password doesn't match, throw a 1
  	    $result[0] = 1;
      }
    }
  // If no users matched it and no admins matche it, so no account exists and it will return default $result
  return $result;
  }

  // Grab username and password
  $user_name = clean_input_org($_POST['username']);
  $pass = clean_input_org($_POST['password']);

  // Check if user is in database
  require "./resources/general/connect.php";
  $code = exists_and_correct($user_name, $pass, $connected);
  $connected = NULL; // Close connection
  if ($code[0] === 0)
  { // If user exists, do this:
    $loc = "$code[1]";
    // Create user session info here storing username and user type
    $_SESSION['user-type'] = $loc;
    $_SESSION['username'] = $user_name;
    // Redirect to homepage based on user type
    header("Location: http://" . $_SERVER['SERVER_NAME'] . '/' . $loc . '/' . $loc . '_home_page.php');
    exit();
  }
  else
  { // If error occurred, set error message
    $err = "Username or Password Incorrect";
  }
}
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset = "UTF-8">
	<meta name = "description" content = "Log In Page">
	<title>HACK&amp;/ Log In</title>
	<link rel="stylesheet" href="./resources/index/index.css">
	<script type="text/javascript" src="resources/general/cookies_enabled.js"></script>
</head>

<body>
	<h1>Welcome to HACK&/</h1>
	<p class="signin">Please Sign In</p>
  <div id="container">
  	<form action="./" method="post" accept-charset="UTF-8" name="Login">
      <span class="info"><?php if ($err){ echo $err; $err = '';}?></span><br/>
      <fieldset>
        <label for="username">Username: </label><input id="username" type="text" name="username" maxlength="20" autofocus required><br/>
      </fieldset>
      <fieldset>
  		  <label for="password">Password: </label><input id="password" type="password" name="password" minlength="12" required><br/>
      </fieldset>
  		<input id="submit" type="submit" value="Login">
  	</form>
  </div>

	<p class="noaccount"><a href = "account_creation/registration.php">No Account? No Problem!</a></p>

	<p class="about"><a href = "about/about.html">About</a></p>

</body>
</html>
<?php unset($_POST); ?>