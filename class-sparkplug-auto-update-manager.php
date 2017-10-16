<?php
/**
 * Created by PhpStorm.
 * User: greg.thorson
 * Date: 10/13/17
 * Time: 1:23 PM
 */

//@TODO remove all references to sp_log

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$GLOBALS['logFunction'] = function($msg) {

};

require_once 'plugin-update-checker.php';

class Auto_Update_Manager {
	private $bitbucketBaseUrl = 'https://bitbucket.org/bitbucket_user_name/';

	private $bitbucketConsumerKey = 'key_here';
	private $bitbucketConsumerSecret = 'secret_here';
	private $stableBranchName = 'master';

	private $plugins = [];
	private $updaters =[];

	private $masterPluginFILE;
	private $masterPluginSlug;

	public function __construct($enableAutoUpdates = false, $metaDataURL, $fullPath, $pluginSlug, $interval) {

		$GLOBALS['logFunction']('hidey ho');


		$this->plugins  = glob(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'familyprefix-*' . DIRECTORY_SEPARATOR .'familyprefix-*.php');

		$this->masterPluginSlug = $pluginSlug;
		$this->masterPluginFILE = $fullPath;


		// master plugin enqueue separately first.
		$this->updaters[$this->masterPluginSlug] = Puc_v4_Factory::buildUpdateChecker(
			$metaDataURL,
			$fullPath,
			$pluginSlug,
			$interval
		);

		// lets init checkers for updates for child plugins all child plugins have familyprefix-  starting their names with.
		// may not need this...
		foreach($this->plugins as $pluginFILE){
			$parts = pathinfo($pluginFILE);
			$pluginSlug = $parts['filename'];

			$this->updaters[$pluginSlug] = Puc_v4_Factory::buildUpdateChecker(
				$metaDataURL,
				//$this->bitbucketBaseUrl . $pluginSlug,
				$pluginFILE,
				$pluginSlug
			);

		}


		// lets set all common access settings for updaters

		/*
		foreach($this->updaters as $slug => &$updater){

			$updater->setAuthentication(array(
				'consumer_key' => $this->bitbucketConsumerKey,
				'consumer_secret' => $this->bitbucketConsumerSecret,
			));

			$updater->setBranch( $this->stableBranchName );
		}
		*/
		//@TODO REMOVE NEXT 2 LINES!!!!
		//$timePluginsLastUpdated = get_option("_site_transient_update_plugins['sparkplug-wp-api/sparkplug-wp-api.php']");
		//var_dump_error_log($timePluginsLastUpdated);
		//sp_log("Plugins were last updated at $timePluginsLastUpdated");
		sp_log("checking on auto update: $enableAutoUpdates");
		add_filter('puc_check_now-sparkplug_wp_api', array($this, 'alwaysCheckOnCron'), 10, 3);
		//all this does is **enable** auto updates, it does not cause the autoupdate to happen on the spot
		//wp makes that call if it's been at least 12 hours since the last update
		if( $enableAutoUpdates ){
			add_filter( 'auto_update_plugin', [$this, 'enableAutoUpdatesForPlugins'], 10, 2 );
		}
		// this of course does not work
		//wp_maybe_auto_update();

	}

	public function alwaysCheckOnCron($currentDecision, $lastTimeChecked, $interval) {
		$timeSinceLastCheck = time() - $lastTimeChecked;
		sp_log("the currentDecision is $currentDecision. Last time checked is $lastTimeChecked, which is $timeSinceLastCheck seconds ago.");
		return true;
	}

	public function enableAutoUpdatesForPlugins($update, $item){
		sp_log("attempting an automatic update");
		if( array_key_exists( $item->slug, $this->updaters )) {
			return true;
		}

		return $update;
	}

	public function setLogFunction($logFunction) {
		$GLOBALS['logFunction'] = $logFunction;
	}
}