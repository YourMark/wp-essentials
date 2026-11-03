<?php
/**
 * Dutch translations, in the format WordPress 6.5+ reads without a .mo file.
 *
 * @package YourMark\Essentials
 */

return [
	'domain'       => 'yourmark-essentials',
	'plural-forms' => 'nplurals=2; plural=(n != 1);',
	'language'     => 'nl_NL',
	'messages'     => [
		'Client administrator' => 'Beheerder (klant)',
		'<strong>Error:</strong> Too many failed login attempts. Try again in %d minutes.' => '<strong>Fout:</strong> te veel mislukte inlogpogingen. Probeer het over %d minuten opnieuw.',
		'Your account can change everything on this site, so protect it with a second step. <a href="%s">Set up two-step login</a>.' => 'Met je account kun je alles op deze site wijzigen, dus bescherm het met een tweede stap. <a href="%s">Tweestapsverificatie instellen</a>.',
		'WordPress readme' => 'Readme van WordPress',
		'PHP in the uploads folder' => 'PHP in de uploadsmap',
		'WordPress\'s readme.html has been removed' => 'De readme.html van WordPress is verwijderd',
		'WordPress\'s readme.html is still in place' => 'De readme.html van WordPress staat er nog',
		'It tells anyone which WordPress version runs here, which the rest of the site no longer does. Delete it on every deploy or update, since core puts it back.' => 'Daarin leest iedereen welke WordPress-versie hier draait, wat de rest van de site niet meer prijsgeeft. Verwijder het bestand bij elke deploy of update, want WordPress zet het terug.',
		'PHP in the uploads folder could not be tested' => 'PHP in de uploadsmap kon niet worden getest',
		'The uploads folder is not writable, so no test file could be placed.' => 'De uploadsmap is niet schrijfbaar, dus er kon geen testbestand worden geplaatst.',
		'The site could not request a file from its own uploads folder.' => 'De site kon geen bestand uit de eigen uploadsmap opvragen.',
		'PHP does not run from the uploads folder' => 'PHP draait niet vanuit de uploadsmap',
		'PHP runs from the uploads folder' => 'PHP draait vanuit de uploadsmap',
		'Anyone who gets a file into uploads through a plugin bug can run code on the server. Deny PHP in the uploads folder in the web server, for nginx with a location block that returns 403 for .php under /uploads/.' => 'Wie via een lek in een plugin een bestand in uploads krijgt, kan code op de server uitvoeren. Blokkeer PHP in de uploadsmap in de webserver, voor nginx met een location-blok dat 403 geeft voor .php onder /uploads/.',
	],
];
