<?php

if ( class_exists('Puc_v4_Theme_UpdateChecker', false) ):

	class Puc_v4_Theme_UpdateChecker extends Puc_v4_UpdateChecker {
		protected $filterPrefix = 'tuc_';

		/**
		 * @var string Theme directory name.
		 */
		protected $stylesheet;

		/**
		 * @var WP_Theme Theme object.
		 */
		protected $theme;

		public function __construct($metadataUrl, $stylesheet = null, $customSlug = null, $checkPeriod = 12, $optionName = '') {
			if ( $stylesheet === null ) {
				$stylesheet = get_stylesheet();
			}
			$this->stylesheet = $stylesheet;
			$this->theme = wp_get_theme($this->stylesheet);

			parent::__construct($metadataUrl, $customSlug ? $customSlug : $stylesheet);
		}

		/**
		 * Retrieve the latest update (if any) from the configured API endpoint.
		 *
		 * @return Puc_v4_Update An instance of Update, or NULL when no updates are available.
		 */
		public function requestUpdate() {
			//Query args to append to the URL. Themes can add their own by using a filter callback (see addQueryArgFilter()).
			$queryArgs = array();
			$installedVersion = $this->getInstalledVersion();
			$queryArgs['installed_version'] = ($installedVersion !== null) ? $installedVersion : '';

			$queryArgs = apply_filters($this->filterPrefix . 'request_update_query_args-' . $this->slug, $queryArgs);

			//Various options for the wp_remote_get() call. Plugins can filter these, too.
			$options = array(
				'timeout' => 10, //seconds
				'headers' => array(
					'Accept' => 'application/json'
				),
			);
			$options = apply_filters($this->filterPrefix . 'request_update_options-' . $this->slug, $options);

			$url = $this->metadataUrl;
			if ( !empty($queryArgs) ){
				$url = add_query_arg($queryArgs, $url);
			}

			$result = wp_remote_get($url, $options);

			//Try to parse the response
			$status = $this->validateApiResponse($result);
			$themeUpdate = null;
			if ( !is_wp_error($status) ){
				$themeUpdate = Puc_v4_Theme_Update::fromJson($result['body']);
				if ( $themeUpdate !== null ) {
					$themeUpdate->slug = $this->slug;
				}
			} else {
				$this->triggerError(
					sprintf('The URL %s does not point to a valid theme metadata file. ', $url)
					. $status->get_error_message(),
					E_USER_WARNING
				);
			}

			$themeUpdate = apply_filters(
				$this->filterPrefix . 'request_update_result-' . $this->slug,
				$themeUpdate,
				$result
			);
			return $themeUpdate;
		}

		/**
		 * Get the currently installed version of the plugin or theme.
		 *
		 * @return string Version number.
		 */
		public function getInstalledVersion() {
			return $this->theme->get('Version');
		}
	}

endif;