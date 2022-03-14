<?php
session_start();
include 'config.php';
if ($_SESSION["auth"] !== "1") {
        header('Location: /');
        die();
}
// thank you for this excerpt, arnorhs from stack overflow
function humanTiming ($time)
{

    $time = time() - $time; // to get the time since that moment
    $time = ($time<1)? 1 : $time;
    $tokens = array (
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    );

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
    }

}

if (isset($_GET["del"]) && $_SESSION["auth"] == "1" && $_GET["key"] == $_SESSION["key"]) {
    $siteid = $_GET["del"];
    $conn = mysqli_connect($dbservername, $dbusername, $dbpassword, $dbname);
    if (!$conn) {
        die("An error occurred.");
    }
    $sql = "SELECT id, status FROM sitesmonitored WHERE id='$siteid' AND status='active'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) < 1) {
        header("Location: uptimemon.php");
        die();
    }
    mysqli_close($conn);
    $conn = new mysqli($dbservername, $dbusername, $dbpassword, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $sql = "UPDATE sitesmonitored SET status='inactive' WHERE id='$siteid'";
    $conn->query($sql) === TRUE;
    mysqli_close($conn);
 
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
      max-width:650px;
      margin: auto;
      padding: 15px;
    }


    </style>
    </head>
    <body>
        <main class="uptime">
<h1 class="display-4">Uptime Monitor</h1>
        <div class="table-responsive">
<table class="table">
  <thead>
    <tr>
      <th scope="col">Website Name</th>
      <th scope="col">Website Domain</th>
      <th scope="col">Uptime Duration</th>
      <th scope="col">Status</th>
      <th scope="col">Uptime Percentage</th>
      <th scope="col">Manage</th>
    </tr>
  </thead>
  <tbody>
<?php
  $user = $_SESSION["user"];
  $conn = mysqli_connect($dbservername, $dbusername, $dbpassword, $dbname);
  $sql = "SELECT id, name, timebeingup, url, isup, status FROM sitesmonitored WHERE monitoredby='$user'";
  $result = mysqli_query($conn, $sql);
  if (mysqli_num_rows($result) > 0) {
    while($row = $result->fetch_assoc()) {
      if ($row["status"] == "active") {
        $name = $row["name"];
        $siteid = $row["id"];
        $timeup = $row["timebeingup"];
        $isup = $row["isup"];
        $website = $row["url"];
        $color = "";
        $key = $_SESSION["key"];
        if ($isup == "false") {
         $color = ' class="table-danger"';
         $isup = "Down";
         $timeup = "Down";
        }
        if ($isup == "true") {
         $color = ' class="table-success"';
         $timeup = humanTiming($timeup);
         $isup = "Online";
        }

        $nsql = "SELECT id, checkresult FROM uptimehistory WHERE id='$siteid'";
        $nresult = mysqli_query($conn, $nsql);
        if (mysqli_num_rows($nresult) > 0) {
                $returnedup = 0;
                $returneddown = 0;
                while($nrow = $nresult->fetch_assoc()) {
                        if ($nrow["checkresult"] == "success") {
                                $returnedup++;
                        }
                        if ($nrow["checkresult"] == "fail") {
                                $returneddown++;
                        }
                }
                $uptimepercentage = round(($returnedup / ($returnedup + $returneddown))*100,2) ."%";
        } else {
                $uptimepercentage = "Not checked yet";
        }

        echo '<tr'.$color.'><th scope="row">'.$name.'</th>';
        echo '<td>'.$website.'</td>';
        echo '<td>'.$timeup.'</td>';
        echo '<td>'.$isup.'</td>';
        echo '<td>'.$uptimepercentage.'</td>';
        echo '<td><a href="uptimemon.php?del='.$row["id"].'&key='.$key.'" class="btn btn-danger">Delete</a></td>';
      }
    }
}
mysqli_close();
?>
  </tbody>
</table>
            </div>
<a class="btn btn-outline-danger w-100" href="/">Back to Homepage</a>
        </main>
    </body>
</html>
