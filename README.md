# PHP IRC CHATBOT
Simple chatbot for IRC written in PHP

## Demo: ##
[Tauri IRCSeeker](https://sont.sytes.net/ircseeker.php/)

## Basic config:

### IRC Server address
```
$server = "irc.example.com";
$port   = "6667";
```

### Nickname of the bot
```
socket_write($sock, "NICK JOHN \r\n");
socket_write($sock, "USER JOHN JOHN JOHN :john\r\n");
socket_write($sock, "JOIN #CHANNEL\r\n");
```

### Database login credentials:
```
$servername = "localhost";
$username   = "root";
$password   = "PASSWORD";
$dbname     = "DATABASE";
```
