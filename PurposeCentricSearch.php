
<?php

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
  'path' => __FILE__,
	'name' => 'PurposeCentricSearch',
	'author' => 'Madhuri Jadhav, Nidhi Zanwar, Priya Wagh',
	'url' => 'https://www.mediawiki.org/wiki/Extension:PurposeCentricSearch',
	'version' => '1.0',
	'descriptionmsg' => 'Purpose based searching',
);

// Autoload the new classes and set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['PurposeCentricSearch'] = $dir . 'PurposeCentricSearch.i18n.php';
$wgExtensionMessagesFiles['PurposeCentricSearchAlias'] = $dir . 'PurposeCentricSearch.alias.php';
$wgAutoloadClasses['PurposeCentricSearch'] = $dir . 'PurposeCentricSearch.body.php';
$wgSpecialPages['PurposeCentricSearch'] = 'PurposeCentricSearch';


// Hooked functions
$wgHooks['LoadExtensionSchemaUpdates'][] ='PurposeCentricSearchSchemaUpdate';
$wgHooks['EditPageBeforeEditToolbar'][] = 'PurposeCentricSearch::LevelInToolbar';
$wgHooks['ArticleSaveComplete'][] = 'PurposeCentricSearch::savepage';
$wgHooks['GetPreferences'][] = 'PurposeCentricSearch::preferences';


function PurposeCentricSearchSchemaUpdate( DatabaseUpdater $updater) {
	$dir = dirname( __FILE__ ) . '/';
	$updater->addExtensionTable( 'expertise', $dir. 'updateDB.sql', true );
	$updater->addExtensionField( 'page', 'page_expertise', $dir.'updateDB.sql', true );
	return true;
}

