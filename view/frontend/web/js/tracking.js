/* global DY_STORAGE_KEY, DY, MGB */

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

        if (d.addEventListener) {
            d.addEventListener("DOMContentLoaded", onReady);
        } else if (d.attachEvent) {
            d.attachEvent("onreadystatechange", onReadyIe);
        }
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
                    while (--i >= 0 && matches.item(i) !== this) {
                        // Define variable to fix empty block JSHint
                        var l;
                    }

                    return i > -1;
                };
        }

        for ( ; element && element !== document; element = element.parentNode ) {
            if ( element.matches( selector ) ) {
                return element;
            }
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
        var eventData = {
                name: name,
                properties: properties
            };

        try {
            DY.API('event', eventData);
        } catch(e) {
            MGB.StorageUtils.setData(eventData);
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
                    sorterSelect.onchange = function(event) {
                        this.onSortChange(event);
                    }.bind(this);
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
                var regularAttribute = optionsWrapper.querySelectorAll('select.super-attribute-select');

                if (regularAttribute !== null && regularAttribute.length) {
                    for (var r = 0; r < regularAttribute.length; r++) {
                        regularAttribute[r].onchange = function (event) {
                            this.onProductAttributeSelectChange(event);
                        }.bind(this);
                    }
                }

                var customOption = optionsWrapper.querySelectorAll('.product-custom-option');

                if (customOption !== null && customOption.length) {
                    for (var c = 0; c < customOption.length; c++) {
                        if (customOption[c].tagName.toLowerCase() === "select") {
                            customOption[c].onchange = function (event) {
                                this.onProductCustomOptionChange(event);
                            }.bind(this);
                        } else if (customOption[c].tagName.toLowerCase() === "input") {
                            if (customOption[c].getAttribute('type') === "radio" ||
                                customOption[c].getAttribute('type') === "checkbox") {
                                addEventHandler(customOption[c], 'click', function (event) {
                                    this.onProductCustomOptionChange(event);
                                }.bind(this));
                            }
                        }
                    }
                }

                var swatchAttribute = optionsWrapper.querySelector('.swatch-opt');

                if (swatchAttribute !== null) {
                    addEventHandler(swatchAttribute, 'DOMNodeInserted', function (event) {
                        var swatchSelect = swatchAttribute.querySelectorAll('select');

                        if (swatchSelect !== null && swatchSelect.length) {
                            for (var s = 0; s < swatchSelect.length; s++) {
                                swatchSelect[s].onchange = function (event) {
                                    this.onProductSwatchClick(event);
                                }.bind(this);
                            }
                        }

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
            isSwatchLink = self.querySelector('.swatch-option'),
            isPrice = false;

        if (filterContainer === null) {
            return false;
        }

        filterTitle = filterContainer.querySelector('.filter-options-title');

        if (filterTitle === null) {
            return false;
        }

        name = filterTitle.innerText.trim();

        if (isSwatchLink !== null) {
            var dataLabel = isSwatchLink.getAttribute('data-option-label'),
                optionLabel = isSwatchLink.getAttribute('option-label');

            value = (dataLabel || optionLabel).trim();
        } else {
            var regex = /^\D+|\D+$/g,
                prices = self.getElementsByClassName('price');

            if (prices !== null && prices.length) {
                isPrice = true;

                var priceArray = [];

                for(var p = 0; p < prices.length; p++) {
                    priceArray.push(prices[p].innerText.replace(regex, '').trim());
                }

                value = priceArray.join('-');
            } else {
                var element = self.cloneNode(true),
                    countHtml = element.querySelector('.count');

                if (countHtml !== null) {
                    countHtml.remove();
                }

                value = element.innerText.trim();
            }
        }

        if(name !== null && value !== null && value !== "") {
            var eventProperties = {
                dyType: 'filter-items-v1',
                filterType: name,
                filterNumericValue: value
            };

            if (!isPrice && isNaN(value)) {
                eventProperties = {
                    dyType: 'filter-items-v1',
                    filterType: name,
                    filterStringValue: value
                };
            }

            this.callEvent('Filter Items', eventProperties);
        }
    };

    /**
     * Handles swatch based product attribute switcher
     *
     * @param event
     */
    DynamicYield_Tracking.prototype.onProductSwatchClick = function(event) {
        var self = event,
            target = self.currentTarget,
            relatedNode = self.relatedNode,
            name,
            value;

        if (!relatedNode && !target) {
            return false;
        }

        if (typeof relatedNode !== "undefined") {
            var child = relatedNode.parentNode;

            name = child.querySelector('span.swatch-attribute-label').innerText;
            value = child.querySelector('span.swatch-attribute-selected-option').innerText.trim();
        } else if (target.tagName.toLowerCase() === "select") {
            var parent = getClosestElement(target, '.swatch-attribute'),
                selected = target.options[target.selectedIndex];

            name = parent.querySelector('span.swatch-attribute-label').innerText;
            value = selected.value > 0 ? selected.innerText.trim() : false;
        } else {
            return false;
        }

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
            selected = self.options[self.selectedIndex],
            value;

        name = name.trim();
        value = selected.value > 0 ? selected.innerText.trim() : false;

        if (name.substr(-1) === ":") {
            name = name.substr(0, name.length - 1);
        }

        if (name && value) {
            this.callEvent('Change Attribute', {
                dyType: 'change-attr-v1',
                attributeType: name,
                attributeValue: value
            });
        }
    };

    /**
     * Handles product custom option switcher
     *
     * @param event
     */
    DynamicYield_Tracking.prototype.onProductCustomOptionChange = function (event) {
        var self = event,
            target = self.currentTarget,
            control = getClosestElement(target, '.control'),
            parent = getClosestElement(control, '.field'),
            name,
            value;

        if (!parent) {
            return false;
        }

        var label = parent.querySelector('.label');

        if (!label) {
            return false;
        }

        name = label.querySelector('span').innerText.trim();

        if (target.tagName.toLowerCase() === "select") {
            if (target.getAttribute('multiple') === null) {
                var selected = target.options[target.selectedIndex];

                value = selected.value > 0 ? selected.innerText.trim() : false;
            }
        } else {
            var subParent = getClosestElement(target, '.field'),
                subLabel = subParent.querySelector('.label');

            if (target.getAttribute('type') === "checkbox") {
                if (target.checked) {
                    value = subLabel.querySelector('span').innerText.trim();
                }
            } else {
                value = subLabel.querySelector('span').innerText.trim();
            }
        }

        if(name.substr(-1) === ":") {
            name = name.substr(0, name.length - 1);
        }

        if (name && value) {
            this.callEvent('Change Attribute', {
                dyType: 'change-attr-v1',
                attributeType: name,
                attributeValue: value
            });
        }
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