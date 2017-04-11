/* global DY, console */

(function(exports, d) {
    "use strict";

    function domReady(fn, context) {

        function onReady(event) {
            d.removeEventListener("DOMContentLoaded", onReady);
            fn.call(context || exports, event);
        }

        function onReadyIe(event) {
            if (d.readyState === "complete") {
                d.detachEvent("onreadystatechange", onReadyIe);
                fn.call(context || exports, event);
            }
        }

        d.addEventListener && d.addEventListener("DOMContentLoaded", onReady) ||
        d.attachEvent && d.attachEvent("onreadystatechange", onReadyIe);
    }

    exports.domReady = domReady;
})(window, document);

(function(DY, exports, doc) {
    "use strict";

    /**
     * Dynamic Yield Tracking constructor
     *
     * @constructor
     */
    function DynamicYield_Tracking() {
        exports.domReady(function () {
            this.onLoad();
        }.bind(this));
    }

    /**
     * Check if element has class
     *
     * @param element
     * @param cls
     * @returns {boolean}
     */
    function hasClass(element, cls) {
        return (' ' + element.className + ' ').indexOf(' ' + cls + ' ') > -1;
    }

    /**
     * Event handler
     *
     * @param element
     * @param eventType
     * @param handler
     */
    function addEventHandler(element, eventType, handler) {
        if (element.addEventListener) {
            element.addEventListener(eventType, handler, false);
        } else if (element.attachEvent) {
            element.attachEvent('on' + eventType, handler);
        }
    }

    /**
     * Get closest matching parent element
     *
     * @param element
     * @param selector
     * @returns {*}
     */
    function getClosestElement(element, selector) {
        if (!Element.prototype.matches) {
            Element.prototype.matches =
                Element.prototype.matchesSelector ||
                Element.prototype.mozMatchesSelector ||
                Element.prototype.msMatchesSelector ||
                Element.prototype.oMatchesSelector ||
                Element.prototype.webkitMatchesSelector ||
                function(s) {
                    var matches = (this.document || this.ownerDocument).querySelectorAll(s),
                        i = matches.length;
                    while (--i >= 0 && matches.item(i) !== this) {}
                    return i > -1;
                };
        }

        for ( ; element && element !== document; element = element.parentNode ) {
            if ( element.matches( selector ) ) return element;
        }

        return null;

    }

    /**
     * Detects the current page type
     *
     * @returns {*}
     */
    DynamicYield_Tracking.prototype.detectPage = function() {
        var body = doc.getElementsByTagName('body')[0];

        if(hasClass(body, 'catalog-category-view')) {
            return 'category';
        }

        if(hasClass(body, 'catalog-product-view')) {
            return 'product';
        }

        return null;
    };

    /**
     * Sends a request to Dynamic Yield API
     *
     * @param name
     * @param properties
     */
    DynamicYield_Tracking.prototype.callEvent = function(name, properties) {
        console.log(name, properties);
        try {
            DY.API('event', {
                name: name,
                properties: properties
            });
        } catch(e) {
            console.error('DY.API: Event failed', e);
        }
    };

    /**
     * Registers all events based on page
     */
    DynamicYield_Tracking.prototype.onLoad = function() {
        var type = this.detectPage();

        if(type === 'category') {
            var layeredNav = doc.querySelector('#layered-filter-block');
            if (layeredNav !== null) {
                var layeredOptionsWrapper = layeredNav.getElementsByClassName('filter-options-content');

                if (layeredOptionsWrapper !== null && layeredOptionsWrapper.length) {
                    for (var i = 0; i < layeredOptionsWrapper.length; i++) {
                        var layeredLinks = layeredOptionsWrapper[i].getElementsByTagName('a');

                        if (layeredLinks !== null && layeredLinks.length) {
                            for (var l = 0; l < layeredLinks.length; l++) {
                                addEventHandler(layeredLinks[l], 'click', function (event) {
                                    this.onLayeredNavClick(event);
                                }.bind(this));
                            }
                        }
                    }
                }
            }

            var sorter = doc.querySelector('.toolbar-sorter');

            if (sorter !== null) {
                var sorterSelect = sorter.querySelector('select'),
                    sorterLink = sorter.querySelector('a');

                if (sorterSelect !== null) {
                    addEventHandler(sorterSelect, 'change', function (event) {
                        this.onSortChange(event);
                    }.bind(this));
                }

                if (sorterLink !== null) {
                    addEventHandler(sorterLink, 'click', function (event) {
                        this.onSortChange(event);
                    }.bind(this));
                }
            }

        } else if(type === 'product') {
            var optionsWrapper = doc.querySelector('#product-options-wrapper');

            if (optionsWrapper !== null) {
                var regularAttribute = optionsWrapper.querySelector('select.super-attribute-select');

                if (regularAttribute !== null) {
                    addEventHandler(regularAttribute, 'change', function (event) {
                        this.onProductAttributeSelectChange(event);
                    }.bind(this));
                }

                var swatchAttribute = optionsWrapper.querySelector('.swatch-opt');

                if (swatchAttribute !== null) {
                    addEventHandler(swatchAttribute, 'DOMNodeInserted', function (event) {
                        this.onProductSwatchClick(event);
                    }.bind(this));
                }
            }
        }
    };

    /**
     * Handles category filter changes
     *
     * @param event
     */
    DynamicYield_Tracking.prototype.onLayeredNavClick = function(event) {
        var self = event.currentTarget,
            value,
            name,
            filterTitle,
            filterContainer = getClosestElement(self, '.filter-options-item'),
            isSwatchLink = self.querySelector('.swatch-option');

        if (filterContainer === null) {
            return false;
        }

        filterTitle = filterContainer.querySelector('.filter-options-title');

        if (filterTitle === null) {
            return false;
        }

        name = filterTitle.innerText.trim();

        if (isSwatchLink !== null) {
            value = isSwatchLink.getAttribute('data-option-label').trim();
        } else {
            var prices = self.getElementsByClassName('price');

            if (prices !== null && prices.length) {
                value = prices[0].innerText.trim();
            } else {
                var countHtml = self.querySelector('.count');

                if (countHtml !== null) {
                    self.querySelector('.count').remove();
                }

                value = self.innerText.trim();
            }
        }


        if(name !== null && value !== null && value !== "") {
            this.callEvent('Filter Items', {
                dyType: 'filter-items-v1',
                filterType: name,
                filterStringValue: value
            });
        }
    };

    /**
     * Handles swatch based product attribute switcher
     *
     * @param event
     */
    DynamicYield_Tracking.prototype.onProductSwatchClick = function(event) {
        var self = event.currentTarget,
            child = self.querySelector('.swatch-attribute'),
            name = child.querySelector('span.swatch-attribute-label').innerText,
            value = child.querySelector('span.swatch-attribute-selected-option').innerText;

        name = name.trim();

        if(name.substr(-1) === ":") {
            name = name.substr(0, name.length - 1);
        }

        if(name && value) {
            this.callEvent('Change Attribute', {
                dyType: 'change-attr-v1',
                attributeType: name,
                attributeValue: value
            });
        }
    };

    /**
     * Handles select based product attribute switcher
     *
     * @param event
     */
    DynamicYield_Tracking.prototype.onProductAttributeSelectChange = function(event) {
        var self = event.currentTarget,
            parent = getClosestElement(self, '.field.configurable'),
            name = parent.querySelector('label.label').querySelector('span').innerText,
            value = self.options[self.selectedIndex].innerText;

        name = name.trim();

        if (name.substr(-1) === ":") {
            name = name.substr(0, name.length - 1);
        }

        this.callEvent('Change Attribute', {
            dyType: 'change-attr-v1',
            attributeType: name,
            attributeValue: value
        });
    };

    /**
     * Handles sort event
     *
     * @param event
     */
    DynamicYield_Tracking.prototype.onSortChange = function(event) {
        var caller = event.currentTarget,
            target = getClosestElement(caller, '.toolbar-sorter'),
            select = target.querySelector('select'),
            option = select.options[select.selectedIndex];

        if (!option) {
            return;
        }

        var title = option.innerText.trim(),
            switcher = target.querySelector('a'),
            sortOrder,
            changingDir = false,
            url = switcher.getAttribute('data-value');

        if (typeof url === "string") {
            sortOrder = url;
        }

        if (sortOrder) {
            changingDir = switcher === caller;
            sortOrder = changingDir ? sortOrder.toUpperCase() : (sortOrder === "desc" ? "ASC" : "DESC");
        } else {
            return;
        }

        this.callEvent('Sort Items', {
            dyType: 'sort-items-v1',
            sortBy:  title,
            sortOrder: sortOrder
        });
    };

    DY = DY || {};

    DY.Tracker = new DynamicYield_Tracking();
})(DY, window, document);