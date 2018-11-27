<?php
/*
Usage: /collector.php?sn=<SERIAL NUMBER>&tx=<INTERFACE TX BYTES>&rx=<INTERFACE TX BYTES>
*/

require("init.php");

// Check input data
if (isset($_GET['sn'])
	and isset($_GET['tx']) and is_numeric($_GET['tx'])
	and isset($_GET['rx']) and is_numeric($_GET['rx'])) {
	$device_serial = substr($_GET['sn'], 0, 12);
} else {
	echo 'fail';
	exit;
}

// Check if device exists
$getDevice = $db->prepare('SELECT id, sn, comment, last_check, last_tx, last_rx FROM devices WHERE sn="'.$device_serial.'"');
$result = $getDevice->execute();
$device = $result->fetchArray(SQLITE3_ASSOC);
if (empty($device)) {
	//Add new device
	$addDevice = $db->prepare('INSERT INTO devices (sn, last_check, last_tx, last_rx)
	VALUES (:serial, :time, :tx, :rx)');
	$addDevice->bindValue(':serial', $device_serial);
	$addDevice->bindValue(':time', date('Y-m-d H:i:s'));
	$addDevice->bindValue(':tx', $_GET['tx']);
	$addDevice->bindValue(':rx', $_GET['rx']);
	$addDevice->execute();
	$device['id'] = $db->lastInsertRowid();
}
else {
	//Update last received data
	$updateData = $db->prepare('UPDATE devices SET last_check=:time, last_tx=:tx, last_rx=:rx WHERE id=:id');
	$updateData->bindValue(':id', $device['id']);
	$updateData->bindValue(':time', date('Y-m-d H:i:s'));
	$updateData->bindValue(':tx', $_GET['tx']);
	$updateData->bindValue(':rx', $_GET['rx']);
	$updateData->execute();
}

//Update traffic data
$updateTraffic = $db->prepare('INSERT INTO traffic (device_id, timestamp, tx, rx)
	VALUES (:id, :time, :tx, :rx)');
$updateTraffic->bindValue(':id', $device['id']);
$updateTraffic->bindValue(':time', date('Y-m-d H:i:s'));
$updateTraffic->bindValue(':tx', $_GET['tx']);
$updateTraffic->bindValue(':rx', $_GET['rx']);
$updateTraffic->execute();

echo 'traffic data updated';
