<?php
include "config.php";
if (isset($_SERVER["REMOTE_ADDR"])) {
        die();
}

  $conn = mysqli_connect($dbservername, $dbusername, $dbpassword, $dbname);
  $sql = "SELECT id, timebeingup, url, isup, status FROM sitesmonitored WHERE status='active'";
  $result = mysqli_query($conn, $sql);
  if (mysqli_num_rows($result) > 0) {
    while($row = $result->fetch_assoc()) {
      if ($row["status"] == "active") {
        $website = file_get_contents($row["url"]);
        $id = $row["id"];
        $time = time();
        if ($website == false) {
         $nsql = "INSERT INTO uptimehistory (id, checktime, checkresult)
         VALUES ('$id', '$time', 'fail')";
         mysqli_query($conn, $nsql);
         $nsql = "UPDATE sitesmonitored SET isup='false', timebeingup='down' WHERE id='$id'";
         mysqli_query($conn, $nsql);
        } else {
         $nsql = "INSERT INTO uptimehistory (id, checktime, checkresult)
         VALUES ('$id', '$time', 'success')";
         mysqli_query($conn, $nsql);
         if ($row["timebeingup"] == "down" || $row["timebeingup"] == "0") {
          $nsql = "UPDATE sitesmonitored SET isup='false', timebeingup='$time' WHERE id='$id'";
         mysqli_query($conn, $nsql);
         }
         $nsql = "UPDATE sitesmonitored SET isup='true' WHERE id='$id'";
         mysqli_query($conn, $nsql);
        }
      }
    }
}
mysqli_close($conn);
?>
