<?
// $Id: data.inc.php,v 1.38 2003/09/02 17:25:38 mmr Exp $

//
// phpbot --> DATA --> irc --> socket
//              `----> sqllink
//              `----> count
//

class bot_data extends bot_irc
{
  var $sqllink  = NULL;
  var $count    = NULL;
  var $userlist = array();

  function bot_data()
  {
    $this->sqllink = New bot_sqllink();
    $this->count   = New bot_count($this->sqllink);
  }

  function dataSearch($search, $nick)
  {
    if(strlen($search) < bot_MIN_SEARCH)
    {
      $this->ircMessagePerson($nick, 'Busca muito pequena, precisa ter, no minimo, ' . bot_MIN_SEARCH . ' caracteres.');
    }
    elseif(!ereg('^[[:alpha:]_][[:alnum:]_]+$', $search))
    {
      $this->ircMessagePerson($nick, '"' . $search . '" nao eh uma busca valida.');
    }
    elseif(($ret = $this->dataSearchDb($search, $nick)))
    {
      $this->dataShowResults($ret, $nick);
    }
    elseif(($ret = $this->dataSearchWeb($search)))
    {
      if($this->dataInsertWebSearchIntoDb($ret))
      {
        $this->dataShowResults($ret, $nick);
      }
      else
      {
        bot_errorLog('Could not insert result into DB.', bot_ERR_ERROR);
      }
    }
    else
    {
      $this->ircMessagePerson($nick, 'Nao encontrei nada com "' . $search . '".');
    }
  }

  // Procura no Banco de Dados pela Funcao PHP
  function dataSearchDb($search, $nick)
  {
    $query = "
      SELECT
        COUNT(cmd_id) AS count
      FROM
        command
      WHERE
        cmd_name LIKE '%" . $search . "%'";

    $ret = $this->sqllink->sqlSingleQuery($query);

    if($ret['count'] > bot_MAX_SQL_LIMIT)
    {
      $this->ircMessagePerson($nick,
        $ret['count'] . ' resultados foram encontrados para sua busca por "' . $search . '".' .
        'Somente os ' . bot_MAX_SQL_LIMIT . ' primeiros resultados serao mostrados, seja mais especifico.');
    }
    elseif($ret['count'] == 0)
    {
      return false;
    }

    $query = "
      SELECT
        cmd_name, cmd_desc, cmd_syntax
      FROM
        command
      WHERE
        cmd_name LIKE '%" . $search . "%'
      ORDER BY
        MATCH(cmd_name) AGAINST('" . $search . "') DESC
      LIMIT " . bot_MAX_SQL_LIMIT;

    return $this->sqllink->sqlQuery($query);
  }

  // Procura na Web
  function dataSearchWeb($search)
  {
    global $bot_WEBSEARCH_SERVERS;
    $servers = $bot_WEBSEARCH_SERVERS;
    $fp  = NULL;

    while(!$fp && count($servers))
    {
      $server_name = array_shift($servers);
      $server_ip   = gethostbyname($server_name);
      $fp = fsockopen($server_ip, 80);
    }

    if(!$fp)
    {
      return false;
    }

    $aux = str_replace('_', '-', $search);
    $url = str_replace('%f', $aux, bot_WEBSEARCH_URL);

    fputs($fp, "GET "   . $url . " HTTP/1.1\r\nHost: " . $server_name . "\r\n\r\n");

    $ret = '';
    while(!feof($fp))
    {
      $ret .= fread($fp, 1024);
    }

    // Pegando dados que importam
    $ini_pos = strpos($ret, '</P');
    $end_pos = strpos($ret, '<BR');

    $ret = substr($ret, $ini_pos, $end_pos - $ini_pos);
    $ret = strip_tags($ret, '<p><h2>');

    if(ereg("</P\n>([^&]+)&nbsp;--&nbsp;([^<]+).*</H2\n>([^[:space:]]+).*(\([^)]+\))", $ret, $match))
    {
      unset($ret);

      array_shift($match);
      $ret[0]['cmd_name']   = $this->dataFormatData($match[0]);
      $ret[0]['cmd_desc']   = $this->dataFormatData($match[1]);
      $ret[0]['cmd_syntax'] = $this->dataFormatData($match[0] . ' ' . $match[3]);

      if($match[2] != $match[0])
      {
        $ret[0]['cmd_syntax'] = $this->dataFormatData($match[2] . ' ' . $ret[0]['cmd_syntax']);
      }

      unset($match);
      fclose($fp);


      return $ret;
    }

    return false;
  }

  // Mostra os Resultados encontrados
  function dataShowResults($ret, $nick)
  {
    if(is_array($ret))
    {
      foreach($ret as $r)
      {
        $this->ircMessagePerson($nick, bot_BOLD_CHR . $r['cmd_name'] . ': ' . bot_BOLD_CHR . $r['cmd_desc'] . ' / ' . bot_BOLD_CHR . 'Syntax: ' . bot_BOLD_CHR . $r['cmd_syntax']);
      }
    }
  }

  // Formata os dados (Tira espacos sobrantes)
  function dataFormatData($str)
  {
    return trim(str_replace("\n", ' ', ereg_replace('[[:space:]]{2,}', ' ', $str)));
  }

  // Formata os dados antes de Colocar no Banco de Dados (ie. Escapeia/AddSlashes)
  function dataFormatDataToDb($str)
  {
    return addslashes($str);
  }

  // Insere o resultado da Busca pela Web no Banco de Dados
  // TODO: Fazer checagem (problemas de concorrencia).
  function dataInsertWebSearchIntoDb($ret)
  {
    if(is_array($ret))
    {
      $ret = $ret[0];
      if(isset($ret['cmd_name']) && isset($ret['cmd_desc']) && isset($ret['cmd_syntax']))
      {
        $query = "
          INSERT INTO command
          (
            cmd_name,
            cmd_desc,
            cmd_syntax
          )
          VALUES
          (
            '" . $this->dataFormatDataToDb($ret['cmd_name'])   . "',
            '" . $this->dataFormatDataToDb($ret['cmd_desc'])   . "',
            '" . $this->dataFormatDataToDb($ret['cmd_syntax']) . "'
          )";

        return $this->sqllink->sqlQuery($query);
      }
    }

    return false;
  }

  // Atualiza Funcoes do Banco de Dados
  // Comparando com as da Web (assumindo que as da Web sao mais recentes)
  function dataUpdateDb()
  {
    $query = 'SELECT cmd_id, cmd_name FROM command';

    $db_cmd = $this->sqllink->query($query);

    $fields = array('cmd_name', 'cmd_desc', 'cmd_syntax');

    foreach($ret as $r)
    {
      if(($web_cmd = $this->dataSearchWeb($r['cmd_name'])) && is_array($web_cmd))
      {
        $update_fields = array();

        foreach($fields as $f)
        {
          if(!empty($web_cmd[$f]) && $db_cmd[$f] != $web_cmd[$f])
          {
            $update_fields[] = $f . " = '" . addslashes(trim($web_cmd[$f])) . "'";
          }
        }

        if(count($update_fields))
        {
          $query = "UPDATE command SET " . implode(", ", $update_fields) . " WHERE cmd_id = '" . $r['cmd_id'] . "'";

          if(!$this->sqllink->query($query))
          {
            bot_errorLog('Could not update command(' . $r['cmd_id'] . ')', bot_ERR_ERROR);
          }
        }
      }
    }

    return $this->sqllink->sqlCloseLink();
  }

  // Procura por comandos IRC no $buffer
  function dataCheckCommand($buffer)
  {
    // Comandos mandados pelo servidor
    if($buffer[0] != ':')
    {
      $server_command = strtok($buffer, ' ');
       
      switch($server_command)
      {
      case 'PING':
        // Responde ao ping
        $server = substr($buffer, 5);
        $this->ircPingReply($server);
        break;
      }

      return;
    }

    // Interpreta protocolo IRC, separando itens que queremos
    // TODO Rever ER
    if(ereg("^:([^!]+)!([^@]+)@([^[:space:]]+)[[:space:]]([^[:space:]]+)[[:space:]]([^[:space:]]+)[[:space:]]:?([^\r]+)", $buffer, $match))
    {
      // $match[0] eh o match completo (&), ie. a linha toda, ignorar
      list(,$nick,$ident,$host,$server_command,$par,$msg) = $match;
      $nick = str_replace(':', '', $nick);
      $par  = str_replace(':', '', $par);
      $par  = str_replace('#', '', $par);
    }
    else
    {
      // Outras coisas (353, 366, ...)
      if(ereg("^:[^[:space:]]+[[:space:]]([23][0-9]{2})[[:space:]][^[:space:]]+[[:space:]]=?[[:space:]]?#([^[:space:]]+)[[:space:]]:?([^\r]+)", $buffer, $match))
      {
        list(,$server_command, $chan, $msg) = $match; 
        unset($match);
        $chan = strtolower($chan);
      }
      else
      {
        return;
      }
    }

    switch($server_command)
    {
    case '353': // 353 -> RPL_NAMREPLY
      $this->userlist[$chan] .= ' ' . $msg;
      break;
    case '366': // 366 -> RPL_ENDOFNAMES
      $list = $this->userlist[$chan];
      unset($this->userlist[$chan]);
 
      $list = ereg_replace('^[[:space:]]+|[[:space:]]+$|\+|@', '', $list);
      $list = ereg_replace('[[:space:]]+', ' ', $list);
      $list = explode(' ', $list);
 
      foreach($list as $user)
      {
        if($user != bot_IRCNICK && !ereg('^Guest[0-9]+$', $user))
        {
          $this->count->countIncJoin($user, $chan);
        }
      }
      break;
    case 'PRIVMSG':
      // Testando se eh um CTCP
      if(ereg('^' . bot_CTCP_CHR . '([A-Z]+)[[:space:]]?(.*)' . bot_CTCP_CHR . '$', $msg, $match))
      {
        list(,$ctcp_command,$ctcp_par) = $match;
        switch($ctcp_command)
        {
        case 'FINGER':
          $this->ircCtcpReplyFinger($nick);
          break;
        case 'TIME':
          $this->ircCtcpReplyTime($nick);
          break;
        case 'USERINFO':
          $this->ircCtcpReplyUserinfo($nick);
          break;
        case 'VERSION':
          $this->ircCtcpReplyVersion($nick);
          break;
        case 'CLIENTINFO':
          $this->ircCtcpReplyClientinfo($nick, $ctcp_par);
          break;
        case 'PING':
          $this->ircCtcpReplyPing($nick, $ctcp_par);
          break;
        case 'ACTION':
          $chan = $par;
          #$this->ircCtcpReplyAction($nick, $chan, $ctcp_par);
          $this->count->countIncAction($nick, $chan);
          break;
        default:
          // CTCP Invalido
          $this->ircCtcpReply($nick, 'ERRMSG ' . $arg . ' is not a valid command. See CLIENTINFO for valid CTCP commands.');
        }
      }
      // Testando se eh um uso ao Bot
      elseif(ereg('^[[:space:]]*!', $msg))
      {
        // Pegando o comando (bot_command) e o parametro (bot_par)
        if(!ereg('^[[:space:]]*![[:space:]]*([a-z]+)[[:space:]]*(.*)$', $msg, $match))
        {
          $this->ircMessagePerson($nick, 'Comandos Validos: !php busca, !host');
          break;
        }

        list(,$bot_command,$bot_par) = $match;

        // Testando comandos
        switch($bot_command)
        {
          case 'php':
            $chan = $par;
            $search = $bot_par;
            $this->dataSearch($search, $nick);
            $this->count->countIncBotuse($nick, $chan);
            break;
          case 'host':
            $chan = $par;
            $this->ircMessagePerson($nick, b1n_HOSTINGS);
            $this->count->countIncBotuse($nick, $chan);
            break;
          default:
            $this->ircMessagePerson($nick, 'Comandos Validos: !php busca, !host');
        }
      }
      else
      {
        $chan = $par;
        $this->count->countIncMsg($nick, $chan);
      }
      break;
    case 'NICK':
      $oldnick = $nick;
      $nick = $par;

      $nick = $this->dataFormatDataToDb($nick);
      $oldnick = $this->dataFormatDataToDb($oldnick);
 
      $this->count->countNickChange($oldnick, $nick);
      break;
    case 'JOIN':
      $chan = $par;

      $nick = $this->dataFormatDataToDb($nick);
      $chan = $this->dataFormatDataToDb($chan);

      if($nick != bot_IRCNICK && !ereg('^Guest[0-9]+$', $nick))
      {
        $this->count->countIncJoin($nick, $chan);
      }
      break;
    case 'PART':
      $chan = $par;

      $nick = $this->dataFormatDataToDb($nick);
      $chan = $this->dataFormatDataToDb($chan);

      $this->count->countIncQuit($nick, $chan);
      break;
    case 'QUIT':
      $msg  = substr($par, 1);
      $nick = $this->dataFormatDataToDb($nick);
      $this->count->countIncQuit($nick);
      break;
    case 'KICK':
      $chan = $par;
      $kicked_nick = substr($msg, 0, strpos($msg, ' '));

      $kicked_nick = $this->dataFormatDataToDb($kicked_nick);
      $chan = $this->dataFormatDataToDb($chan);

      $this->count->countIncQuit($kicked_nick, $chan, 'Shup Up, Kartman!');
      break;
    case 'MODE':
    case 'TOPIC':
      break;
    }
  }

  function dataCountClose()
  {
    return $this->count->countClose();
  }
}
