<?php

require dirname(dirname(__FILE__)) . '/init.php';

if (!isset($_POST['query'])) die('Missing query');
$query = trim($_POST['query']);
$page = isset($_POST['page']) ? intval($_POST['page']) : 0;
$redirs = isset($_POST['redirs']) ? intval($_POST['redirs']) > 0 : false;
$agent = isset($_POST['agent']) ? $_POST['agent'] : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 Safari/537.36';

logDebuggingSession(sprintf('Bing query (%s)', $query));

$query = urlencode($query);

if ($page > 0) $start = "&first=$page";
else $start = "";

$ch = curl_init("https://www.bing.com/search?q={$query}{$start}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_ENCODING, '');
curl_setopt($ch, CURLOPT_USERAGENT, $agent);
if ($redirs) curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$data = curl_exec($ch);
$code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch) > 0) die(sprintf('Encountered a CURL error (code %i): %s', curl_errno($ch), curl_error($ch)));
if ($code != 200) die(sprintf('Encountered an abnormal status code (code %i)', $code));

echo curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) . PHP_EOL;
echo sanitize_trusted($data);
