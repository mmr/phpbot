<?
// $Id: irc.inc.php,v 1.25 2003/08/21 18:14:46 mmr Exp $

//
// phpbot --> data --> IRC --> socket
//              `----> sqllink
//              `----> count
//

// Classe de comunicacao com a Camada IRC
class bot_irc extends bot_socket
{
  var $data   = NULL;
  var $socket = NULL;

  // Conecta ao Servidor IRC
  function ircConnect($server, $port)
  {
    return $this->sockConnect($server, $port);
  }

  // Identifica o usuario com o Servidor IRC
  function ircIdentify($user = bot_IRCUSER, 
                       $name = bot_IRCNAME,
                       $nick = bot_IRCNICK,
                       $pass = bot_IRCPASS)
  {
    return
      $this->sockWrite('USER ' . $user . ' 8 * :' . $name) &&
      $this->sockWrite('NICK ' . $nick) &&
      $this->sockWrite('NICKSERV IDENTIFY ' . $pass);
  }

  // Entra em um canal
  function ircJoinChannel($channel)
  {
    return $this->sockWrite('JOIN #' . $channel);
  }

  // Sai de um canal
  function ircPartChannel($channel)
  {
    return $this->sockWrite('PART #' . $channel);
  }

  // Sai do IRC 
  function ircQuit($msg)
  {
    $msg = trim($msg);

    if(empty($msg))
    {
      $msg = bot_QUITMSG;
    }

    $this->sockWrite('QUIT :' . $msg);
    $this->ircClose();
  }

  function ircClose()
  {
    return $this->sockClose();
  }

  // Manda mensagem para um canal
  function ircMessageChannel($channel, $msg='')
  {
    return $this->sockWrite('PRIVMSG ' . $channel . ' :' . $msg);
  }

  // Manda mensagem para uma pessoa
  function ircMessagePerson($person, $msg='')
  {
    return $this->sockWrite('PRIVMSG ' . $person . ' :' . $msg);
  }

  // Responde a Ping do Servidor
  function ircPingReply($server)
  {
    return $this->sockWrite('PONG ' . $server);
  }

  // CTCP (Client To Client Protocol)
    // Abstracao de resposta CTCP
  function ircCtcpReply($nick, $reply)
  {
    return $this->sockWrite('NOTICE ' . $nick . ' :' . bot_CTCP_CHR . $reply . bot_CTCP_CHR);
  }

    // Responde a CTCP Action
  function ircCtcpReplyAction($nick, $channel, $action)
  {
    global $bot_ACTIONSEARCH;

    if(in_array($action, $bot_ACTIONSEARCH))
    {
      global $bot_ACTIONMSGS;

      srand((float) microtime() * 10000000);
      $randmsg = str_replace('%n', $nick, $bot_ACTIONMSGS[rand(0, count($bot_ACTIONMSGS) - 1)]);

      return $this->sockWrite('PRIVMSG ' . $channel . ' :' . bot_CTCP_CHR . 'ACTION ' . $randmsg . bot_CTCP_CHR);
    }
  }

    // Responde a CTCP Clientinfo
  function ircCtcpReplyClientinfo($nick, $arg = '')
  {
    if(empty($arg))
    {
      $msg = 'CLIENTINFO You can request help of the commands CLIENTINFO ERRMSG FINGER PING USERINFO VERSION by giving an argument to CLIENTINFO.';
    }
    else
    {
      $arg = trim(strtoupper($arg));
      switch($arg)
      {
      case 'CLIENTINFO':
        $msg = 'CLIENTINFO CLIENTINFO with 0 arguments gives a list of known client query keywords. With 1 argument, a description of the client query keyword is returned';
        break;
      case 'ERRMSG':
        $msg = 'CLIENTINFO ERRMSG returns error messages';
        break;
      case 'FINGER':
        $msg = 'CLIENTINFO FINGER shows real name, login name and iddle time of user';
        break;
      case 'PING':
        $msg = 'CLIENTINFO PING returns the arguments it receives';
        break;
      case 'USERINFO':
        $msg = 'CLIENTINFO USERINFO returns user settable information';
        break;
      case 'VERSION':
        $msg = 'CLIENTINFO VERSION shows client type, version and environment';
        break;
      default:
        $msg = 'ERRMSG CLIENTINFO: ' . $arg . ' is not a valid command.';
        break;
      }
    }

    return $this->ircCtcpReply($nick, $msg);
  }

    // Responde a CTCP Finger
  function ircCtcpReplyFinger($nick)
  {
    return $this->ircCtcpReply($nick, 'FINGER ' . bot_NAME . ' - ' . bot_IRCNICK . ' (' . bot_NAME . '@' . str_replace('\n', '', `hostname`) . ')');
  }

    // Responde a CTCP Ping
  function ircCtcpReplyPing($nick, $time)
  {
    return $this->ircCtcpReply($nick, 'PING ' . $time);
  }

    // Responde a CTCP Time
  function ircCtcpReplyTime($nick)
  {
    return $this->ircCtcpReply($nick, 'TIME ' . date('D M d H:i:s T Y'));
  }

    // Responde a CTCP Userinfo
  function ircCtcpReplyUserinfo($nick)
  {
    $this->ircCtcpReply($nick, 'USERINFO ' . bot_DESC);
  }

    // Responde a CTCP Version
  function ircCtcpReplyVersion($nick)
  {
    $this->ircCtcpReply($nick, 'VERSION ' . bot_NAME . bot_VERSION . ' By ' . bot_AUTHORS . ' - ' . bot_ENV);
  }
}
?>
