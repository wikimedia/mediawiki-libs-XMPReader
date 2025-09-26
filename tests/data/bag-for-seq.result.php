<?php

return [
	'xmp-general' => [
		'Artist' => [
			'_type' => 'ul',
			0 => 'The author',
		]
	],
	'logs' => [
		'Wikimedia\XMPReader\Reader::startElementModeSeq Expected' .
			' an rdf:Seq, but got an rdf:Bag. Pretending it is a Seq,' .
			' since some buggy software is known to screw this up.'
	]
];
