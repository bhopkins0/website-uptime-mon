<?php
session_start();
include 'config.php';

// Login attempt
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_SESSION["auth"] !== "1") {
  $user         = strtolower($_POST["username"]);
  $password     = $_POST["password"];
  $attempt_ip   = ip2long($_SERVER['REMOTE_ADDR']);
  $attempt_time = time();

  if (strlen($user) > 32 || strlen($password) > 50 || strlen($password) < 8) {
  $_SESSION["loginerr"] = 1;
  header("Location: /");
  die();
  }
  if (!preg_match('/^[A-Za-z0-9]+$/', $user)) {
  $_SESSION["loginerr"] = 1;
  header("Location: /");
  die();
  }

  $conn = mysqli_connect($dbservername, $dbusername, $dbpassword, $dbname);
  $sql = "SELECT creation_time FROM accounts WHERE username='$user'";
  $result = mysqli_query($conn, $sql);
  if (mysqli_num_rows($result) < 1) {
  $_SESSION["loginerr"] = 1;
  header("Location: /");
  die();
  }
  mysqli_close($conn);

  $conn = new mysqli($dbservername, $dbusername, $dbpassword, $dbname);
  $sql = "SELECT password FROM accounts WHERE username='$user'";
  $result = mysqli_query($conn, $sql);
  if (mysqli_num_rows($result) > 0) {
    while($row = $result->fetch_assoc()) {
      if (password_verify($password, $row["password"])) {
        $_SESSION["auth"]  = "1";
        $success = 1;
        $_SESSION["user"]  = $user;
        $_SESSION["key"] = random_int(1,9999999999); // Idea here is to prevent potential CSRF attack
      } else {
        $_SESSION["loginerr"] = 1;
        $success = 0;
      }
    }
  }
  $conn->close();

if ($success == 1) {
  $conn = new mysqli($dbservername, $dbusername, $dbpassword, $dbname);
  $sql = "INSERT INTO login_attempts(username, ip, result, login_time)
  VALUES ('$user', '$attempt_ip', 'success', $attempt_time)";

  if (mysqli_query($conn, $sql)) {
    header("Location: /");
  }
  $conn->close();
die();
}
if ($success == 0) {
  $conn = new mysqli($dbservername, $dbusername, $dbpassword, $dbname);
  $sql = "INSERT INTO login_attempts(username, ip, result, login_time)
  VALUES ('$user', '$attempt_ip', 'fail', $attempt_time)";

  if (mysqli_query($conn, $sql)) {
    header("Location: /");
  }
  $conn->close();
die();
}
}

// Authenticated user wants to log out
if ($_POST["lo"] == $_SESSION["key"]) {
        $_SESSION["auth"] = "";
        $_SESSION["key"] = "";
}

// Authenticated user wants to add new website to monitor
if (isset($_POST["name"]) && isset($_POST["url"]) && isset($_POST["httptype"]) && $_SESSION["auth"] == "1" && $_POST["key"] == $_SESSION["key"]) {
    $name  = strtolower($_POST["name"]);
    $url   = strtolower($_POST["url"]);
    $http  = strtolower($_POST["httptype"]);
    $user  = $_SESSION["user"];
    if ($http != "http" && $http != "https") {
        $_SESSION["err"] = 4;
        header("Location: /");
        die();
    }

    if ($http == "http") {
        $prefix="http://";
    }

    if ($http == "https") {
        $prefix="https://";
    }



    // name is not alphanumeric and is greater than 32
    if (strlen($name) > 32 || !preg_match("/^[A-Za-z0-9]*$/", $name)) {
        $_SESSION["err"] = 2;
        header("Location: /");
        die();
    }

    // name is blank
    if (strlen($name) < 1) {
        $_SESSION["err"] = 3;
        header("Location: /");
        die();
    }

    // domain name invalid
    if (strlen($url) > 255 || !preg_match("/^[A-Za-z0-9.]*$/", $url)) {
        $_SESSION["err"] = 5;
        header("Location: /");
        die();
    }

    $url = $prefix . $url;


    $conn = mysqli_connect($dbservername, $dbusername, $dbpassword, $dbname);
    if (!$conn) {
        die("An error occurred.");
    }
    $sql = "SELECT id FROM sitesmonitored WHERE monitoredby='$user'";
    $result = mysqli_query($conn, $sql);
    $monitorid = $user . mysqli_num_rows($result);
    mysqli_close($conn);

    $conn = new mysqli($dbservername, $dbusername, $dbpassword, $dbname);
    if (!$conn) {
        die("Error");
    }
    $sql = "INSERT INTO sitesmonitored (id, name, url, timebeingup, checkfrequency, monitoredby, isup, status)
    VALUES ('$monitorid', '$name', '$url', '0', '300', '$user', 'false', 'active')";
    if (mysqli_query($conn, $sql)) {

    } else {
        die("Error");
    }

    $_SESSION["success"] = 1;
    header("Location: /");
    die();
}

?>

<html>
    <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uptime Monitor</title>
    <link rel="stylesheet" href="bootstrap.min.css">
    <style>
    html,
    body {
      height: 100%;
    }

    body {
      display: flex;
      align-items: center;
      padding-top: 40px;
      padding-bottom: 40px;
      background-color: #f5f5f5;
    }

    .uptime {
      width: 100%;
      max-width: 500px;
      margin: auto;
      padding: 15px;
    }


    </style>
    </head>
    <body>
        <main class="uptime">
        <h1 class="display-4">Uptime Monitor</h1>
     <div>
<?php
if ($_SESSION["auth"] == "1") {
    $key = $_SESSION["key"];
    if ($_SESSION["err"] == 1) {
        echo '<p class="lead text-danger">Common Name is taken</p>';
        $_SESSION["err"] = 0;
    }
    if ($_SESSION["err"] == 2) {
        echo '<p class="lead text-danger">Name must be alphanumeric and less than 32 characters long.</p>';
        $_SESSION["err"] = 0;
    }
    if ($_SESSION["err"] == 3) {
        echo '<p class="lead text-danger">Name can not be empty.</p>';
        $_SESSION["err"] = 0;
    }
    if ($_SESSION["err"] == 4) {
        echo '<p class="lead text-danger">You must choose HTTP or HTTPS.</p>';
        $_SESSION["err"] = 0;
    }
    if ($_SESSION["err"] == 5) {
        echo '<p class="lead text-danger">Domain name must only contain alphanumeric characters and periods.</p>';
        $_SESSION["err"] = 0;
    }
    if ($_SESSION["success"] == 1) {
        echo '<p class="lead text-success">Successfully added to the uptime monitor.</p>';
        $_SESSION["success"] = 0;
    }
echo <<<EOL
<form method="post">
  <div class="form-group">
    <input type="text" class="form-control" name="name" placeholder="Name">
    <input type="text" class="form-control" name="url" placeholder="Website domain name (example.com)">
  <input class="form-check-input" type="radio" name="httptype" id="https" value="https">
  <label class="form-check-label" for="https">
    HTTPS
  </label>
  <input class="form-check-input" type="radio" name="httptype" id="http" value="http">
  <label class="form-check-label" for="http">
    HTTP
  </label>
    <input type="hidden" id="key" name="key" value="$key">
  </div><br>
  <input class="btn btn-outline-primary w-100" type="submit" value="Add to uptime monitor">
</form>
<div class="card-deck mb-3 text-center">
<div class="card mb-4 box-shadow">
<div class="card-header">
<h4 class="my-0 font-weight-normal">View Monitor</h4>
</div>
<div class="card-body">
<p class="lead">View uptime statistics of websites you monitor</p>
<a class="btn btn-primary btn-block" href="uptimemon.php">Go to uptime monitor</a>
</div>
</div>
<form method="post">
<input type="hidden" id="lo" name="lo" value="$key">
<input class="btn btn-danger w-100" type="submit" value="Logout">
</form>
            </div>
        </main>
    </body>
</html>

EOL;
die();
}
?>
<form method="post">
<?php
if ($_SESSION["loginerr"] == 1) {
        echo '<div class="alert alert-danger" role="alert">Invalid username or password</div>';
        $_SESSION["loginerr"] = 0;
}
?>
<div class="form-group">
<input type="text" class="form-control" name="username" placeholder="Username">
</div><br>
<div class="form-group">
<input type="password" class="form-control" name="password" placeholder="Password">
</div><br>
<input class="btn btn-primary w-100" type="submit" value="Login">
</form>
            </div>
        </main>
    </body>
</html>
