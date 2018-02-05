<?php


$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
$server = "irc.tauri.hu";
$port = "6667";
$yonk = socket_connect($sock, $server, $port);

socket_write($sock, "NICK sontiBOT \r\n");
socket_write($sock, "USER sontiBOT sontiBOT sontiBOT :Sontii\r\n");
socket_write($sock, "JOIN #wow\r\n");

priv();

function priv(){
	global $sock;
	socket_write($sock, ":sontiBOT PRIVMSG Sontii :CONNECTED! \r\n");
}
while (True) {
$wut = socket_read($sock, 1024, PHP_NORMAL_READ);
//echo $wut;
if (strpos($wut, "PING :" . $server) !== false) {
	echo "PING received\n";
	socket_write($sock, "PONG :" . $server . "\r\n");
	echo "PONG sent\n";
}
if (strpos($wut, "PRIVMSG") !== false) {
$from = parse($wut, ":", "!");
$to = parse($wut, "PRIVMSG "," :");
$msg = parse_last($wut, $to . " :");
$msgtime = date('Y-m-d H:i:s');

echo "PRIVMSG: " . $from . " -> " . $to . ": " . $msg . "\n";

if (strpos($msg, "!last") !== false) {
	$last_pieces = explode(" ", $msg);
	echo $last_pieces[1];
	if($last_pieces[1] < 20){
		getlast($from, $last_pieces[1]);
	}
}

// SAVE LOG
if(substr( $to, 0, 1 ) === "#"){
	REGISTER($from, $to, $msg, $msgtime);
 }
}

// WHILE VÉGE
}

function parse($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}
function parse_last($miben, $miutan){
return array_pop(explode($miutan, $miben));
}

function REGISTER($fromuser, $touser, $msg, $whattime)
{
	$db = new SQLite3('/var/www/sqlite/irclog.db');
	$stmt = $db->prepare('INSERT INTO irclog VALUES (?,?,?,?,?)');
	$stmt->bindValue(1, NULL, SQLITE3_INTEGER);
	$stmt->bindValue(2, $fromuser, SQLITE3_TEXT);
	$stmt->bindValue(3, $touser, SQLITE3_TEXT);
	$stmt->bindValue(4, $msg, SQLITE3_TEXT);
	$stmt->bindValue(5, $whattime, SQLITE3_TEXT);
	$result = $stmt->execute();
	$stmt->close();
	unset($stmt);
	$db->close();
	unset($db);

//	echo "Registered\n";
}

function getlast($kinek, $mennyit){
//echo "getlast()";
$db = new SQLite3('/var/www/sqlite/irclog.db');
//$linelimit = 5;
$stmt = $db->prepare('select whattime, fromuser, touser, msg from (select * from irclog order by id desc limit (?)) irclog order by id asc;');
$stmt->bindValue(1, $mennyit, SQLITE3_TEXT);
$result = $stmt->execute();

$i = 0;
while($res = $result->fetchArray(SQLITE3_ASSOC)){

	    $row[$i]['whattime'] = $res['whattime'];
		$row[$i]['fromuser'] = $res['fromuser'];
		$row[$i]['touser'] = $res['touser'];
		$row[$i]['msg'] = $res['msg'];

        $whattime = $row[$i]['whattime'];
		$fromuser = $row[$i]['fromuser'];
		$touser = $row[$i]['touser'];
		$msg = $row[$i]['msg'];

        $i++;

		$log =  $whattime . " > " . $fromuser . " > ". $touser . " > " . $msg . "\n";

//		sendpriv("asd", "asdd");

global $sock;
socket_write($sock, ":sontiBOT PRIVMSG $kinek :$log! \r\n");
	}

}


?>
