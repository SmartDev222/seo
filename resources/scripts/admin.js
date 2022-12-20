'use strict';

/**
* Initializes the ON-OFF switches to their respective states and binds to their click events.
*/
function initSwitches() {
	var switches = $('.switch, .toggle');
	var switchLabels = $('label[data-switch]');

	switches.each(function() {
		var switchElement = switches.filter(this);
		var switchInput = switchElement.find('input[type=hidden]').first();
		var childSwitch = switchElement.find('.switch');

		if (childSwitch.length > 0) {
			return;
		}

		if (switchInput.length > 0) {
			if (switchInput.val() === 'On') {
				switchElement.addClass('on');
			}

			switchElement.on('click', function(event) {
				if (switchInput.val() === 'On') {
					switchInput.val('Off');
					switchElement.removeClass('on');
				}
				else {
					switchInput.val('On');
					switchElement.addClass('on');
				}

				switchInput.trigger('change');
				event.stopPropagation();
			});
		}
	});

	switchLabels.each(function() {
		var switchLabel = switchLabels.filter(this);
		var switchId = switchLabel.data('switch');
		var switchElement = switchId !== undefined ?
		switches.filter('#' + switchId) :
		switchLabel.find('.switch, .toggle');

		if (switchElement.length > 0) {
			switchLabel.on('click', function(event) {
				switchElement.trigger('click');
				event.stopPropagation();
			});
		}
	});
}

/**
* Initializes tooltips for timestamps throughout the admin panel.
*/
function initTimeTooltips() {
	var timestampElements = $('.time');

	timestampElements.on('mouseover', function(event) {
		var timestampElement = timestampElements.filter(this);
		var timestampIcon = timestampElement.find('span');

		if (!timestampElement.data('tooltip-active')) {
			var date = new Date(parseInt(timestampIcon.data('time')) * 1000);
			var dateLabel = date.toLocaleDateString();
			var timeLabel = date.toLocaleTimeString();

			timestampElement.data('tooltip-active', true);
			timestampIcon.append("<div class=\"tooltip\">" + dateLabel + " " + timeLabel + "</div>");
		}
	});

	timestampElements.on('mouseout', function(event) {
		// Filter children for performance boost
		if (event.target !== this && event.target.parentElement.parentElement !== this) {
			return;
		}

		var timestampElement = timestampElements.filter(this);
		var timestampIcon = timestampElement.find('span');

		if (timestampElement.data('tooltip-active')) {
			timestampIcon.find('.tooltip').remove();
			timestampElement.data('tooltip-active', false);
		}
	});
}

/**
* Initializes utilities for form inputs.
*/
function initInputHelpers() {
	// Text inputs that automatically select all of their text on focus
	var selectAllInputs = $('input.select-all');
	selectAllInputs.on('focus', function() {
		selectAllInputs.filter(this).select();
	});
}

/**
* Initializes utilities for app language management.
*/
function initLanguageHelpers() {
	// Buttons to switch between raw json and the form editor
	var translationGroupElements = $('.translation-group');
	translationGroupElements.each(function() {
		var groupElement = translationGroupElements.filter(this);
		var button = groupElement.find('.raw');
		var formElement = groupElement.find('.translate');
		var codeElement = groupElement.find('.code-editor');

		button.on('click', function(event) {
			if (!button.data('is-raw-editor')) {
				button.html('Editor');

				formElement.hide();
				codeElement.removeClass('hidden');
			}
			else {
				button.html('Raw');

				formElement.show();
				codeElement.addClass('hidden');
			}

			button.data('is-raw-editor', !button.data('is-raw-editor'));
			event.stopPropagation();
		});
	});
}

/**
* Initializes the sign-in button.
*/
function initEnvatoButton() {
	$('.envatoSigninButton').on('click', function() {
		var redirectTo = window.location.href;
		redirectTo = redirectTo.substring(0, redirectTo.indexOf('admin'));
		redirectTo += 'admin/envato.php';

		window.location = "https://api.getseostudio.com/v1/authorization?method=connect&redirect_uri=" + encodeURIComponent(redirectTo);
	});
}

/**
* Writes the current URL to purchase verification detail boxes.
*/
function initPurchaseVerificationDetails() {
	$('.pvdetails').html(window.location.href);
}

/**
 * Initializes the new "smart saving" feature which intelligently tracks form inputs and offers asynchronous saving in
 * the background. This is an advanced feature, edit with caution!
 */
function initSmartSaving() {
	/**
	 * Ckeditor element cache.
	 */
	var ckEditors = {};
	var saveContainers = $('.save-container');
	var ckEditorInitQueue = {};

	/**
	 * Resolves the callback when the specified editor is loaded.
	 *
	 * @param {string} editorId
	 * @param {Function} callback
	 * @returns
	 */
	function awaitCKEditor(editorId, callback) {
		if (editorId in ckEditors) {
			return ckEditors[editorId];
		}

		if (editorId in CKEDITOR.instances) {
			ckEditors[editorId] = CKEDITOR.instances[editorId];
			return callback(CKEDITOR.instances[editorId]);
		}

		ckEditorInitQueue[editorId] = callback;
	}

	// Listen for editors as they load
	if (typeof CKEDITOR !== 'undefined' && CKEDITOR.on) {
		CKEDITOR.on('instanceReady', function(event) {
			const id = event.editor.element.$.id;

			if (id in ckEditorInitQueue) {
				ckEditors[id] = event.editor;
				ckEditorInitQueue[id](event.editor);
				delete ckEditorInitQueue[id];
			}
		});
	}

	/**
	 * Returns the current state of a form.
	 *
	 * @param {HTMLFormElement} form
	 * @param {boolean} lowercase Lowercase string values
	 * @returns Object
	 */
	function getFormState(form, lowercase = false) {
		var state = {};
		var elements = form.find('input[name], select[name], button[name], textarea');
		var editorTextAreas = form.find('.ckeditor');
		var names = [];

		elements.each(function() {
			var element = $(this);
			var name = element.attr('name');
			var value = element.val();

			if (element.is('[type=checkbox]')) {
				value = element.is(':checked') ? value : undefined;
			}

			if (names.indexOf(name) >= 0) {
				console.error('Duplicate input name on this page:', name);
			}

			names.push(name);
			state[name] = value;

			if (lowercase && typeof state[name] === 'string') {
				state[name] = state[name].toLowerCase();
			}
		});

		editorTextAreas.each(function() {
			var textArea = editorTextAreas.filter(this);
			var textAreaId = textArea.attr('id');
			var textAreaName = textArea.attr('name');

			if (textAreaId in ckEditors) {
				state[textAreaName] = ckEditors[textAreaId].getData();
			}
		});

		return state;
	}

	/**
	 * Returns `true` if the given two states are identical.
	 *
	 * @param {Object} oldState
	 * @param {Object} newState
	 * @returns boolean
	 */
	function areStatesIdentical(oldState, newState) {
		for (var name in oldState) {
			if (newState[name] != oldState[name]) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Listens for changes to the form's state and invokes the given `fn` with the new state. If the state is changed back
	 * to its initial form, the state object is `null`.
	 *
	 * @param {HTMLFormElement} form
	 * @param {string[]} editors
	 * @param {Function} fn
	 * @param {boolean} lowercase
	 */
	function listenFormState(form, editors, fn, lowercase) {
		var elements = form.find('input[name], select[name], button[name], textarea');
		var state = getFormState(form, lowercase);

		function recheckFormState() {
			var newState = getFormState(form, lowercase);

			if (!areStatesIdentical(state, newState)) fn(newState);
			else fn(null);
		};

		elements.on('change keyup paste', function() {
			recheckFormState();
		});

		for (var id of editors) {
			awaitCKEditor(id, function(editor) {
				editor.on('change', function() {
					recheckFormState();
				});
			});
		}

		return function(newState) {
			state = newState;
			newState = getFormState(form, lowercase);

			if (!areStatesIdentical(state, newState)) fn(newState);
			else fn(null);
		};
	}

	/**
	 * Updates the value of editor textareas.
	 *
	 * @param {HTMLFormElement} form
	 * @returns void
	 */
	function applyEditorValues(form) {
		var editorTextAreas = form.find('.ckeditor');

		editorTextAreas.each(function() {
			var textArea = editorTextAreas.filter(this);
			var textAreaId = textArea.attr('id');
			var textAreaName = textArea.attr('name');

			if (textAreaId in ckEditors) {
				textArea.val(ckEditors[textAreaId].getData());
			}
		});
	}

	saveContainers.each(function() {
		var container = saveContainers.filter(this);
		var form = container.closest('form');
		var submitButton = container.find('.save button[type=submit]');
		var trackingState, savedAnimationTimeout;
		var isCaseInsensitive = container.find('.saveable.case-insensitive').length > 0;

		// Try to find the form and halt if we can't
		if (!form.length) form = container.find('form');
		if (!form.length) return;

		var isFixed = container.hasClass('fixed');
		var isPost = container.hasClass('post');

		var editorTextAreas = form.find('.ckeditor');
		var editorsToTrack = [];

		editorTextAreas.each(function() {
			var textArea = editorTextAreas.filter(this);
			editorsToTrack.push(textArea.attr('id'));
		});

		// Listen for changes to the form state
		var updateState = listenFormState(form, editorsToTrack, function(state) {
			trackingState = state;

			if (state) {
				// The form has been changed
				submitButton.removeClass('saved');
				submitButton.removeClass('successful-save');

				if (savedAnimationTimeout) {
					clearTimeout(savedAnimationTimeout);
				}
			}
			else {
				// The form has been reset to its initial state
				if (!isFixed) {
					submitButton.addClass('saved');
				}
			}
		}, isCaseInsensitive);

		if (!isPost) {
			// Handle save action
			form.on('submit', function() {
				if (submitButton.hasClass('saved')) return;
				if (submitButton.hasClass('saving')) return;
				if (!trackingState) return;

				var savingState = trackingState;
				submitButton.addClass('saving');

				if (demoMode) {
					setTimeout(function() {
						submitButton.removeClass('saving');
						updateState(savingState);

						submitButton.addClass('successful-save');

						savedAnimationTimeout = setTimeout(function() {
							console.log('ok');
							submitButton.removeClass('successful-save');
						}, 2000);
					}, 1200);

					return;
				}

				if (Object.keys(ckEditors).length > 0) {
					applyEditorValues(form);
				}

				$.ajax({
					url: window.location.href,
					type: 'POST',
					data: new FormData(form[0]),
					processData: false,
					contentType: false
				})
				.done(function(body, status, res) {
					if (res.status != 200) {
						// There was an error saving, so let's submit the form natively
						form.attr('action', '');
						trackingState = null;
						return form.submit();
					}

					var $b = $('<div class="parse">' + body.replace(/^[\s\S]*<body.*?>|<\/body>[\s\S]*$/ig, '') + '</div>');
					var wasSuccessful = $b.find('.body').find('.success').length > 0;

					if (wasSuccessful) {
						submitButton.removeClass('saving');
						updateState(savingState);

						submitButton.addClass('successful-save');

						savedAnimationTimeout = setTimeout(function() {
							console.log('ok');
							submitButton.removeClass('successful-save');
						}, 2000);
					}
					else {
						var errorMessage = $b.find('.error').find('i').remove().end().text().trim();

						if (errorMessage.length > 0) {
							alert('Error:\n' + errorMessage);
							submitButton.removeClass('saving');
						}
						else {
							// Unknown error, so post natively
							form.attr('action', '');
							trackingState = null;
							return form.submit();
						}
					}
				}).fail(function() {
					form.attr('action', '');
					trackingState = null;
					return form.submit();
				});
			});

			form.attr('action', 'javascript: void(0);');
			submitButton.addClass('saved');
		}

		window.addEventListener('beforeunload', function(e) {
			if (trackingState && !isPost) {
				var confirmation = 'You have unsaved changes. Are you sure you want to leave?';
				(e || window.event).returnValue = confirmation;
				return confirmation;
			}
		});
	});
}

function initBootstrapTooltips() {
	var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
	var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
		return new bootstrap.Tooltip(tooltipTriggerEl)
	});
}

function initTabLinking() {
	var url = location.href.replace(/\/$/, "");

	if (location.hash) {
		var hash = url.split('#')[1];
		var tab = $('.nav-link[data-bs-target="#' + hash + '"]');

		if (tab.length > 0) {
			tab.trigger('click');
		}
	}
}

// Call initializers
initSwitches();
initTimeTooltips();
initInputHelpers();
initLanguageHelpers();
initEnvatoButton();
initPurchaseVerificationDetails();
initSmartSaving();
initBootstrapTooltips();
initTabLinking();
