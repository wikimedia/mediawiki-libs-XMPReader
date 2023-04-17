<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config-library.php';

$cfg['directory_list'][] = 'vendor/';

$cfg['exclude_analysis_directory_list'][] = 'vendor/';

// Can be addressed when we require PHP >= 8.0 and we have XMLParser to use
$cfg['suppress_issue_types'][] = 'PhanTypeMismatchArgumentInternal';
$cfg['suppress_issue_types'][] = 'PhanTypeMismatchProperty';
$cfg['suppress_issue_types'][] = 'PhanTypeMismatchPropertyProbablyReal';
// Suppress PHP 8.0+ issue on PHP 7.4 compatibility code
$cfg['suppress_issue_types'][] = 'PhanDeprecatedFunctionInternal';

return $cfg;
