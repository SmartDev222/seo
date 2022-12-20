'use strict';

/**
 * Initializes the navigation button on small screens.
 */
function initNavButton() {
    var button = $('header .toggle');
    var nav = $('header nav');

    button.on('click', function() {
        if (nav.hasClass('visible')) {
            nav.stop().slideUp(200);
        }
        else {
            nav.stop().slideDown(200);
        }

        nav.toggleClass('visible');
    });
}

/**
 * Initializes the website switcher dropdown component.
 */
function initWebsiteSwitcher() {
    var selectElement = $('.site-selector .select');
    var dropDownElement = selectElement.find('.dropdown');
    var dropDownLink = dropDownElement.find('li a');
    var dropDownEnabled = false;
    var isSwitching = false;

    selectElement.on('mousedown', function(event) {
        event.preventDefault();
        event.stopPropagation();
    });

    selectElement.on('click', function(event) {
        if (dropDownEnabled) {
            dropDownElement.stop().slideUp('fast');
        }
        else {
            dropDownElement.stop().slideDown('fast');
        }

        dropDownEnabled = !dropDownEnabled;
        event.stopPropagation();
    });

    dropDownLink.on('click', function(e) {
        if (isSwitching) {
            e.preventDefault();
            return;
        }

        isSwitching = true;
        dropDownLink.addClass('working');
        e.stopPropagation();
    });
}

/**
 * Hides errors after a short time after the page finishes loading.
 */
function initErrors() {
    var errors = $('.error');

    if (errors.length > 0) {
        $(function() {
            setTimeout(function() {
                errors.fadeOut();
            }, 6000);
        });
    }
}

/**
 * Initializes sticky table headers and their scroll to top buttons.
 */
function initStickyTables() {
    var $window = $(window);
    var $document = $(document);
    var tables = $('table[data-sticky]');
    var stickyId = '';

    $window.on('scroll', function() {
        tables.each(function() {
            var table = tables.filter(this);
            var id = table.data('sticky');

            if ($document.scrollTop() > table.offset().top) {
                if (stickyId !== id) {
                    stickyId = id;

                    // @nocache – rarely triggered, minimal perf hit
                    $('#' + stickyId).show();
                }
            }

            // @nocache – rarely triggered, minimal perf hit
            else if (stickyId === id) {
                $("#" + stickyId).hide();
                stickyId = "";
            }
        });
    });

    // Scroll to top buttons
    $('.sticky .up').on('click', function() {
        $('html, body').animate({ scrollTop: '0' }, 'fast');
    });
}

/**
 * Adds spinners when submit buttons are clicked.
 */
function initSubmitLoaders() {
    var formSubmitButtons = $('.form-container input[type=submit]');

    formSubmitButtons.on('click', function() {
        setTimeout(function() {
            var button = formSubmitButtons.filter(this);
            button.parent().html(`<img src="${path}resources/images/load32.gif" alt="Loading" width="16px" />`);
        }, 20);
    });
}

/**
 * Adds spinners to tool icons when clicked.
 */
function initToolIconAnimations() {
    var toolButtons = $('.category .tool');

    toolButtons.on('click', function(e) {
        var button = toolButtons.filter(this);

        if (button.hasClass('is-loading')) {
            e.preventDefault();
            return;
        }

        button.addClass('is-loading');
        button.find('.tool-loader').show().html('<img src="resources/images/b-load32.gif" />');
    });
}

/**
 * Adds an apply button to the site selector.
 */
function initApplyButtons() {
    $('.site-selector').each(function() {
        var selector = $(this);
        var apply = selector.find('.apply');
        var textbox = selector.find('[name=site]');

        textbox.on('change keyup', function() {
            apply.removeClass('hidden');
        });
    });
}

/**
 * Implements the language dropdown menu.
 */
function initLanguageBar() {
    var dropdowns = $('.language-bar .dropdown');
    var languageMenu = $('.language-menu');

    dropdowns.on('click', function() {
        var dropdown = dropdowns.filter(this);

        if (dropdown.hasClass('active')) {
            dropdown.removeClass('active');
            languageMenu.hide();
        }
        else {
            dropdown.addClass('active');
            languageMenu.show();
        }
    });
}

/**
 * Implements the submit button on the "Submit Sitemaps" tool.
 */
function initSitemapSubmit() {
    if (typeof submitSitemaps !== 'undefined') {
        var submitButton = $('.submit');

        submitButton.on('click', function() {
            var td = submitButton.parent();
            var id = submitButton.data('id');
            var sec = submitButton.data('sec');

            td.html(`<img src="${path}resources/images/load32.gif" alt="Loading" width="16px" />`);

            $.get(window.location + `&sitemap=${id}&sec=${sec}`, function(data) {
                td.html(`<img src="${path}resources/images/check32.png" alt="Submitted" width="16px" />`);
            });
        });
    }
}

/**
 * Implements spinners when buttons are clicked.
 */
function initLoadableButtons() {
    var loadables = $('.loadable');
    var loaderElement = document.getElementById('loader');

    if (!loaderElement && loadables.length > 0) {
        console.error('Missing #loader element');
        return;
    }

    $.each(loadables, function() {
        var button = loadables.filter(this);

        if (button.is('button') || button.is('a')) {
            button.on('click', function(e) {
                if (button.hasClass('triggered')) {
                    e.preventDefault();
                    return;
                }

                button.addClass('triggered');
                button.prepend(loaderElement.innerHTML);
            });
        }
        else if (button.is('form')) {
            var form = button;
            var submitButton = form.find('button.loadable');

            form.on('submit', function(e) {
                if (submitButton.length === 0) {
                    if (form.hasClass('triggered')) {
                        e.preventDefault();
                        return;
                    }

                    form.addClass('triggered');
                }
            });
        }
        else {
            console.error('Element', button[0], 'is marked as loadable but is not a button or link');
        }
    });
}

// Call initializers
initNavButton();
initWebsiteSwitcher();
initErrors();
initStickyTables();
initSubmitLoaders();
initToolIconAnimations();
initApplyButtons();
initSitemapSubmit();
initLanguageBar();
initLoadableButtons();
