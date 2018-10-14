<?php

/*
The bot is written in PHP by GRP.
Still in early ALPHA. Ver.: 0.8
*/

$time_start = microtime(true);

$sock   = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
$server = "irc.tauri.hu";
$port   = "6667";
$yonk   = socket_connect($sock, $server, $port);
socket_write($sock, "NICK BOTNAME \r\n");
socket_write($sock, "USER BOTNAME BOTNAME BOTNAME :GRP\r\n");
socket_write($sock, "JOIN #CHANNEL\r\n");
//socket_write($sock, "/msg nickserv group GRP jelszo\r\n");
//socket_write($sock, "PRIVMSG NickServ group GRP johosszujelszoqwe\r\n");
socket_write($sock, "PRIVMSG NickServ :identify johosszujelszoqwe\r\n");
$toPaste = "";
welcome();
$bandwidth = 0;
$rxdata;

$lastSeen; // = date('Y-m-d H:i:s');

sendMail("IRCBot Started!", "RPI-IRC-BOT");

function sendMail($msg, $subject)
{
    exec("echo $msg | mail -s $subject EMAIL@EXAMPLE.COM");
}

function get_lastquit($who)
{
    $servername = "localhost";
    $username   = "root";
    $password   = "PASSWORD";
    $dbname     = "DATABASE";
    
    $whoo = preg_replace('/\s+/', '', $who);
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $sql    = "SELECT lastquit FROM irc_last WHERE user = '$whoo' ORDER BY lastquit DESC LIMIT 1";
    $result = $conn->query($sql);
    //    echo $sql . "\n";
    if (!$result) {
        echo "Invalid query: " . $conn->error . "\n";
    }
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            return $row["lastquit"]; // $row["id/user/lastjoin/lastquit"]
        }
    } else {
        return "N/A. Database error. (query?)";
    }
    $conn->close();
}

function record_login($user, $lastjoin, $lastquit)
{
    global $i;
    $servername = "localhost";
    $username   = "root";
    $password   = "PASSWORD";
    $dbname     = "DATABASE";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $sql = $conn->prepare("INSERT INTO irc_last (id, user, lastjoin, lastquit) VALUES ('',?,?,?)");
    $sql->bind_param('sss', $user, $lastjoin, $lastquit);
    $sql->execute();
    echo "[LOGIN] Info recorded.\n";
    $sql->close();
    $conn->close();
    $i++;
}

function welcome()
{
    global $sock;
    sendSocket("PRIVMSG", "Sontiii", "Connected!");
    sendSocket("PRIVMSG", "Sontii", "Connected!");
    sendSocket("PRIVMSG", "Sont", "Connected!");
    sendSocket("PRIVMSG", "Sonti", "Connected!");
}
//echo "WHILE ELOTT\n";
while (true) {
    $wut       = @socket_read($sock, 1024, PHP_NORMAL_READ);
    $bandwidth = $bandwidth + strlen($wut);
    echo $wut . "\n";
    
    if (socket_strerror(socket_last_error($sock)) !== "Success") {
        $socketState = socket_strerror(socket_last_error($sock));
        // echo $socketState . "\n";
        if (strpos($socketState, "Success") != TRUE) {
            // echo "SOCKET Error happened\n";
            // exit();
            sleep(10);
        }
    }
    
    
    if (strpos($wut, "Cannot join channel") !== false) {
        echo "Bot is BANNED !\n";
        sendMail("IRCBot has been BANNED!", "RPI-IRC-BOT");
    }
    
    if ($wut === '') {
        echo "EMPTY DATA RECEIVED\n";
        socket_write($sock, "JOIN #CHANNEL\r\n");
        sleep(10);
    }
    
    if (strpos($wut, "KICK #CHANNEL BOTNAME") !== false) {
        socket_write($sock, "JOIN #CHANNEL\r\n");
        sleep(10);
        sendMail("The BOT has been KICKED!", "RPI-IRC-BOT");
    }

    if (strpos($wut, "PING :" . $server) !== false) {
        socket_write($sock, "PONG :" . $server . "\r\n");
    }

    if (strpos($wut, "JOIN :#CHANNEL") !== false) {
        $from = parse($wut, ":", "!");
        $date = date('Y-m-d H:i:s');
        record_login($from, $date, "");
        echo "Valaki megjott!\n";
    }

    if ((strpos($wut, ":Sont") !== false) && (strpos($wut, "JOIN :#CHANNEL") !== false)) {
        $from = parse($wut, ":", "!");
	$sontlast = get_lastquit($from);
	sendSocket("NOTICE", $from, "Last seen: $sontlast");
    }

    if ((strpos($wut, ":") !== false) && (strpos($wut, "QUIT") !== false)) {
        $from = parse($wut, ":", "!");
        $date = date('Y-m-d H:i:s');
        record_login($from, "", $date);
    }

    if (strpos($wut, "PRIVMSG") !== false) {
        $from    = parse($wut, ":", "!");
        $to      = parse($wut, "PRIVMSG ", " :");
        $msg     = parse_last($wut, $to . " :");
        $msgtime = date('Y-m-d H:i:s');

	if ((strpos($wut, "PING") !== false) && (strpos($wut, "#CHANNEL") != true)) {
            $arr = explode("PING ", $wut);
            socket_write($sock, "NOTICE $from :\x01PING $arr[1]\x01\r\n");
        }
        if (strpos($wut, "VERSION") !== false) {
            socket_write($sock, "NOTICE $from :\x01VERSION SontBot 0.9 Alpha \x01 \r\n");
        }
        if (strpos($wut, "DCC SEND") !== false) {
            sendSocket("PRIVMSG", $from, "File sending is not yet supported.");
        }
        if (strpos($to, '#CHANNEL') !== false) {
            // echo "PUBLIC: " . $from . " -> " . $to . ": " . $msg . "\n";
        } else {
            echo "PRIVATE: " . $from . " -> " . $to . ": " . $msg . "\n";
        }
        if ((strpos($msg, "!sontibot links") !== false) && (substr($msg, 0, 1) === "!")) {
            sendSocket("PRIVMSG", $from, "LINK");;
        }
        if ((strpos($msg, "!when") !== false) && (substr($msg, 0, 1) === "!")) {
            $arr    = explode("when ", $wut); // $arr[1]
            $qu     = get_lastquit($arr[1]); // QUERY
            $date   = date('Y-m-d H:i:s');
            $format = 'Y-m-d H:i:s';
            $date1  = DateTime::createFromFormat($format, $qu);
            $date2  = DateTime::createFromFormat($format, $date);
            if (is_a($date1, 'DateTime')) {
                if (is_a($date2, 'DateTime')) {
                    $interval = $date1->diff($date2);
                    $elapsed  = $interval->format('%h hours %i minutes');
                    if ($qu !== "0000-00-00 00:00:00")
					{
                        if (strpos($to, '#CHANNEL') !== false) {
							sendSocket("PRIVMSG", "#CHANNEL", "[SEEN] Last seen: $qu ($elapsed ago)");
						} else {
							sendSocket("PRIVMSG", $from, "[SEEN] Last seen: $qu ($elapsed ago)");
						}
                    }
					else
					{
                        if (strpos($to, '#CHANNEL') !== false) {
							sendSocket("PRIVMSG", "#CHANNEL", "[SEEN] No data.");
						} else {
							sendSocket("PRIVMSG", $from, "[SEEN] No data.");
						}
                    }
                }
            } else
			{
                        if (strpos($to, '#CHANNEL') !== false) {
							sendSocket("PRIVMSG", "#CHANNEL", "[SEEN] No data.");
						} else {
							sendSocket("PRIVMSG", $from, "[SEEN] No data.");
						}
            }
        }
        
        // NEW BETA FUNCTION
        if ((strpos($msg, "!sontibot lasthour") !== false) && (substr($msg, 0, 1) === "!")) {
            $arr = explode("lasthour ", $wut); // $arr[1]
            echo $arr[1] . "\n";
            $lastHourQuery    = "SELECT * FROM irclog WHERE whattime >= datetime('now', '-$arr[1] hours')";
            $lastHourQuerySTR = "SELECT * FROM irclog WHERE whattime >= datetime('now', '-" . $arr[1] . " hours')";
            echo "\n" . $lastHourQuerySTR . "\n";
            $lastHourPasteLink = db_query_alter($lastHourQuery);
            
            if (strpos($to, '#CHANNEL') !== false) {
                socket_write($sock, ":BOTNAME PRIVMSG #CHANNEL :Last $arr[1] hour log: $lastHourPasteLink \r\n");
            } else {
                socket_write($sock, ":BOTNAME PRIVMSG $from :Last $arr[1] hour log: $lastHourPasteLink \r\n");
            }
        }
        if ((strpos($msg, "!sontibot onehour") !== false) && (substr($msg, 0, 1) === "!")) {
            $lastHourQuery     = "SELECT * FROM irclog WHERE whattime >= datetime('now', '-1 hours')";
            $lastHourPasteLink = db_query_alter($lastHourQuery);
            if (strpos($to, '#CHANNEL') !== false) {
                socket_write($sock, ":BOTNAME PRIVMSG #CHANNEL :Last one hour log: $lastHourPasteLink \r\n");
            } else {
                socket_write($sock, ":BOTNAME PRIVMSG $from :Last one hour log: $lastHourPasteLink \r\n");
            }
        }
        if ((strpos($msg, "!sontibot about") !== false) && (substr($msg, 0, 1) === "!")) {
            $upp      = (microtime(true) - $time_start);
            $uptime   = gmdate("H:i:s", $upp);
            $servup   = server_uptime();
            $servload = server_load();
            $logsize  = round(filesize("/var/www/sqlite/irclog.db") / 1024 / 1024, 2) . " MB";
            $rowcount = db_query();
            if (strpos($to, '#CHANNEL') !== false) {
                $rxdata = byteConvert($bandwidth);
                socket_write($sock, ":BOTNAME PRIVMSG #CHANNEL :[ABOUT] Bot uptime: $uptime | Server uptime: $servup | Data received: $rxdata | Server load: $servload \r\n");
                socket_write($sock, ":BOTNAME PRIVMSG #CHANNEL :[ABOUT] The bot written in: PHP | By: GRP(note that it's in early alpha)\r\n");
                socket_write($sock, ":BOTNAME PRIVMSG #CHANNEL :[ABOUT] Size of database: $logsize | Rows in db: $rowcount \r\n");
            } else {
                $rxdata = byteConvert($bandwidth);
                socket_write($sock, ":BOTNAME PRIVMSG $from :[ABOUT] Bot uptime: $uptime | Server uptime: $servup | Data received: $rxdata | Server load: $servload \r\n");
                socket_write($sock, ":BOTNAME PRIVMSG $from :[ABOUT] The bot written in: PHP | By: GRP (note that it's in early alpha)\r\n");
                socket_write($sock, ":BOTNAME PRIVMSG $from :[ABOUT] Size of database: $logsize | Rows in db: $rowcount \r\n");
            }
        }
        if ((strpos($msg, "!sontibot network hourly") !== false) && (substr($msg, 0, 1) === "!")) {
            exec("vnstati -h -i wlan0 -o /var/www/snippets/hourly.png");
            $link = "http://sont.me/snippets/hourly.png";
            if (strpos($to, '#CHANNEL') !== false) {
                socket_write($sock, ":BOTNAME PRIVMSG #CHANNEL :Network Hourly Statistics: $link \r\n");
            } else {
                socket_write($sock, ":BOTNAME PRIVMSG $from :Network Hourly Statistics: $link \r\n");
            }
        }
        if ((strpos($msg, "!sontibot network summary") !== false) && (substr($msg, 0, 1) === "!")) {
            exec("vnstati -s -i wlan0 -o /var/www/snippets/summary.png");
            $link = "http://sont.me/snippets/summary.png";
            if (strpos($to, '#CHANNEL') !== false) {
                socket_write($sock, ":BOTNAME PRIVMSG #CHANNEL :Network Statistics: $link \r\n");
            } else {
                socket_write($sock, ":BOTNAME PRIVMSG $from :Network Statistics: $link \r\n");
            }
        }
        if ((strpos($msg, "!sontibot bitcoin") !== false) && (substr($msg, 0, 1) === "!")) {
            $url           = "https://bitpay.com/api/rates";
            $json          = file_get_contents($url);
            $data          = json_decode($json, TRUE);
            $rate          = $data[1]["rate"];
            $usd_price     = 10;
            $bitcoin_price = round($usd_price / $rate, 8);
            
            if (strpos($to, '#CHANNEL') !== false) {
                socket_write($sock, ":BOTNAME PRIVMSG #CHANNEL :Bitcoin price: $rate USD \r\n");
            } else {
                socket_write($sock, ":BOTNAME PRIVMSG $from :Bitcoin price: $rate USD \r\n");
            }
        }
        if ((strpos($msg, "!sontibot help") !== false) && (substr($msg, 0, 1) === "!")) {
            $helpString = "[BOTNAME-HELP] !last <1-10000> | !sontibot about | !sontibot onehour | !sontibot bitcoin | !sontibot network hourly | !sontibot network summary | !sontibot help\n";
            if (strpos($to, '#CHANNEL') !== false) {
		sendSocket("PRIVMSG", "#CHANNEL", $helpString);
            } else {
		sendSocket("PRIVMSG", $from, $helpString);
            }
        }
        if ((strpos($msg, "!last") !== false) && (substr($msg, 0, 1) === "!")) {
            $last_pieces = explode(" ", $msg);
            if ((int) $last_pieces[1] < 10001 && !((int) $last_pieces[1] < 1)) {
                if (is_numeric((int) $last_pieces[1]) === TRUE) {
                    $linkToLast = getlast($from, (int) $last_pieces[1], $to);
                    if (strpos($hovaMent, '#CHANNEL') !== false) {
			sendSocket("PRIVMSG", "#CHANNEL", $linkToLast);
                    } else {
			sendSocket("PRIVMSG", $from, $linkToLast);
                    }
                }
            } else {
                if (strpos($to, '#CHANNEL') !== false) {
			sendSocket("PRIVMSG", "#CHANNEL", "Hibas formatum!");
                } else {
			sendSocket("PRIVMSG", $from, "Hibas formatum!");
                }
            }
        }
	// ACTUAL RECORD TO DATABASE
        if (substr($to, 0, 1) === "#") {
            REGISTER($from, $to, $msg, $msgtime);
        }
    }
    
    // WHILE VEGE
    
}

function sendSocket($type, $touser, $tosend)
{
    global $sock;
    // type: PRIVMSG, NOTICE
    socket_write($sock, ":BOTNAME $type $touser :$tosend \r\n");
}

function parse($string, $start, $end)
{
    $string = ' ' . $string;
    $ini    = strpos($string, $start);
    if ($ini == 0)
        return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

function parse_last($miben, $miutan)
{
    return array_pop(explode($miutan, $miben));
}
function server_uptime()
{
    $str       = @file_get_contents('/proc/uptime');
    $num       = floatval($str);
    $secs      = fmod($num, 60);
    $num       = (int) ($num / 60);
    $mins      = $num % 60;
    $num       = (int) ($num / 60);
    $hours     = $num % 24;
    $num       = (int) ($num / 24);
    $days      = $num;
    $formatted = $days . "n " . $hours . "o " . $mins . "p ";
    return $formatted;
}
function server_load()
{
    $load    = sys_getloadavg();
    $loadstr = $load[0] . " " . $load[1] . " " . $load[2];
    return $loadstr;
}
function db_query()
{
    $db     = new SQLite3('/var/www/sqlite/irclog.db');
    $stmt   = $db->prepare('SELECT COUNT(*) FROM irclog');
    $result = $stmt->execute();
    $mennyi;
    while ($row = $result->fetchArray()) {
        $mennyi = $row[0];
    }
    return $mennyi;
}
// NEW BETA FUNCTION
function db_query_alter($query)
{
    $db       = new SQLite3('/var/www/sqlite/irclog.db');
    $stmt     = $db->prepare($query);
    $result   = $stmt->execute();
    $eredmeny = $result;
    //global $toPaste;
    $toPaste  = "";
    $i        = 0;
    while ($res = $eredmeny->fetchArray(SQLITE3_ASSOC)) {
        $row[$i]['whattime'] = $res['whattime'];
        $row[$i]['fromuser'] = $res['fromuser'];
        $row[$i]['touser']   = $res['touser'];
        $row[$i]['msg']      = $res['msg'];
        $whattime            = $row[$i]['whattime'];
        $fromuser            = $row[$i]['fromuser'];
        $touser              = $row[$i]['touser'];
        $msg                 = $row[$i]['msg'];
        $i++;
        $log = $whattime . " -> " . $fromuser . " -> " . $touser . " -> " . $msg . "\n";
        
        //global $sock;
        global $toPaste;
        
        $toPaste = $toPaste . $log;
    }
    
    $pasteLink = pastebin($toPaste);
    return $pasteLink;
    
    //return $eredmeny;
}
function byteConvert($bytes)
{
    $kilo = $bytes / 1024;
    $mega = $bytes / 1024 / 1024;
    $mennyi;
    $mertek;
    if ($kilo > "1") {
        $mennyi = $bytes / 1024;
        $mertek = "KB";
    }
    if ($mega > "1") {
        $mennyi = (($bytes / 1024) / 1024);
        $mertek = "MB";
    }
    return round($mennyi, 3) . " " . $mertek;
}

function REGISTER($fromuser, $touser, $msg, $whattime)
{
    $db   = new SQLite3('/var/www/sqlite/irclog.db');
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
}

function pastebin($szoveg)
{
    $api_dev_key           = 'APIKEY'; // your api_developer_key
    $api_paste_code        = $szoveg; // your paste text
    $api_paste_private     = '0'; // 0=public 1=unlisted 2=private
    $api_paste_name        = 'Tauri IRC History'; // name or title of your paste
    $api_paste_expire_date = '10M';
    $api_paste_format      = 'php';
    $api_user_key          = '';
    $api_paste_name        = urlencode($api_paste_name);
    $api_paste_code        = urlencode($api_paste_code);
    $url                   = 'https://pastebin.com/api/api_post.php';
    $ch                    = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'api_option=paste&api_user_key=' . $api_user_key . '&api_paste_private=' . $api_paste_private . '&api_paste_name=' . $api_paste_name . '&api_paste_expire_date=' . $api_paste_expire_date . '&api_paste_format=' . $api_paste_format . '&api_dev_key=' . $api_dev_key . '&api_paste_code=' . $api_paste_code . '');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_NOBODY, 0);
    $response = curl_exec($ch);
    return $response;
}

function getlast($kinek, $mennyit, $hovaMent)
{
    
    $db = new SQLite3('/var/www/sqlite/irclog.db');
    
    $stmt = $db->prepare('select whattime, fromuser, touser, msg from (select * from irclog order by id desc limit (?)) irclog order by id asc;');
    $stmt->bindValue(1, $mennyit, SQLITE3_TEXT);
    $result = $stmt->execute();
    $i      = 0;
    global $toPaste;
    $toPaste = "";
    while ($res = $result->fetchArray(SQLITE3_ASSOC)) {
        $row[$i]['whattime'] = $res['whattime'];
        $row[$i]['fromuser'] = $res['fromuser'];
        $row[$i]['touser']   = $res['touser'];
        $row[$i]['msg']      = $res['msg'];
        $whattime            = $row[$i]['whattime'];
        $fromuser            = $row[$i]['fromuser'];
        $touser              = $row[$i]['touser'];
        $msg                 = $row[$i]['msg'];
        $i++;
        $log = $whattime . " -> " . $fromuser . " -> " . $touser . " -> " . $msg . "\n";
        
        //global $sock;
        global $toPaste;
        
        $toPaste = $toPaste . $log;
    }
    
    $pasteLink = pastebin($toPaste);
    return $pasteLink;
    
}

// Restart script
passthru('/usr/bin/php /var/www/snippets/ircbot.php');
?>
