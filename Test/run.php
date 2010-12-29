<?php
use Nette\Caching\BtreeFileJournal;
use Nette\Caching\Cache;

set_time_limit(0);

$coverage = FALSE;
$basicTests = TRUE;
$bigTests = TRUE;
$extraBigTests = TRUE;

class Test
{
  public static function t($result, $condition, $name) 
  {
    echo $name . ' ';
    if($condition === TRUE) {
      echo '– OK'."<br>\n";
    } else {
      echo '– <span style="color:red">Error</span>'."<br>\n";
      echo '<div class"error">';
      echo 'Count: '.count($result).'<br>'."\n";
      var_dump($result);
      echo '</div>';
    }
    @ob_flush();
    @flush();
  }
}

require_once dirname(__FILE__).'/Object.php';
require_once dirname(__FILE__).'/Cache.php';
require_once dirname(__FILE__).'/ICacheJournal.php';
require_once dirname(__FILE__).'/exceptions.php';
require_once dirname(__FILE__).'/../BtreeFileJournal.php';

if ($coverage and function_exists('xdebug_start_code_coverage')) {
  xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
}

$journal = new BtreeFileJournal(dirname(__FILE__));

if($basicTests) {
  echo '<h2>Basic test</h2>';
  
  $journal->write('ok_test1', 
    array(
      Cache::TAGS => array('test:homepage'), 
    ));
    
  $result = $journal->clean(array(Cache::TAGS => array('test:homepage')));
  Test::t($result, (count($result) === 1 and $result[0] === 'ok_test1'), 'One tag');
  
  $journal->write('ok_test2', 
    array(
      Cache::TAGS => array('test:homepage', 'test:homepage2'), 
    ));
    
  $result = $journal->clean(array(Cache::TAGS => array('test:homepage2')));
  Test::t($result, (count($result) === 1 and $result[0] === 'ok_test2'), 'Two tags');
  
  $journal->write('ok_test2b', 
    array(
      Cache::TAGS => array('test:homepage', 'test:homepage2'), 
    ));
    
  $result = $journal->clean(array(Cache::TAGS => array('test:homepage', 'test:homepage2')));
  Test::t($result, (count($result) === 1 and $result[0] === 'ok_test2b'), 'Two tags b');
  
  $journal->write('ok_test2c', 
    array(
      Cache::TAGS => array('test:homepage', 'test:homepage'), 
    ));
    
  $result = $journal->clean(array(Cache::TAGS => array('test:homepage', 'test:homepage')));
  Test::t($result, (count($result) === 1 and $result[0] === 'ok_test2c'), 'Two same tags');
  
  $journal->write('ok_test2d', 
    array(
      Cache::TAGS => array('test:homepage'), 
      Cache::PRIORITY => 15,
    ));
    
  $result = $journal->clean(array(Cache::TAGS => array('test:homepage'), Cache::PRIORITY => 20));
  Test::t($result, (count($result) === 1 and $result[0] === 'ok_test2d'), 'Tag and priority');
  
  $journal->write('ok_test3', 
    array(
      Cache::PRIORITY => 10, 
    ));
    
  $result = $journal->clean(array(Cache::PRIORITY => 10));
  Test::t($result, (count($result) === 1 and $result[0] === 'ok_test3'), 'Priority only');
  
  $journal->write('ok_test4', 
    array(
      Cache::TAGS => array('test:homepage'),
      Cache::PRIORITY => 10, 
    ));
    
  $result = $journal->clean(array(Cache::TAGS => array('test:homepage')));
  Test::t($result, (count($result) === 1 and $result[0] === 'ok_test4'), 'Priority and tag (clean by tag)');
  
  $journal->write('ok_test5', 
    array(
      Cache::TAGS => array('test:homepage'),
      Cache::PRIORITY => 10, 
    ));
    
  $result = $journal->clean(array(Cache::PRIORITY => 10));
  Test::t($result, (count($result) === 1 and $result[0] === 'ok_test5'), 'Priority and tag (clean by priority)');
  
  for($i=1;$i<=10;$i++) {
    $journal->write('ok_test6_'.$i, 
      array(
        Cache::TAGS => array('test:homepage', 'test:homepage/'.$i),
        Cache::PRIORITY => $i, 
      ));
  }
  
  $result = $journal->clean(array(Cache::PRIORITY => 5));
  Test::t($result, (count($result) === 5 and $result[0] === 'ok_test6_1'), '10 writes, clean priority lower then 5');
  
  $result = $journal->clean(array(Cache::TAGS => array('test:homepage/7')));
  Test::t($result, (count($result) === 1 and $result[0] === 'ok_test6_7'), '10 writes, clean tag homepage/7');
  
  $result = $journal->clean(array(Cache::TAGS => array('test:homepage/4')));
  Test::t($result, (count($result) === 0), '10 writes, clean non exists tag');
  
  $result = $journal->clean(array(Cache::PRIORITY => 4));
  Test::t($result, (count($result) === 0), '10 writes, clean non exists priority');
  
  $result = $journal->clean(array(Cache::TAGS => array('test:homepage')));
  Test::t($result, (count($result) === 4 and $result[0] === 'ok_test6_6'), '10 writes, clean other');
  
  $journal->write('ok_test7ščřžýáíé', 
    array(
      Cache::TAGS => array('čšřýýá', 'ýřžčýž/'.$i) 
    ));
  $result = $journal->clean(array(Cache::TAGS => array('čšřýýá')));
  Test::t($result, (count($result) === 1 and $result[0] === 'ok_test7ščřžýáíé'), 'Special chars');
  
  $journal->write('ok_test_a', 
    array(
      Cache::TAGS => array('homepage') 
    ));
  $journal->write('ok_test_a', 
    array(
      Cache::TAGS => array('homepage') 
    ));
  $result = $journal->clean(array(Cache::TAGS => array('homepage')));
  Test::t($result, (count($result) === 1 and $result[0] === 'ok_test_a'), 'Duplicates: same tags');
  
  $journal->write('ok_test_b', 
    array(
      Cache::PRIORITY => 12
    ));
  $journal->write('ok_test_b', 
    array(
      Cache::PRIORITY => 12
    ));
  $result = $journal->clean(array(Cache::PRIORITY => 12));
  Test::t($result, (count($result) === 1 and $result[0] === 'ok_test_b'), 'Duplicates: same priority');
  
  $journal->write('ok_test_ba', 
    array(
      Cache::TAGS => array('homepage') 
    ));
  $journal->write('ok_test_ba', 
    array(
      Cache::TAGS => array('homepage2') 
    ));
  $result = $journal->clean(array(Cache::TAGS => array('homepage')));
  $result2 = $journal->clean(array(Cache::TAGS => array('homepage2')));
  Test::t($result, (count($result2) === 1 and count($result) === 0 and $result2[0] === 'ok_test_ba'), 'Duplicates: differenet tags');
  
  $journal->write('ok_test_baa', 
    array(
      Cache::TAGS => array('homepage', 'aąa') 
    ));
  $journal->write('ok_test_baa', 
    array(
      Cache::TAGS => array('homepage2', 'aaa') 
    ));
  $result = $journal->clean(array(Cache::TAGS => array('homepage')));
  $result2 = $journal->clean(array(Cache::TAGS => array('homepage2')));
  Test::t($result, (count($result2) === 1 and count($result) === 0 and $result2[0] === 'ok_test_baa'), 'Duplicates: 2 differenet tags');
  
  $journal->write('ok_test_bb', 
    array(
      Cache::PRIORITY => 15
    ));
  $journal->write('ok_test_bb', 
    array(
      Cache::PRIORITY => 20 
    ));
  $result = $journal->clean(array(Cache::PRIORITY => 30));
  Test::t($result, (count($result) === 1 and $result[0] === 'ok_test_bb'), 'Duplicates: differenet priorities');
  
  
  $journal->write('ok_test_all_tags', 
    array(
      Cache::TAGS => array('test:all', 'test:all') 
    ));
  $journal->write('ok_test_all_priority', 
    array(
      Cache::PRIORITY => 5,
    ));
  $result = $journal->clean(array(Cache::ALL => TRUE));
  $result2 = $journal->clean(array(Cache::TAGS => 'test:all'));
  Test::t($result, ($result === NULL and empty($result2)), 'Clean ALL');
}

if($bigTests) {

  echo '<h2>Big journal tests</h2>';
  
  for($i=1;$i<=1000;$i++) {
  $journal->write('ok_one_tag_'.$i, 
    array(
      Cache::TAGS => array('test:homepage'),
    ));
  }
  $result = $journal->clean(array(Cache::TAGS => array('test:homepage')));
  Test::t($result, (count($result) === 1000 and strpos($result[0], 'ok_one_tag') === 0), '1000 writes, delete 1000 with one tag');
  
  for($i=1;$i<=1000;$i++) {
  $journal->write('ok_one_priority_'.$i, 
    array(
      Cache::PRIORITY => 30,
    ));
  }
  $result = $journal->clean(array(Cache::PRIORITY => 50));
  Test::t($result, (count($result) === 1000 and strpos($result[0], 'ok_one_priority') === 0), '1000 writes, delete 1000 with one priority');
  
  
  //$journal->clean(array(Cache::ALL => TRUE));
  
  for($i=1;$i<=1000;$i++) {
  $journal->write('ok_test_priority_'.$i, 
    array(
      Cache::TAGS => array('test:homepage', 'test:homepage/'.$i),
      Cache::PRIORITY => 100, 
    ));
  }
  
  $result = $journal->clean(array(Cache::PRIORITY => 200));
  Test::t($result, (count($result) === 1000 and strpos($result[0], 'ok_test_priority') === 0), '1000 writes, delete 1000 by one priority');
  
  for($i=1;$i<=1000;$i++) {
  $journal->write('ok_test_8_'.$i, 
    array(
      Cache::TAGS => array('test:homepage', 'test:homepage/'.$i),
      Cache::PRIORITY => $i, 
    ));
  }
  
  $result = $journal->clean(array(Cache::TAGS => array('test:homepage')));
  Test::t($result, (count($result) === 1000 and strpos($result[0], 'ok_test_8_') === 0), '1000 writes, delete 1000 by one tag');
  
  for($i=1;$i<=1000;$i++) {
  $journal->write('ok_test9'.$i, 
    array(
      Cache::TAGS => array('test:homepage', 'test:homepage/'.$i),
      Cache::PRIORITY => $i, 
    ));
  }
  
  $result = array();
  for($i=1;$i<=1000;$i++) {
    $result[] = $journal->clean(array(Cache::TAGS => array('test:homepage/'.$i)));
  }
  Test::t($result, (count($result) === 1000 and strpos($result[0][0], 'ok_test9') === 0), '1000 writes, delete 1000 by tags');
  
  //$journal->debugNodes();
  
  for($i=1;$i<=1000;$i++) {
  $journal->write('ok_test10_'.$i, 
    array(
      Cache::TAGS => array('test:homepage', 'test:homepage/'.$i),
      Cache::PRIORITY => $i, 
    ));
  }
  
  $result = $journal->clean(array(Cache::PRIORITY => 400));
  Test::t($result, (count($result) === 400 and strpos($result[0], 'ok_test10') === 0), '1000 writes, delete  priority lower than 400');
  
  $result = $journal->clean(array(Cache::PRIORITY => 500));
  Test::t($result, (count($result) === 100 and strpos($result[0], 'ok_test10') === 0), '1000 writes, delete priority lower then 500');
  
  $result = $journal->clean(array(Cache::PRIORITY => 300));
  Test::t($result, (count($result) === 0), '1000 writes, delete non exists by priority');
  
  $result = $journal->clean(array(Cache::TAGS => array('test:homepage/123', 'test:homepage/256', 'test:homepage/356', 'test:homepage/500')));
  Test::t($result, (count($result) === 0), '1000 writes, delete non exists by tags');
  
  $result = $journal->clean(array(Cache::TAGS => array('test:homepage/654', 'test:homepage/867', 'test:homepage/764', 'test:homepage/853')));
  Test::t($result, (count($result) === 4 and strpos($result[0], 'ok_test10') === 0), '1000 writes, delete exists by tags');
  
  $result = $journal->clean(array(Cache::TAGS => array('test:homepage')));
  Test::t($result, (count($result) === 496 and strpos($result[0], 'ok_test10') === 0), '1000 writes, others by tag');
}

if($extraBigTests) {
  echo '<h2>Extra big journal</h2>';
  
  for($i=1;$i<=10000;$i++) {
  $journal->write('ok_test10_'.$i, 
    array(
      Cache::TAGS => array('test:homepage', 'test:homepage/'.$i),
      Cache::PRIORITY => $i, 
    ));
  }
  
  $result = $journal->clean(array(Cache::TAGS => array('test:homepage')));
  Test::t($result, (count($result) === 10000 and strpos($result[0], 'ok_test10') === 0), '10 000 writes, clear all by tag');
  
  for($i=1;$i<=10000;$i++) {
  $journal->write('ok_test10b_'.$i, 
    array(
      Cache::TAGS => array('test:homepage', 'test:homepage/'.$i),
      Cache::PRIORITY => $i, 
    ));
  }
  
  $result = $journal->clean(array(Cache::PRIORITY => 10000));
  Test::t($result, (count($result) === 10000 and strpos($result[0], 'ok_test10b') === 0), '10 000 writes, clear all by priority');
}

if ($coverage and function_exists('xdebug_get_code_coverage')) {
  $coverageArray = xdebug_get_code_coverage();
  file_put_contents('coverage.dat', serialize($coverageArray));
}