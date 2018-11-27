<html>
<?php

require("init.php");

if (isset($_GET['id']) and is_numeric($_GET['id'])) {
  //get device info
  $getDevice = $db->prepare('SELECT * FROM devices WHERE id = ?');
  $getDevice->bindValue(1, $_GET['id']);
  $result = $getDevice->execute();
  #print_r($device->fetchArray(SQLITE3_ASSOC));
  $device = $result->fetchArray(SQLITE3_ASSOC);
  echo "<strong>Device Serial: ".$device['sn']."</strong> (".$device['comment'].")<br/>";
  echo "Last check time: ".$device['last_check']." <br/>";
  echo "Last results: TX:".round(($device['last_tx']/1024/1024),2)." Mb RX : ".round(($device['last_rx']/1024/1024),2)." Mb <br/>";
  echo "<br/>";

  //get data for chart
  $getTraffic = $db->prepare('SELECT timestamp, tx, rx FROM traffic WHERE device_id = ? ORDER BY timestamp DESC LIMIT 6');
  $getTraffic->bindValue(1, $_GET['id']);
  $results = $getTraffic->execute();
  $chartData = '';
  while ($res = $results->fetchArray(SQLITE3_ASSOC)){
    #$res = $results->fetchArray(SQLITE3_ASSOC);
    #print_r($res);
    if(!isset($res['timestamp'])) continue;
      //set to Google Chart data format
      $chartData .= "['".date('d M H:i', strtotime($res['timestamp']))."',".round(($res['tx']/1024/1024),2).",".round(($res['rx']/1024/1024),2)."],";
  }
  $results->finalize();
  #echo $chartData;
  ?>
    <head>
      <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
      <script type="text/javascript">
        google.charts.load('current', {'packages':['bar']});
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {
          var data = google.visualization.arrayToDataTable([
            ['Date/Time', 'TX (Mb)', 'RX (Mb)'],
            <?php echo $chartData;?>
          ]);

          var options = {
            chart: {
              title: 'Traffic Stats',
              subtitle: 'Last 6 hours',
            }
          };

          var chart = new google.charts.Bar(document.getElementById('columnchart_material'));

          chart.draw(data, google.charts.Bar.convertOptions(options));
        }
      </script>
    </head>
    <body>
      <div id="columnchart_material" style="width: 700px; height: 500px;"></div>
    </body>

  <?php
  //get summary stats

  //get daily stats
  //query the db
  $daily = $db->prepare('SELECT sum(tx) as sumtx, sum(rx) as sumrx FROM traffic WHERE device_id = ? AND timestamp >= ? AND timestamp <= ?');
  $daily->bindValue(1, $_GET['id']);
  $daily->bindValue(2, date('Y-m-d 00:00:00'));
  $daily->bindValue(3, date('Y-m-d 23:59:59'));
  $result = $daily->execute();
  #print_r($result->fetchArray(SQLITE3_ASSOC));
  $dailyTraffic = $result->fetchArray(SQLITE3_ASSOC);
  //display results
  echo "<strong>Daily Stats</strong><br/>";
  echo "From: ".date('Y-m-d 00:00:00')." to ".date('Y-m-d 23:59:59')."<br/>";
  echo "TX: ".round(($dailyTraffic['sumtx']/1024/1024),2)." Mb ";
  echo "RX: ".round(($dailyTraffic['sumrx']/1024/1024),2)." Mb ";
  echo "Total: ".round((($dailyTraffic['sumtx']+$dailyTraffic['sumrx'])/1024/1024),2)." Mb </br>";
  echo "<br/>";

  //get weekly stats
  //getting sunday and saturday dates for current week
  $today = new DateTime();
  $currentWeekDay = $today->format('w');
  $firstdayofweek = clone $today;
  $lastdayofweek = clone $today;

  ($currentWeekDay != '0')?$firstdayofweek->modify('last Sunday'):'';
  ($currentWeekDay != '6')?$lastdayofweek->modify('next Saturday'):'';

  #echo $firstdayofweek->format('Y-m-d 00:00:00').' to '.$lastdayofweek->format('Y-m-d 23:59:59');

  //query the db
  $weekly = $db->prepare('SELECT sum(tx) as sumtx, sum(rx) as sumrx FROM traffic WHERE device_id = ? AND timestamp >= ? AND timestamp <= ?');
  $weekly->bindValue(1, $_GET['id']);
  $weekly->bindValue(2, $firstdayofweek->format('Y-m-d 00:00:00'));
  $weekly->bindValue(3, $lastdayofweek->format('Y-m-d 23:59:59'));
  $result = $weekly->execute();
  #print_r($weeklyTraffic->fetchArray(SQLITE3_ASSOC));
  $weeklyTraffic = $result->fetchArray(SQLITE3_ASSOC);
  //display results
  echo "<strong>Weekly Stats</strong><br/>";
  echo "From: ".$firstdayofweek->format('Y-m-d 00:00:00')." to ".$lastdayofweek->format('Y-m-d 23:59:59')."<br/>";
  echo "TX: ".round(($weeklyTraffic['sumtx']/1024/1024),2)." Mb ";
  echo "RX: ".round(($weeklyTraffic['sumrx']/1024/1024),2)." Mb ";
  echo "Total: ".round((($weeklyTraffic['sumtx']+$weeklyTraffic['sumrx'])/1024/1024),2)." Mb </br>";
  echo "<br/>";

  //get monthly stats
  //query the db
  $monthly = $db->prepare('SELECT sum(tx) as sumtx, sum(rx) as sumrx FROM traffic WHERE device_id = ? AND timestamp >= ? AND timestamp <= ?');
  $monthly->bindValue(1, $_GET['id']);
  $monthly->bindValue(2, date('Y-m-01 00:00:00'));
  $monthly->bindValue(3, date('Y-m-t 23:59:59'));
  $result = $monthly->execute();
  #print_r($monthlyTraffic->fetchArray(SQLITE3_ASSOC));
  $monthlyTraffic = $result->fetchArray(SQLITE3_ASSOC);
  //display results
  echo "<strong>Monthly Stats</strong><br/>";
  echo "From: ".date('Y-m-01 00:00:00')." to ".date('Y-m-t 23:59:59')."<br/>";
  echo "TX: ".round(($monthlyTraffic['sumtx']/1024/1024),2)." Mb ";
  echo "RX: ".round(($monthlyTraffic['sumrx']/1024/1024),2)." Mb ";
  echo "Total: ".round((($monthlyTraffic['sumtx']+$monthlyTraffic['sumrx'])/1024/1024),2)." Mb </br>";
  echo "<br/>";

  $result->finalize();
}
else {
  $result = $db->query('SELECT * FROM devices');
  if(empty($result->fetchArray(SQLITE3_ASSOC))) {
    echo "No devices found.<br/>";
  }
  else {
    $result = $db->query('SELECT * FROM devices');
    while ($device = $result->fetchArray(SQLITE3_ASSOC)){
      echo '<a href="?id='.$device['id'].'"><strong>'.$device['sn'].'</strong></a> ('.$device['comment'].') Last check: '.$device['last_check'].'<br/>';
    }
  }
  $result->finalize();
}
?>
</html>
