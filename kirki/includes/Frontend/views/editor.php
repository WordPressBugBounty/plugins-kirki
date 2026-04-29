<?php

/**
 * Editor template php file.
 *
 * @package kirki
 */

if (! defined('ABSPATH')) {
	exit;
}

use Kirki\HelperFunctions;

$load_iframe_url = HelperFunctions::get_post_url_arr_from_post_id(HelperFunctions::get_post_id_if_possible_from_url(), array('iframe_url' => true))['iframe_url'];
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>
		<?php echo esc_html_e('Kirki', 'kirki') . ' | ' . esc_html(get_the_title(HelperFunctions::get_post_id_if_possible_from_url())); ?>
	</title>
	<?php wp_head(); ?>
</head>

<body class="kirki-tool">
	<div id="kirki-root"></div>
	<div id="kirki-editor-wrapper">
		<div id="kirki-top-bar"></div>
		<div id="kirki-left-bar"></div>
		<div id="kirki-content-bar">
			<div id="kirki-responsive-devices"></div>
			<div id="kirki-front-overlay"></div>
		</div>
		<div id="kirki-right-bar"></div>
		<div id="kirki-footer-bar"></div>
		<div id="kirki-floating-elems">
			<div id="kirki-alert-dialog-anchor-ele"></div>
		</div>
	</div>

	<div id="kirki-loadingDiv"
		style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; display: flex; justify-content: center;  overflow: hidden; z-index: 9;">
		<div class="kirki-loading-left-side" style="background: var(--kirki-surface-1); flex: 0 0 304px;">
			<div style="border-right: 1px solid var(--kirki-border-secondary); height: 100%; width: 48px;"></div>
		</div>
		<div style="flex: 1; display: flex; background: var(--kirki-surface-2); justify-content: center;     align-items: center;">
		<div class="kirki-loading-wrapper"
			style="width: 256px; height: 62px; display: flex ; justify-content: center; flex-direction: column; align-items: center; gap: 24px; overflow: hidden; margin-top: -5px;">
			
			<img src="<?php echo esc_url(KIRKI_PLUGIN_URL . '/assets/images/kirki-loading.svg'); ?>" alt="Kirki" style="height: 32px;">

			<div class="kirki-loading"
				style="height: 8px; border-radius: 10px; width: 100%; background: var(--kirki-surface-3); position: relative">
				<div class="kirki-loading-overlay"
					style="width: 2%; position: absolute; left: 0; top: 0; bottom: 0; background: var(--kirki-on-surface-3); transition: width 3s ease; border-radius: 10px;">

				</div>
			</div>
		</div>
		</div>
		<div class="kirki-loading-right-side" style="background: var(--kirki-surface-1); flex: 0 0 256px;"></div>
	</div>
	<script>
		const loadingOverlay = document.querySelector('.kirki-loading-overlay');
		let loadingDiv = document.getElementById("kirki-loadingDiv");

		const loading = (parentage) => {
			loadingOverlay.style.width = parentage + '%';
		}
		window.loading = loading;
		window.removeLoading = () => {
			setTimeout(() => {
				loadingDiv?.remove();
			}, 2000);
		}

		const loadingInterval = setInterval(() => {
			let parentage = parseInt(loadingOverlay.style.width);
			let random = Math.floor(Math.random() * 5) + 1;

			if (parentage < 85) {
				loading(parentage + random);
			} else {
				clearInterval(loadingInterval);
			}
		}, 50);

		let mode = localStorage.getItem(`kirkiMode`) || 'dark';
		document.documentElement.setAttribute('data-mode', mode);


		if (mode === 'dark') {
			loadingDiv.style.backgroundColor = '#1d1d1d';
		} else {
			loadingDiv.style.backgroundColor = '#FFFFFF';
		}
	</script>
	</div>

	<?php wp_footer(); ?>
</body>

</html>