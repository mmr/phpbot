<?
define('bot_URL', $_SERVER['SCRIPT_NAME']);
?>
<html>
<head>
  <title>PHPBot Source</title>
  <style>
    body{font-family: Verdana, Helvetica; color: #000000; background-color: #ffffff; line-height: 1.4; font-size: 14px;}
    a:link, a:visited {font-family: Verdana, Helvetica; font-size: 15px; font-weighT: bold; color: #003399; background: none; text-decoration: none}
    a:active, a:hover {font-family: Verdana, Helvetica; font-size: 15px; font-weight: bold; color: #55AAFF; background: none; text-decoration: none}
    code{font-size: 14px;}
    pre {font-size: 14px;}
    hr  {color: #000000; background-color: #000000; width: 100%; height: 1px}
    h1  {font-family: Verdana, Helvetica; font-size: 18px; color: #000000; background: none}
  </style>
</head>
<body bgcolor='#ffffff' text='#000000'>
<h1>PHPBot Source</h1>
<hr />
<pre>
  <a href='<?= bot_URL ?>?file=phpbot.php'>PHPBot</a> --&gt; <a href='<?= bot_URL ?>?file=include%2Fdata.inc.php'>Data</a> --&gt; <a href='<?= bot_URL ?>?file=include%2Firc.inc.php'>IRC</a> --&gt; <a href='<?= bot_URL ?>?file=include%2Fsocket.inc.php'>Socket</a>
                 `----&gt; <a href='<?= bot_URL ?>?file=include%2Fsqllink.inc.php'>SQLLink</a>
                 `----&gt; <a href='<?= bot_URL ?>?file=include%2Fcount.inc.php'>Count</a>

  Configura&ccedil;&otilde;es:
    <a href='<?= bot_URL ?>?file=include%2Fbotconfig.inc.php'>include/botconfig.inc.php</a>
    <a href='<?= bot_URL ?>?file=include%2Fsqlconfig.inc.php'>include/sqlconfig.inc.php</a>

  Bot Inteiro em Tar.Gz:
    <a href='phpbot-1.5.tar.gz'>php-1.5.tar.gz</a>
</pre>
<hr />
<?
if(isset($_GET['file']))
{
  $file = $_GET['file'];

  ob_start();
    show_source($file);
    $source = ob_get_contents();
  ob_end_clean();

  $source = explode('<br />', $source);

  for($i=0; $i<count($source)-1; $i++)
  {
    $source[$i] = '<span style="color: #000000">[' . sprintf('%03d', $i) . ']</span>&nbsp;&nbsp;' . wordwrap($source[$i]);
  }

  $source[0] = str_replace('[000]', '<code>[000]</code>', $source[0]);
?>
<h4><?= $file ?></h4>
<p style='text-align: left;'>
  <?= implode('<br />', $source) ?>
</p>
<hr />
<?
}
?>
$Id: index.php,v 1.23 2003/07/27 03:10:29 mmr Exp $
</body>
