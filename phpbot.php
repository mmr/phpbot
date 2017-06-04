<?
/*
 * $Id: phpbot.php,v 1.60 2004/12/16 01:56:47 mmr Exp $
 *
 * IRC Bot written in PHP Language By 
 * mmr <mmr@b1n.org>
 *
 * Copyright (c) 2003 b1n.org
 * All rights reserved.
 *
 * Redistribution of this program, with or without modification
 * are permitted provided that the following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT HOLDERS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 */

//
// PHPBOT --> data --> irc --> socket
//              `----> sqllink
//              `----> count
//

define('bot_INCPATH', 'include');

// Includes
require(bot_INCPATH . '/botconfig.inc.php');// IRCBot Configuration
require(bot_INCPATH . '/socket.inc.php');   // Socket Creation/Manipulation Class
require(bot_INCPATH . '/irc.inc.php');      // IRCCommands Class
require(bot_INCPATH . '/data.inc.php');     // Data (Count, Search) Class
require(bot_INCPATH . '/count.inc.php');    // Data (Count, Search) Class
require(bot_INCPATH . '/sqllink.inc.php');  // Database Connection Class

// Classe Principal
class bot_phpBot extends bot_data
{
  var $userlist = array(); // Users List

  // Construtor
  function bot_phpBot()
  {
    $this->bot_data();
  }

  // Conecta ao Servidor IRC
  function botConnect($server = '', $port = 6667)
  {
    return $this->ircConnect($server, $port);
  }

  // Identifica o usuario com o Servidor IRC
  function botIdentify()
  {
    return $this->ircIdentify();
  }

  // Entra nos Canais
  function botJoinChannels()
  {
    global $bot_CHANNELS;

    $ret = true;
    foreach($bot_CHANNELS as $chan)
    {
      $this->userlist[$chan] = '';
      $ret = $ret && $this->ircJoinChannel($chan);
    }

    return $ret;
  }

  // Checa pelos comandos do protocolo IRC
  function botCheckCommand($buffer)
  {
    if(ereg('^ERROR :Closing Link: ' . bot_IRCNICK, $buffer))
    {
      if($this->dataCountClose() && $this->ircClose())
      {
        if(!$this->botRun())
        {
          bot_errorLog('Could not Reconnect.', bot_ERR_ERROR);
          exit();
        }
      }
      else
      {
        bot_errorLog('Could not Close.', bot_ERR_ERROR);
        exit();
      }
    }
    else
    {
      return $this->dataCheckCommand($buffer);
    }
  }

  // Atualiza Dados de busca do Bot
  function botUpdate()
  {
    return $this->dataUpdateDb();
  }

  // Iniciando Bot
  function botRun()
  {
    if($this->botConnect('irc.gbi.com.br')  && 
       $this->botIdentify() && 
       $this->botJoinChannels())
    {
      while(!feof($this->socket))
      {
        $buffer = $this->sockRead();
        $this->botCheckCommand($buffer);
      }
    }
    else
    {
      return false;
    }
  }

  // Uso do Bot
  function botUsage()
  {
    echo bot_NAME . " " . bot_VERSION . "\n" .  bot_DESC .
         "\nOfficial Homepage: " . bot_HOMEPAGE .
         "\n" . str_repeat('-', 40) .
         "\nUsage:" . 
         "\n\t" . bot_PROGNAME . " -r [-d debuglevel] [-l file] [-p pidfile]" .
         "\n\t" . bot_PROGNAME . " -u [-d debuglevel] [-l file]" .
         "\n\t" . bot_PROGNAME . " -s [-p pidfile]" .
         "\n\n\t--update\n\t-u\tUpdate Bot data from Web" .
         "\n\n\t--run\n\t-r\tRun the Bot" .
         "\n\n\t--stop\n\t-s\tStop the Bot" .
         "\n\n\t--debuglevel\n\t-d\tDebug Level [default: " . bot_DEFAULT_DEBUG . "]" .
         "\n\t\t0 - Turned off" .
         "\n\t\t1 - What the bot reads" .
         "\n\t\t2 - What the bot writes" .
         "\n\t\t4 - SQL Queries" .
         "\n\t\tObs: 3 = Read + Write, 5 = SQL + Read and so forthy" .
         "\n\n\t--logfile\n\t-l\tLog file  [default: " . bot_DEFAULT_LOGFILE  . "]" .
         "\n\n\t--pidfile\n\t-p\tPID file  [default: " . bot_DEFAULT_PIDFILE  . "]\n";

    return bot_RET_ERR;
  }

  // Para o Bot
  function botStop()
  {
    clearstatcache();
    if(is_readable(bot_PIDFILE)){
      $fp = fopen(bot_PIDFILE, 'r');
      $pid = fread($fp, bot_BUFSIZ);
      fclose($fp);
    }
    else
    {
      echo "Nao pode ler PID File (" . bot_PIDFILE . ").\n";
      exit(bot_RET_ERR);
    }

    #  1) SIGHUP	 2) SIGINT	   3) SIGQUIT	 4) SIGILL
    #  5) SIGTRAP	 6) SIGABRT	   7) SIGEMT	 8) SIGFPE
    #  9) SIGKILL	10) SIGBUS	  11) SIGSEGV	12) SIGSYS
    # 13) SIGPIPE	14) SIGALRM	  15) SIGTERM	16) SIGURG
    # 17) SIGSTOP	18) SIGTSTP	  19) SIGCONT	20) SIGCHLD
    # 21) SIGTTIN	22) SIGTTOU	  23) SIGIO	  24) SIGXCPU
    # 25) SIGXFSZ	26) SIGVTALRM	27) SIGPROF	28) SIGWINCH
    # 29) SIGINFO	30) SIGUSR1	  31) SIGUSR2	
    return posix_kill($pid, 15);
  }
}

// Metodo de classe estatica
// (ie. visivel para todas classes)
function bot_errorLog($str, $lvl)
{
  /*
  switch($lvl)
  {
    case bot_ERR_NOTICE:
      $str = "Notice: " . $str;
      break;
    case bot_ERR_WARN:
      $str = "Warning: " . $str;
      break;
    default:
      $str = "nERROR: " . $str;
  }
  */

  error_log($str, 3, bot_LOGFILE);
}

// Tratando argumentos da Shell
$args = $_SERVER['argv'];
$action   = '';
$debuglvl = bot_DEFAULT_DEBUG;
$logfile  = bot_DEFAULT_LOGFILE;
$pidfile  = bot_DEFAULT_PIDFILE;
define('bot_PROGNAME', basename(array_shift($args)));

  // Se a quantidade de argumentos for menor do que a esperada
  // Mostrar Usage e sair
if($_SERVER['argc'] < 2)
{
  $bot = New bot_phpBot();
  exit($bot->botUsage());
}
  
  // Testando argumentos
while($arg = array_shift($args))
{
  switch($arg)
  {
  case '-u':
  case '--update':
    if(empty($action)){
      $action = 'run';
    }
    else {
      $action = 'usage';
      break(2);
    }
    break;
  case '-r':
  case '--run':
    if(empty($action)){
      $action = 'run';
    }
    else {
      $action = 'usage';
      break(2);
    }
    break;
  case '-s':
  case '--stop':
    if(empty($action)){
      $action = 'stop';
    }
    else {
      $action = 'usage';
      break(2);
    }
    break;
  case '-d':
  case '--debuglevel':
    $debuglvl = array_shift($args);
    if(($debuglvl !== 0 && empty($debuglvl)) ||
      !is_numeric($debuglvl))
    {
      $action = 'usage';
      break(2);
    }
    break;
  case '-l':
  case '--logfile':
    $logfile = array_shift($args);
    if(empty($logfile)){
      $action = 'usage';
      break(2);
    }

    clearstatcache();
    if(!is_writable(dirname($logfile)) ||
      (file_exists($logfile) && !is_writable($logfile)))
    {
      echo "Nao pode gravar LogFile (" . $logfile . ").\n";
      exit(bot_RET_ERR);
    }
    break;
  case '-p':
  case '--pidfile':
    $pidfile = array_shift($args);
    if(empty($pidfile)){
      $action = 'usage';
      break(2);
    }

    clearstatcache();
    if(!is_writable(dirname($pidfile)) ||
      (file_exists($pidfile) && !is_writable($pidfile)))
    {
      echo "Nao pode gravar PIDFile (" . $pidfile . ").\n";
      exit(bot_RET_ERR);
    }
    break;
  default:
    $action = 'usage';
    break(2);
  }
}

// Constantes dinamicas (estranho? eh, um pouco)
define('bot_DEBUG_LEVEL', $debuglvl);
define('bot_LOGFILE',     $logfile);
define('bot_PIDFILE',     $pidfile);
unset($debuglvl, $logfile, $pidfile);

if($action == 'run'){
  clearstatcache();
  if(is_writable(dirname(bot_PIDFILE)) &&
    (is_writable(bot_PIDFILE) || !file_exists(bot_PIDFILE)))
  {
    $fp = fopen(bot_PIDFILE, 'w');
    fputs($fp, bot_PID, strlen(bot_PID));
    fclose($fp);
  }
  else {
    echo "Nao pode gravar PID File (" . bot_PIDFILE . ").\n";
    exit(bot_RET_ERR);
  }
}

$bot = New bot_phpBot();

switch($action)
{
  case 'run':
    exit($bot->botRun());
    break;
  case 'update':
    exit($bot->botUpdate());
    break;
  case 'stop':
    if($bot->botStop())
    {
      if(is_writable(bot_LOGFILE))
      {
        unlink(bot_LOGFILE);
      }
      if(is_writable(bot_PIDFILE))
      {
        unlink(bot_PIDFILE);
      }
      exit(bot_RET_OK);
    }
    else
    {
      exit(bot_RET_ERR);
    }
    break;
  default:
    exit($bot->botUsage());
}
?>
