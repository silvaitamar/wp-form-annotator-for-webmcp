/**
 * Ninja Forms: annotate <form> and fields after Backbone render (nfFormReady).
 */
(function () {
	'use strict';

	var configs = window.siwmfaNinja || {};

	function cssEscape(value) {
		if (window.CSS && typeof window.CSS.escape === 'function') {
			return window.CSS.escape(value);
		}
		return String(value).replace(/["\\]/g, '\\$&');
	}

	function annotateOne(formId, config) {
		var cont = document.getElementById('nf-form-' + formId + '-cont');
		if (!cont || !config) {
			return;
		}

		var form = cont.querySelector('form');
		if (!form) {
			return;
		}

		form.setAttribute('toolname', config.toolname || '');
		form.setAttribute('tooldescription', config.tooldescription || '');

		var params = config.params || {};
		Object.keys(params).forEach(function (name) {
			var el = form.querySelector('[name="' + cssEscape(name) + '"]');
			if (!el) {
				el = form.querySelector('[name="' + name + '"]');
			}
			if (el) {
				el.setAttribute('toolparamdescription', params[name]);
			}
		});
	}

	function run() {
		Object.keys(configs).forEach(function (id) {
			annotateOne(id, configs[id]);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', run);
	} else {
		run();
	}

	if (window.jQuery) {
		window.jQuery(document).on('nfFormReady', run);
	}

	try {
		var obs = new MutationObserver(run);
		obs.observe(document.documentElement, { childList: true, subtree: true });
		window.setTimeout(function () {
			obs.disconnect();
		}, 20000);
	} catch (e) {
		// ignore
	}
})();
