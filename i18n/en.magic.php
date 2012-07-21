<?php

/**
 * English.
 *
 * Stephane Galland <galland@arakhne.org>
 *
 * A message must follow the following format:
 * 'magic_word'     => array( 'case_sensitive', 'name1', 'name2', ...),
 * where:
 * - magic_word is the magic word to translate.
 * - case_sensitive is equal to '0' for case insensitive and '1' for case sensitive.
 * - name1, name2, etc. are the translations for the magic word in preferred order.
 *   For English translation, we recommend to put back the magic word in the list.
 *   For other languages than English, we recommend to put the English as the last choice.
 */
$magicWords['en'] = array(
	'events'		=>	array( '0', 'events' ),
	'noticeevents'		=>	array( '0', 'noticeevents' ),
);


