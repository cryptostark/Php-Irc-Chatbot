# PHP IRC CHATBOT
Simple chatbot for IRC written in PHP

## Basic config:

### IRC Server address
```
$server = "";
$port   = "";
```

### Nickname of the bot
```
socket_write($sock, "NICK BOTNAME \r\n");
socket_write($sock, "USER BOTNAME BOTNAME BOTNAME :GRP\r\n");
socket_write($sock, "JOIN #CHANNEL\r\n");
```

### Database login credentials:
```
$servername = "localhost";
$username   = "root";
$password   = "PASSWORD";
$dbname     = "DATABASE";
```
