<?
// $Id: sqllink.inc.php,v 1.9 2003/07/26 23:19:31 mmr Exp $

//
// phpbot --> data --> irc --> socket
//              `----> SQLLINK
//              `----> count
//

require(bot_INCPATH . '/sqlconfig.inc.php');

class bot_sqlLink
{
  var $sqllink = NULL;

  // Construtor
  function bot_sqlLink()
  {
    $i = 0;
    while((!$this->sqlConnect()) && ($i < 3))
    {
      $i++;
      sleep(1);
    }
  }

  // Conecta ao servidor IRC
  function sqlConnect()
  {
    if($this->sqlIsConnected())
    { 
      bot_errorLog('Already connected to DB', bot_ERR_WARN);
      return false; 
    }
    
    $this->sqllink = mysql_connect(bot_DB_HOST, bot_DB_USER, bot_DB_PASS);

    if(mysql_select_db(bot_DB_NAME, $this->sqllink) <= 0)
    {
      bot_errorLog(mysql_error($this->sqllink), bot_ERR_WARN);
      return false;
    }
    
    if($this->sqllink)
    {
      return true;
    }
    else
    { 
      mysql_close($this->sqllink);
      bot_errorLog(mysql_error($this->sqllink), bot_ERR_ERROR);
      return false; 
    }
  }

  // Verifica se ja esta conectado  
  function sqlIsConnected()
  {
    return $this->sqllink;
  }

  // Query singular (LIMIT 1)
  function sqlSingleQuery($query)
  {
    if(!$query)
    {
      return false;
    } 

    if(bot_DEBUG_LEVEL & bot_DEBUG_SQL)
    {
      bot_errorLog($query . ' LIMIT 1', bot_ERR_NOTICE);
    }

    if(!$this->sqlIsConnected())
    {
      bot_errorLog('MySQL NOT CONNECTED', bot_ERR_ERROR);
      return false;
    }

    $result = mysql_query($query . ' LIMIT 1', $this->sqllink);
    if(is_bool($result))
    {
      return $result;
    }

    if((mysql_num_rows($result)> 0) && ($aux = mysql_fetch_array($result, MYSQL_ASSOC)))
    {
      return $aux;
    }
    else
    {
      return true;
    }
  }

  function sqlQuery($query)
  {
    if(!$query)
    {
      return false;
    }

    if(bot_DEBUG_LEVEL & bot_DEBUG_SQL)
    {
      bot_errorLog($query, bot_ERR_NOTICE);
    }

    if(!$this->sqlIsConnected())
    {
      bot_errorLog('MySQL NOT CONNECTED', bot_ERR_ERROR);
      return false;
    }

    $result = mysql_query($query, $this->sqllink);

    if(is_bool($result))
    {
      return mysql_affected_rows($this->sqllink);
    }

    $num = mysql_num_rows($result);

    if($num > 0)
    {
      for($i=0; $i<$num; $i++)
      {
        $row[$i] = mysql_fetch_array($result, MYSQL_ASSOC);
      }

      return $row;
    }
    return true;
  }

  // Devolve link de conexao SQL
  function sqlGetLink()
  {
    return $this->sqllink;
  }

  // Fecha conexao SQL
  function sqlCloseLink()
  {
    if(!is_null($this->sqllink))
    {
      return mysql_close($this->sqllink);
    }

    return false;
  }
}
?>
