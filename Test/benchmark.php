<pre>
<?php
use Nette\Caching\BtreeFileJournal;
use Nette\Caching\SqliteJournal;
use Nette\Caching\FileJournal;
use Nette\Caching\Cache;

set_time_limit(0);

$all = TRUE;

require_once dirname(__FILE__) . '/Object.php';
require_once dirname(__FILE__) . '/Cache.php';
require_once dirname(__FILE__) . '/ICacheJournal.php';
require_once dirname(__FILE__) . '/FileJournal.php';
require_once dirname(__FILE__) . '/../BtreeFileJournal.php';
require_once dirname(__FILE__) . '/SqliteJournal.php';

class Benchmark 
{
  private $currentType;
  private $no;
  private $result;
  
  public function test($type, $function) {
    $start = microtime(true);
    $function();
    $duration = microtime(true) - $start;
    $this->result[$type][$this->currentType][] = $duration;
    echo $type.': '.$duration . " s\n";
  }
  
  public function testRepeat($type, $function, $repeat = 1) {
    for ($i=0;$i<$repeat;$i++) {
      $start = microtime(true);
      $function();
      $duration = microtime(true) - $start;
      $this->result[$type][$this->currentType][] = $duration;
    }
  }
  
  public function type($name)
  {
    $this->currentType = $name;
    echo "\n".$name ."\n";
  }
  
  public function cvs()
  {
    echo "\n\n";
    $csv = array();
    
    foreach($this->result as $type => $c) {
      foreach($c as $currentType => $d) {
        foreach($d as $id => $duration) {
          $csv[$id][] = $duration;  
        }
      }
    }
    $data = '';
    foreach($csv as $type => $duration) {
      $data .= implode(',', $duration) . "\n";
    }
    file_put_contents('results.csv', $data);
    echo $data;
  }
}

$bench = new Benchmark;

function insert1000($journal) 
{
  for($i=1;$i<=1000;$i++) {
    $journal->write('/usr/local/apache2/htdocs/kam/temp/cache/_fnjsnfjksadnfnsdjkcnjkd'.$i, 
      array(
        Cache::TAGS => array('homepage', 'homepage'.$i), 
        Cache::PRIORITY => $i,
      )
    );
  }
}

function cleanPriority($journal) 
{
  $clean = $journal->clean(array(Cache::PRIORITY => 400));
  if(count($clean) != 400)
    echo 'cleanPriority error'."\n";
}

function cleanTag($journal)
{
  $clean = $journal->clean(array(Cache::TAGS => array('homepage528')));
  if(count($clean) != 1)
    echo 'cleanTag error'."\n";
}

function cleanTag2($journal)
{
  $clean = $journal->clean(array(Cache::TAGS => array('homepage575', 'homepage645', 'homepage787')));
  if(count($clean) != 3)
    echo 'cleanTag2 error'."\n";
}

function cleanTag3($journal, $rightCount)
{
  $clean = $journal->clean(array(Cache::TAGS => array('homepage')));
  if(count($clean) != $rightCount) {
    var_dump(count($clean));
    echo 'cleanTag3 error'."\n";
  }
}

function cleanAll($journal)
{
  $journal->clean(array(Cache::ALL => TRUE));
}

$bench->type('Init');

if($all) {
  $bench->test('SqliteJournal', function() use (&$sj) {$sj = new SqliteJournal(dirname(__FILE__).'/journal.sqlite');});
  $bench->test('FileJournal', function() use (&$fj) {$fj = new FileJournal(dirname(__FILE__));});
}
$bench->test('BtreeFileJournal', function() use (&$bfj) {$bfj = new BtreeFileJournal(dirname(__FILE__));});

$bench->type('1000 inserts');
if($all) {
  $bench->test('SqliteJournal', function() use (&$sj) {insert1000($sj);});
  $bench->test('FileJournal', function() use (&$fj) {insert1000($fj);});
}
$bench->test('BtreeFileJournal', function() use (&$bfj) {insert1000($bfj);});

$bench->type('Clean all');
if($all) {
  $bench->test('SqliteJournal', function() use (&$sj) {cleanTag3($sj, 1000);});
  $bench->test('FileJournal', function() use (&$fj) {cleanTag3($fj, 1000);});
}
$bench->test('BtreeFileJournal', function() use (&$bfj) {cleanTag3($bfj, 1000);}); 

$bench->type('Same 1000 inserts again');
if($all) {
  $bench->test('SqliteJournal', function() use (&$sj) {insert1000($sj);});
  $bench->test('FileJournal', function() use (&$fj) {insert1000($fj);});
}
$bench->test('BtreeFileJournal', function() use (&$bfj) {insert1000($bfj);}); 

$bench->type('Clean priority lower then 400');
if($all) {
  $bench->test('SqliteJournal', function() use (&$sj) {cleanPriority($sj);});
  $bench->test('FileJournal', function() use (&$fj) {cleanPriority($fj);});
}
$bench->test('BtreeFileJournal', function() use (&$bfj) {cleanPriority($bfj);});

$bench->type('Clean one tag');
if($all) {
  $bench->test('SqliteJournal', function() use (&$sj) {cleanTag($sj);});
  $bench->test('FileJournal', function() use (&$fj) {cleanTag($fj);});
}
$bench->test('BtreeFileJournal', function() use (&$bfj) {cleanTag($bfj);});

$bench->type('Clean 3 tags');
if($all) {
  $bench->test('SqliteJournal', function() use (&$sj) {cleanTag2($sj);});
  $bench->test('FileJournal', function() use (&$fj) {cleanTag2($fj);});
}
$bench->test('BtreeFileJournal', function() use (&$bfj) {cleanTag2($bfj);}); 

$bench->type('Clean all other tags');
if($all) {
  $bench->test('SqliteJournal', function() use (&$sj) {cleanTag3($sj, 596);});
  $bench->test('FileJournal', function() use (&$fj) {cleanTag3($fj, 596);});
}
$bench->test('BtreeFileJournal', function() use (&$bfj) {cleanTag3($bfj, 596);});  

$bench->type('Clean All');
if($all) {
  $bench->test('SqliteJournal', function() use (&$sj) {cleanAll($sj);});
  $bench->test('FileJournal', function() use (&$fj) {cleanAll($fj);});
}
$bench->test('BtreeFileJournal', function() use (&$bfj) {cleanAll($bfj);});

unlink(dirname(__FILE__).'/'.BtreeFileJournal::FILE);

if($all) {
  unlink(dirname(__FILE__).'/fj');
  unlink(dirname(__FILE__).'/fj.log');
  unlink(dirname(__FILE__).'/journal.sqlite');
}