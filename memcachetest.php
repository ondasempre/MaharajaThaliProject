<?php

$memcache = new Memcache;
$memcache->connect('localhost', 11211) or die ("Non ci si può connettere al server.");

$version = $memcache->getVersion();
echo "Versione del server: ".$version."<br/>\n";

$tmp_object = new stdClass;
$tmp_object->str_attr = 'test';
$tmp_object->int_attr = 123;

$memcache->set('key', $tmp_object, false, 10) or die ("Errore di memorizzazione dati.");
echo "Memorizzare i dati nella cache (i dati scadono tra 10 secondi)<br/>\n";

$get_result = $memcache->get('key');
echo "Dati presenti in cache:<br/>\n";

var_dump($get_result);

?>