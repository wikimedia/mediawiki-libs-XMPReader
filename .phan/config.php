<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'][] = 'vendor/';

$cfg['exclude_analysis_directory_list'][] = 'vendor/';

// Can be addressed when we require PHP >= 8.0 and we have XMLParser to use
$cfg['suppress_issue_types'][] = 'PhanTypeMismatchArgumentInternal';
$cfg['suppress_issue_types'][] = 'PhanTypeMismatchProperty';
$cfg['suppress_issue_types'][] = 'PhanTypeMismatchPropertyProbablyReal';

return $cfg;
