<?
// $Id: socket.inc.php,v 1.18 2003/09/02 17:25:38 mmr Exp $

//
// phpbot --> data --> irc --> SOCKET
//              `----> sqllink
//              `----> count
//

class bot_socket
{
  var $socket = NULL;

  // Conecta ao servidor IRC
  function sockConnect($server, $port)
  {
    global $bot_IRCSERVERS;

    if($this->sockIsConnected())
    { 
      bot_errorLog('Already connected.', bot_ERR_ERROR);
      return false; 
    }

    if(empty($server))
    {
      $servers = $bot_IRCSERVERS;
      srand((float) microtime() * 1000000);
      shuffle($servers);
    }
    else
    {
      $servers = array($server);
    }

    while(!$this->socket && count($servers))
    {
      $server_name = array_shift($servers);
      $server_ip   = gethostbyname($server_name);
      $this->sockSetSocket(fsockopen($server_ip, $port, $errno, $err));
    }

    if($this->sockGetSocket())
    {
      return true;
    }

    bot_errorLog($errno . ' - ' . $err, bot_ERR_ERROR);
    return false; 
  }

  // Verifica se ja esta conectado
  function sockIsConnected()
  {
    return $this->sockGetSocket();
  }

  // Escreve no Socket de comunicacao com o servidor IRC
  function sockWrite($str)
  {
    if(bot_DEBUG_LEVEL & bot_DEBUG_WRITE)
    {
      bot_errorLog($str, bot_ERR_NOTICE);
    }

    # + 2 devido ao \r\n
    return fputs($this->socket, $str . "\r\n", (strlen($str) + 2));
  }

  // Le do socket e grava em buffer
  function sockRead()
  {
    $buf = fgets($this->socket, bot_BUFSIZ);

    if(bot_DEBUG_LEVEL & bot_DEBUG_READ)
    {
      bot_errorLog($buf, bot_ERR_NOTICE);
    }

    return $buf;
  }

  // Fecha socket de comunicacao
  function sockClose()
  {
    if($this->sockIsConnected())
    {
      fclose($this->sockGetSocket());
      $this->sockSetSocket('');
      return true;
    }
    return false;
  }

  function sockGetSocket()
  {
    return $this->socket;
  }

  function sockSetSocket($sock)
  {
    return $this->socket = $sock;
  }
}
?>
