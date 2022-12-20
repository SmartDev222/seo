'use strict';

/**
 * Initializes the tools page editor when applicable.
 */
function initToolsEditor() {
	if (typeof toolsEditor !== 'undefined') {
		toolEditor();
	}
}

function toolEditor() {
	var editor = $('.editor');

	// State
	var dragging = null;
	var accepted = false;

	/**
	 * Updates the values of the inputs.
	 */
	function updateToolEditorInputs() {
		$("input[name=tools]").val(JSON.stringify(tools)).trigger('change');
		$("input[name=categories]").val(JSON.stringify(categories)).trigger('change');
	}

	/**
	 * Renders the editor.
	 */
	function renderEditor() {
		editor.html('');

		$.each(categories, function(catIndex, category) {
			var toolList = tools[category.token];
			var tileId = 0;
			var blank = 0;
			var minTileCount = 3;
			var extraTileCount = 3;

			// Escape category token for insertion
			var token = category.token.replace(/"/g, '\\"');

			// Build the name HTML
			var nameHTML = '';
			for (var locale in category.names) {
				var text = category.names[locale];
				nameHTML += `<input class="language-specific" data-locale="${locale}" type="text" value="${text.replace(/"/g, '\\"')}">`;
			}

			// Build the description HTML
			var descriptionHTML = '';
			for (var locale in category.names) {
				var text = category.descriptions[locale];
				descriptionHTML += `<textarea class="language-specific" data-locale="${locale}" rows="1" placeholder="Enter a description of the category...">${text}</textarea>`;
			}

			// Build initial HTML
			var html = (`
				<div class="category" data-pos="${catIndex}">
					<div class="title">
						${nameHTML}
						${descriptionHTML}
						<div class="control">
							<a class="up"><i class="material-icons">keyboard_arrow_up</i></a>
							<a class="down"><i class="material-icons">keyboard_arrow_down</i></a>
							<a class="delete"><i class="material-icons">delete_forever</i></a>
						</div>
					</div>
					<div class="tiles">
			`);

			$.each(toolList, function(toolIndex, tool) {
				tileId++;

				if (tool !== 0 && typeof tool !== 'undefined') {
					var id = tool[0];
					var icon = tool[1];
					var name = tool[2];

					var escapedName = name.replace('<', '&lgt;').replace('>', '&gt;');

					// Append the HTML for this tool
					html += (`
						<div class="tile" data-cat="${token}" data-tileid="${tileId}" data-pos="${toolIndex}">
							<div class="tool-item" data-tool="${id}">
								<img src="${icon}" />
								<span>${escapedName}</span>
							</div>
						</div>
					`);
				}
				else {
					blank++;
					html += (`
						<div class="tile" data-cat="${token}" data-tileid="${tileId}" data-pos="${toolIndex}">
						</div>
					`);
				}
			});

			// Add some blank tiles at the end for easier drag-and-drop
			for (var i = tileId; i < minTileCount; i++) {
				tileId++;
				html += (`
					<div class="tile" data-cat="${token}" data-tileid="${tileId}">
					</div>
				`);
			}

			// Add some blank tiles at the end for easier drag-and-drop
			for (var i = 0; i < extraTileCount; i++) {
				tileId++;
				html += (`
					<div class="tile is-empty" data-cat="${token}" data-tileid="${tileId}">
					</div>
				`);
			}

			// Append to the editor
			editor.append(html + '</div>');
		});

		// Add a button to create a new category
		editor.append(`
			<div class="new-section">
				<a class="btn">Add another section</a>
			</div>
		`);
	}

	function optimize(tools) {
		if (!tools) return;

		let lastIndex = 0;

		for (var i = 0; i < tools.length; i++) {
			if (tools[i]) {
				lastIndex = i;
			}
		}

		return tools.slice(0, lastIndex + 1);
	}

	/**
	 * Initializes the drag and drop functionality.
	 */
	function initDragDrop() {
		var toolItems = $('.tool-item');
		var tiles = $('.category .tile');
		var drawers = $('.category');

		// Define which elements can be dragged
		toolItems.draggable({
			zIndex: 10000,
			helper: 'clone',
			refreshPositions: true,
			start: function(event, ui) {
				var item = toolItems.filter(this);
				item.parent().addClass('active');
				editor.addClass('is-dragging');
				ui.helper.css({ position: 'fixed' });
				dragging = item;
			},
			drag: function(event, ui) {
				ui.position.left = event.clientX - Math.floor(ui.helper.outerWidth() / 2);
				ui.position.top = event.clientY - Math.floor(ui.helper.outerHeight() / 2) + 25;
			},
			stop: function() {
				var item = toolItems.filter(this);
				item.parent().removeClass('active');
				editor.removeClass('is-dragging');

				// For unsuccessful placements, revert back
				if (!accepted) {
					item.css({
						top: 'auto',
						left: 'auto',
						right: 'auto',
						bottom: 'auto'
					});
				}
				else {
					dragging.remove();
				}
			}
		});

		// Define where elements can be dropped
		tiles.droppable({
			hoverClass: 'hover',
			drop: function() {
				var tile = tiles.filter(this);
				var index = tile.data('tileid') - 1;
				var cat = tile.data('cat');
				var draggingParent = dragging.parent();

				if (draggingParent.data('tileid') !== '') {
					var dindex = draggingParent.data('tileid') - 1;
					var dcat = draggingParent.data('cat');
					var me = tools[dcat][dindex].slice();

					if (typeof tools[cat][index] !== "undefined" && tools[cat][index] !== 0) {
						var you = tools[cat][index].slice();

						tools[cat][index] = me;
						tools[dcat][dindex] = you;
						accepted = true;
					}
					else {
						tools[cat][index] = me;
						tools[dcat][dindex] = 0;
						accepted = true;
					}

					tools[cat] = optimize(tools[cat]);
					tools[dcat] = optimize(tools[dcat]);
					updateEditor();
				}
				else {
					var tid = draggingParent.data('m-toolid');
					var tname = draggingParent.data('m-toolname');
					var ticon = draggingParent.data('m-toolicon');

					tools[cat][index] = [tid, ticon, tname, true];
					accepted = true;

					tools[cat] = optimize(tools[cat]);
					tools[dcat] = optimize(tools[dcat]);
					updateEditor();
				}
			}
		});

		drawers.droppable({
			hoverClass: 'is-hovered',
			tolerance: 'touch',
			greedy: true,
			drop: function() {

			}
		});
	}

	function initCheckboxes() {
		var inputs = $('.tool-item input');

		inputs.on('change', function(event) {
			if (this.name === 'enabled') {
				var input = inputs.filter(this);
				var tile = input.parent().parent().parent().parent();
				var toolCategoryName = tile.data('cat');
				var toolIndex = tile.data('pos');
				var toolEnabled = input.is(':checked');

				tools[toolCategoryName][toolIndex][3] = toolEnabled;
			}
		});
	}

	/**
	 * Handles delete buttons.
	 */
	function initDeleteButtons() {
		var deleteButtons = $('.title .delete');

		deleteButtons.each(function() {
			var button = deleteButtons.filter(this);
			var id = parseInt(button.parent().parent().parent().data('pos'));
			var cat = categories[id];

			button.on('click', function() {
				var isEmpty = true;

				$.each(tools[cat.token], function(i, v) {
					if (v !== 0 && typeof v !== "undefined") {
						isEmpty = false;
					}
				});

				if (!isEmpty) {
					alert(
						'You cannot delete this section because it is not empty!\n' +
						'Please move all tools to other sections before deleting.'
					);
				}
				else {
					delete tools[cat];
					categories.splice(id, 1);

					updateEditor();
				}
			});
		});
	}

	/**
	 * Handles up buttons.
	 */
	function initUpButtons() {
		var upButtons = $('.title .up');

		upButtons.each(function() {
			var button = upButtons.filter(this);
			var id = parseInt(button.parent().parent().parent().data('pos'));
			var cat = categories[id];

			button.on('click', function() {
				if (id > 0) {
					var swapWith = categories[id - 1];
					categories[id - 1] = cat;
					categories[id] = swapWith;

					updateEditor();
				}
			});
		});
	}

	/**
	 * Handles down buttons.
	 */
	function initDownButtons() {
		var downButtons = $('.title .down');

		downButtons.each(function() {
			var button = downButtons.filter(this);
			var id = parseInt(button.parent().parent().parent().data('pos'));
			var cat = categories[id];

			button.on('click', function() {
				if (id < (categories.length - 1)) {
					var swapWith = categories[id + 1];

					if (typeof swapWith !== 'undefined') {
						categories[id + 1] = cat;
						categories[id] = swapWith;

						updateEditor();
					}
				}
			});
		});
	}

	/**
	 * Implements the "add another section" button.
	 */
	function initNewSectionButton() {
		$('.new-section a').on('click', function() {
			var names = {};
			var descriptions = {};

			for (var locale of languages) {
				names[locale] = "Untitled #" + (categories.length + 1);
			}

			for (var locale of languages) {
				descriptions[locale] = '';
			}

			var nextId = categories.length + 1;

			while (('Cat' + nextId) in tools) {
				nextId++;
			}

			categories.push({
				token: "Cat" + nextId,
				names: names,
				descriptions: descriptions
			});

			tools["Cat" + nextId] = [];

			updateEditor();
		});
	}

	/**
	 * Tracks category name changes and records them.
	 */
	function initTitleInputs() {
		var inputs = $('.title input');

		inputs.on('change keyup', function() {
			var input = inputs.filter(this);
			var newName = input.val();
			var id = parseInt(input.parent().parent().data('pos'));
			var category = categories[id];
			var locale = input.data('locale');

			category.names[locale] = newName;

			updateToolEditorInputs();
		});
	}

	/**
	 * Tracks category name changes and records them.
	 */
	function initDescriptionInputs() {
		var inputs = $('.title textarea');

		inputs.on('change keyup', function() {
			var input = inputs.filter(this);
			var newDesc = input.val();
			var id = parseInt(input.parent().parent().data('pos'));
			var category = categories[id];
			var locale = input.data('locale');

			category.descriptions[locale] = newDesc;

			updateToolEditorInputs();
		});
	}

	/**
	 * Redraws the editor and binds events.
	 */
	function updateEditor() {
		renderEditor();

		initDragDrop();
		initDeleteButtons();
		initUpButtons();
		initDownButtons();
		initNewSectionButton();
		initTitleInputs();
		initDescriptionInputs();
		initCheckboxes();

		updateToolEditorInputs();
		applyLanguage();
	}

	updateEditor();
}

// Call initializers
initToolsEditor();
