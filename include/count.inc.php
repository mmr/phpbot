<?
// $Id: count.inc.php,v 1.7 2003/09/02 17:25:38 mmr Exp $

//
// phpbot --> data --> irc --> socket
//              `----> sqllink
//              `----> COUNT
//

class bot_count
{
  var $sqllink = NULL;

  // Construtor
  function bot_count($sqllink)
  {
    return $this->sqllink = $sqllink;
  }

  // Incrementa contagem de Joins
  function countIncJoin($nick, $chan)
  {
    // Atualizando sem saber se o usuario existe
    $query = "
      UPDATE user SET
        usr_join_dt  = now(),
        usr_join_qtt = usr_join_qtt + 1
      WHERE
        usr_nick = '" . $nick . "' AND
        usr_chan = '" . $chan . "' AND
        usr_join_qtt = usr_quit_qtt";

    $ret = $this->sqllink->sqlQuery($query);

    if($ret > 0)
    {
      // Sim, conseguiu
      return true;
    }
    else
    {
      // Nao conseguiu, o usuario nao existe
      // Inserir
      $query = "
        INSERT INTO user
        (
          usr_nick,
          usr_chan,
          usr_join_qtt,
          usr_join_dt,
          usr_add_dt
        )
        VALUES
        (
          '" . $nick . "',
          '" . $chan . "',
          1,
          now(),
          now()
        )";
      return $this->sqllink->sqlQuery($query);
    }
    return false;
  }

  // Olha se o cara nao trocou de nick
  function countVerifyNickChange($nick, $chan = '')
  {
    $query = "
      SELECT
        COUNT(usr_id) AS count
      FROM
        user
      WHERE
        usr_cur_nick = '" . $nick . "'" . $chan . " AND 
        usr_join_qtt = (usr_quit_qtt + 1)";

    $ret = $this->sqllink->sqlSingleQuery($query);
    return ($ret['count'] > 0);
  }

  // Abstracao de Incremento
  function countInc($inc, $nick, $chan = '')
  {
    if(!empty($chan))
    {
      $chan = " AND usr_chan = '" . $chan . "'";
    }

    if($this->countVerifyNickChange($nick, $chan))
    {
      $nick = "usr_cur_nick = '" . $nick . "'";

    }
    else
    {
      $nick = "usr_nick = '" . $nick . "'";
    }

    $query = "
      UPDATE user SET
        usr_" . $inc . "_dt  = now(),
        usr_" . $inc . "_qtt = usr_" . $inc . "_qtt + 1
      WHERE
        " . $nick . $chan . " AND
        usr_join_qtt = (usr_quit_qtt + 1)";

    return ($this->sqllink->sqlQuery($query) > 0);
  }

  // Muda nick e incrementa contagem de mudanca de Nick
  function countNickChange($oldnick, $nick)
  {
    $savenick = $nick;

    if($this->countVerifyNickChange($oldnick))
    {
      $nick = "usr_cur_nick = '" . $oldnick . "'";

    }
    else
    {
      $nick = "usr_nick = '" . $oldnick . "'";
    }

    $query = "
      UPDATE user SET
        usr_cur_nick = '" . $savenick . "',
        usr_nickchg_qtt = usr_nickchg_qtt + 1
      WHERE
        " . $nick . " AND
        usr_join_qtt = (usr_quit_qtt + 1)";

    return ($this->sqllink->sqlQuery($query) > 0);
  }

  // Incrementa saidas do IRC (Quit/Part) e Kicks
  function countIncQuit($nick, $chan = '', $kick = '')
  {
    if(!empty($chan))
    {
      // Forneceu o Canal entao eh um PART
      $chan = " AND usr_chan = '" . $chan . "'";
    }

    if($this->countVerifyNickChange($nick, $chan))
    {
      $nick = "usr_cur_nick = '" . $nick . "'";

    }
    else
    {
      $nick = "usr_nick = '" . $nick . "'";
    }

    if(!empty($kick))
    {
      $kick = ',
        usr_kick_dt = now(),
        usr_kick_qtt = usr_kick_qtt + 1';
    }

    $query = "
      UPDATE user SET
        usr_quit_dt  = now(),
        usr_quit_qtt = usr_quit_qtt + 1" . $kick . "
      WHERE
        " . $nick . $chan . " AND
        usr_join_qtt = (usr_quit_qtt + 1)";

    if($this->sqllink->sqlQuery($query) > 0)
    {
      // Atualizando permanencia
      $query = "
        SELECT 
          UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(usr_join_dt) AS usr_cur_period,
          usr_max_period
        FROM
          user
        WHERE
          " . $nick . $chan . " AND
          usr_join_qtt = usr_quit_qtt";

      $ret = $this->sqllink->sqlSingleQuery($query);

      // Vendo se a permanencia atual eh maior que a maxima
      if($ret['usr_cur_period'] > $ret['usr_max_period']) 
      {
        // Sim, eh maior, atualizar
        $query = "
          UPDATE user SET 
            usr_max_period = '" . $ret['usr_cur_period'] . "'
          WHERE
            " . $nick . $chan;

        return ($this->sqllink->sqlQuery($query) > 0);
      }
      return true;
    }
    return false;
  }

  function countIncAction($nick, $chan)
  {
    return $this->countInc('act', $nick, $chan);
  }

  function countIncMsg($nick, $chan)
  {
    return $this->countInc('msg', $nick, $chan);
  }

  function countIncBotuse($nick, $chan)
  {
    return $this->countInc('botuse', $nick, $chan);
  }

  function countIncKick($nick, $chan)
  {
    return $this->countIncQuit($nick, $chan, 'Shup up Kartman!');
  }

  function countClose()
  {
    global $bot_CHANNELS;

    $chan = "(usr_chan IS NULL";
    foreach($bot_CHANNELS as $c)
    {
      $chan .= " OR usr_chan = '" . $c . "'";
    }
    $chan .= ")";

    $query = "
      UPDATE user SET
        usr_quit_dt = now(),
        usr_quit_qtt = usr_quit_qtt + 1
      WHERE
        usr_join_qtt = (usr_quit_qtt + 1) AND " . $chan;

    if($ret = $this->sqllink->sqlQuery($query))
    {
      $query = "
        UPDATE user SET
          usr_max_period = UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(usr_join_dt)
        WHERE
          usr_join_qtt = usr_quit_qtt AND 
          usr_max_period < UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(usr_join_dt)
          " . $chan;

      if($this->sqllink->sqlQuery($query))
      {
        return true;
      }
      else
      {
        bot_errorLog('countClose: Could not update (2).', bot_ERR_ERROR);
      }
    }
    else
    {
      bot_errorLog('countClose: Could not update (1).', bot_ERR_ERROR);
    }

    return false;
  }
}
