<?php
/*

https://asterisk_host.ru/api/input_calls/get.php?api_key=fegflgroergdf&start_calldate=1687311309&end_calldate=1687344309&min_diration=50

 */
ini_set('display_errors', 0);
define('API_KEY', 'fegflgroergdf');
define('HOST', 'https://asterisk_host.ru');

define('AC_DB_HOST', 'localhost');
define('AC_DB_UNAME', 'user');
define('AC_DB_UPASS', 'pwd');
define('AC_DB_NAME', 'asteriskcdrdb');

$channels = [
    '4923432344' => 'FAR',
    '4993432343' => 'TEST',
    '7495362342' => 'RUN',
    '7499432344' => 'WERT',
    '7812234343' => 'QST',
];

if (API_KEY != $_GET['api_key']) {
    return;
}
$start_calldate_timestamp = $_GET['start_calldate'];
$end_calldate_timestamp = $_GET['end_calldate'];


$start_calldate = $start_calldate_timestamp ? date('Y-m-d H:i:s', $start_calldate_timestamp) : date('Y-m-d H:i:s', time() - 5 * 60);
$end_calldate = $end_calldate_timestamp ? date('Y-m-d H:i:s', $end_calldate_timestamp) : null;

$min_diration = $_GET['min_diration'];

date_default_timezone_set('UTC');
$mysqli = new mysqli(AC_DB_HOST, AC_DB_UNAME, AC_DB_UPASS, AC_DB_NAME);

$query = 'SELECT * FROM cdr WHERE calldate >= "' . $start_calldate . '"';

if (strlen($end_calldate)) {
    $query .= ' and calldate <= "' . $end_calldate . '"';
}

if (strlen($min_diration)) {
    $query .= ' and duration >= "' . $min_diration . '"';
}

$query .= ' and LENGTH(recordingfile) > 0';

$query .= ' and ( false ';
foreach ($channels as $phone => $name) {
    $query .= 'or did = "' . $phone . '" ';
}
$query .= ')';
$query .= ' LIMIT 500';
$query .= ';';

$result = $mysqli->query($query);

$calls['var'] =
    [
    'start_calldate_timestamp' => $start_calldate_timestamp,
    'end_calldate_timestamp' => $end_calldate_timestamp,
    'start_calldate' => $end_calldate,
    'end_calldate' => $end_calldate,
    'min_diration' => $min_diration,
];

foreach ($result as $row) {
    $query = 'SELECT recordingfile, dst FROM cdr WHERE linkedid = ' . $row['linkedid']. ' and lastapp = "Dial" and disposition = "ANSWERED" LIMIT 1';
    $linked_row = mysqli_fetch_assoc($mysqli->query($query));
    if ($linked_row){
        $recordingfile = $linked_row['recordingfile'] ?: $row['recordingfile'];
        $dst = $linked_row['dst'] ?: $row['dst'];
        $recordingfile_url = HOST . '/api/getrecord/' . $recordingfile . '/' . substr($row['calldate'], 0, 10) . '/secret';
        $calls['data'][$row['uniqueid']] = [
            'calldate' => $row['calldate'],
            'channel_name' => $channels[$row['did']],
            'channel_phone' => $row['did'],
            'src' => $row['src'],
            'dst' => $dst,
            'duration' => $row['duration'],
            'recordingfile_url' => $recordingfile_url,
        ];
    }
}

//foreach ($calls['data'] as $uniqueid => $call){
//    echo "<br><br> uniqueid = " . $uniqueid;
//    foreach ($call as $key => $c) {
//        echo "<br> $key = " . $c;
//    }
//}

echo json_encode($calls);
