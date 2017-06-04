<?
// $Id: botconfig.inc.php,v 1.53 2003/09/14 03:09:32 mmr Exp $

// Constantes
  // Configuracoes gerais
define('bot_NAME',      'PHPBot');
define('bot_VERSION',   '1.7.1');
define('bot_AUTHORS',   'mmr <mmr@b1n.org>');
define('bot_ENV',       php_uname());
define('bot_DESC',      'IRCBot written in PHP by ' . bot_AUTHORS . '.');
define('bot_HOMEPAGE',  'http://source.b1n.org/source/phpbot/');

  // Configuracoes do IRC 
define('bot_IRCNICK',   'DebugIsOnTheTable');
define('bot_IRCNAME',   bot_HOMEPAGE);
define('bot_IRCUSER',   'phpbot');
define('bot_IRCPASS',   'rasputim22');
define('bot_CTCP_CHR',  chr(1));
define('bot_BOLD_CHR',  chr(2));

  // Retornos para a Shell
define('bot_RET_OK',  0);
define('bot_RET_ERR', 1);

  // Niveis para o ErrorReporting
define('bot_ERR_NOTICE',  0);
define('bot_ERR_WARN',    1);
define('bot_ERR_ERROR',   2);

  // Valores padrao (Default)
define('bot_DEFAULT_LOGFILE', '/tmp/phpbot.log');
define('bot_DEFAULT_PIDFILE', '/tmp/phpbot.pid');
define('bot_DEFAULT_DEBUG',   0);

  // Niveis de Debug
  // 0 - Desligado
  // 1 - Mostra o que o bot Le
  // 2 - Mostra o que o bot Escreve
  // 4 - Mostra Queries SQL
define('bot_DEBUG_OFF',   0);
define('bot_DEBUG_READ',  1);
define('bot_DEBUG_WRITE', 2);
define('bot_DEBUG_SQL',   4);

  // Configuracoes da Busca
define('bot_WEBSEARCH_URL', '/manual/pt_BR/function.%f.php');
define('bot_MIN_SEARCH',    3);   // Busca minima (numero de caracteres)
define('bot_MAX_SQL_LIMIT', 3);   // Maximo de retornos pela query de busca

  // Misc
define('bot_PID',     getmypid());
define('bot_BUFSIZ',  513); // 512 + '\0' = 513 bytes
define('bot_QUITMSG', 'See ya later, Alligator...');
define('b1n_HOSTINGS', 'www.barrysworld.com * www.coolfreepages.com * www.dk3.com * www.f2g.net * www.uklinux.net * www.portland.co.uk * www.freewebspace.net');

// Variaveis Globais (arrays) - Somente para Leitura
  // Administradores do Bot
$bot_ADMINS = array('mmr', 'Narcotic', 'l0k1', 'OGangrel');

  // Buscas
    // Servidores para busca na Web
$bot_WEBSEARCH_SERVERS = array('www.php.net', 'br.php.net');
    // O bot respondera a Actions contendo essas strings
$bot_ACTIONSEARCH = array('mp3', 'ogg', 'away', 'back');

  // IRC
    // Servidores IRC a tentar conectar
$bot_IRCSERVERS = array('sp.brasnet.org', 
                        'irc.brasnet.org',
                        'irc.pelotas.org',
                        'irc.dialdata.com.br',
                        'irc.brturbo.com.br');
    // Canais a entrar
      // ATENCAO: O nome dos canais deve ser escrito em letra minuscula.
$bot_CHANNELS = array('php');
    // Msgs
      // Mensagens randomicas a falar quando o nick do bot for citado
      // %n sera expandido para o nick de quem fez a citacao
$bot_MSGS = array('Que voce quer comigo, %n?',
                  '%n, vai encher outro.',
                  '%n, tou ocupado agora, na boa...',
                  '%n ???',
                  '%n php.net');

      // Mensagens randomicas para Reply do Action
$bot_ACTIONMSGS = array('Pensa: Olha que nick tosco, %n...',
                        'Constrangido: %n, e...? ',
                        'Responde: Cada um com seus problemas %n',
                        'Pergunta: %n, c nao tem o que fazer nao?');


?>
