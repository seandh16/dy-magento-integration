/* global DY, DY_SETTINGS, MGB */
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
            this.ajaxEvent(XMLHttpRequest);
        }.bind(this));
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
        if (DY_SETTINGS.currentPage.length) {
            if (DY_SETTINGS.currentPage === 'catalog_category_view') {
                return "category";
            }

            if (DY_SETTINGS.currentPage === 'catalog_product_view') {
                return "product";
            }
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
        properties['uniqueRequestId'] = getUniqueId();
        var eventData = {
            name: name,
            properties: properties
        };
        DY.API('event', eventData);
    };

    /**
     * Workaround for Page Cache issue.
     * Check if this is a new session
     * If a new session send a Sync Cart event
     */
    DynamicYield_Tracking.prototype.syncCartEvent = function () {
        try{
            if (window.XMLHttpRequest) {
                var xhr = new XMLHttpRequest();
            } else {
                var xhr = new ActiveXObject("Microsoft.XMLHTTP");
            }
            xhr.open('GET', '/dyIntegration/synccart/index');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var session = JSON.parse(xhr.responseText);
                    if(session.sync_cart == false) {
                        var eventData = {
                            name: session.eventData.name,
                            properties: session.eventData.properties
                        };
                        try{ DY.API('event', eventData); }catch(e){}
                    }
                }
            };
            xhr.send();
        } catch (e){}
    };

    /**
     * Generates unique id
     *
     * @returns {Number}
     */
    function getUniqueId() {
        return parseInt(Date.now() + Math.random());
    }

    /**
     * Tracks ajax events and sends a request to Dynamic Yield API
     *
     * @param DY_XHR
     */
    DynamicYield_Tracking.prototype.ajaxEvent = function (DY_XHR) {
        var dy_send = DY_XHR.prototype.send;
        var dy_headers = [];
        DY_XHR.prototype.send = function(data) {
            var readyState;
            function onReadyStateChange() {
                if (this.readyState == 4 && this.status == 200) {
                    try {
                        var key, name, val;
                        var headers = dy_convertHeaders(this.getAllResponseHeaders());
                        for (key in headers) {
                            val = headers[key];
                            if (!dy_headers[key]) {
                                name = key.toLowerCase();
                                dy_headers[name] = val;
                            }
                        }
                        var targetHeader = DY_SETTINGS.headerName || 'dy-event-data';
                        if(dy_headers[targetHeader]) {
                            var json = JSON.parse(headers[targetHeader]);
                            try { DY.API('event', json); } catch (e){}
                        }
                    } catch (e) {}
                }
                if (readyState) {
                    readyState();
                }
            }
            var dy_convertHeaders  = function(h, dest) {
                var header, headers, k, name, v, value, _i, _len, _ref;
                if (dest == null) {
                    dest = {};
                }
                switch (typeof h) {
                    case "object":
                        headers = [];
                        for (k in h) {
                            v = h[k];
                            name = k.toLowerCase();
                            headers.push("" + name + ":\t" + v);
                        }
                        return headers.join('\n');
                    case "string":
                        headers = h.split('\n');
                        for (_i = 0, _len = headers.length; _i < _len; _i++) {
                            header = headers[_i];
                            if (/([^:]+):\s*(.+)/.test(header)) {
                                name = (_ref = RegExp.$1) != null ? _ref.toLowerCase() : void 0;
                                value = RegExp.$2;
                                if (dest[name] == null) {
                                    dest[name] = value;
                                }
                            }
                        }
                        return dest;
                }
            };
            if (this.addEventListener) {
                this.addEventListener("readystatechange", onReadyStateChange, false);
            } else {
                readyState = this.onreadystatechange;
                this.onreadystatechange = onReadyStateChange;
            }
            dy_send.call(this, data);
        }
    };

    /**
     * Registers all events based on page
     */
    DynamicYield_Tracking.prototype.onLoad = function() {
        var type = this.detectPage();
        this.syncCartEvent();

        if(type === 'category') {
            var layeredNav = doc.querySelector(DY_SETTINGS.eventSelectors.layered_nav_block);
            if (layeredNav !== null) {
                var layeredOptionsWrapper = layeredNav.querySelectorAll(DY_SETTINGS.eventSelectors.layered_nav_content);

                if (layeredOptionsWrapper !== null && layeredOptionsWrapper.length) {
                    for (var i = 0; i < layeredOptionsWrapper.length; i++) {
                        var layeredLinks = layeredOptionsWrapper[i].querySelectorAll(DY_SETTINGS.eventSelectors.layered_nav_trigger);

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

            var sorter = doc.querySelector(DY_SETTINGS.eventSelectors.toolbar_sorter_block);

            if (sorter !== null) {
                var sorterSelect = sorter.querySelector(DY_SETTINGS.eventSelectors.toolbar_sorter_type),
                    sorterLink = sorter.querySelector(DY_SETTINGS.eventSelectors.toolbar_sorter_order);

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
            var optionsWrapper = doc.querySelector(DY_SETTINGS.eventSelectors.product_options_wrapper);

            if (optionsWrapper !== null) {
                var regularAttribute = optionsWrapper.querySelectorAll(DY_SETTINGS.eventSelectors.product_options_regular_attribute);

                if (regularAttribute !== null && regularAttribute.length) {
                    for (var r = 0; r < regularAttribute.length; r++) {
                        regularAttribute[r].onchange = function (event) {
                            this.onProductAttributeSelectChange(event);
                        }.bind(this);
                    }
                }

                var customOption = optionsWrapper.querySelectorAll(DY_SETTINGS.eventSelectors.product_custom_options_container);

                if (customOption !== null && customOption.length) {
                    for (var c = 0; c < customOption.length; c++) {
                        if (customOption[c].tagName.toLowerCase() === DY_SETTINGS.eventSelectors.product_custom_options_type_select) {
                            customOption[c].onchange = function (event) {
                                this.onProductCustomOptionChange(event);
                            }.bind(this);
                        } else if (customOption[c].tagName.toLowerCase() === DY_SETTINGS.eventSelectors.product_custom_options_type_input) {
                            if (customOption[c].getAttribute('type').toLowerCase() === DY_SETTINGS.eventSelectors.product_custom_options_type_radio ||
                                customOption[c].getAttribute('type').toLowerCase() === DY_SETTINGS.eventSelectors.product_custom_options_type_checkbox) {
                                addEventHandler(customOption[c], 'click', function (event) {
                                    this.onProductCustomOptionChange(event);
                                }.bind(this));
                            }
                        }
                    }
                }

                var swatchAttribute = optionsWrapper.querySelector(DY_SETTINGS.eventSelectors.product_custom_options_swatch_container);

                if (swatchAttribute !== null) {
                    addEventHandler(swatchAttribute, 'DOMNodeInserted', function (event) {
                        var swatchSelect = swatchAttribute.querySelectorAll(DY_SETTINGS.eventSelectors.product_custom_options_swatch_select);

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
            filterContainer = getClosestElement(self, DY_SETTINGS.eventSelectors.layered_nav_filter_container),
            isSwatchLink = self.querySelector(DY_SETTINGS.eventSelectors.layered_nav_filter_swatch_option),
            isPrice = false;

        if (filterContainer === null) {
            return false;
        }

        filterTitle = filterContainer.querySelector(DY_SETTINGS.eventSelectors.layered_nav_filter_title);

        if (filterTitle === null) {
            return false;
        }

        name = filterTitle.innerText.trim();

        if (isSwatchLink !== null) {
            var dataLabel = isSwatchLink.getAttribute(DY_SETTINGS.eventSelectors.layered_nav_filter_swatch_data_title),
                optionLabel = isSwatchLink.getAttribute(DY_SETTINGS.eventSelectors.layered_nav_filter_swatch_title);

            value = (dataLabel || optionLabel).trim();
        } else {
            var regex = /^\D+|\D+$/g,
                prices = self.querySelectorAll(DY_SETTINGS.eventSelectors.layered_nav_filter_price);

            if (prices !== null && prices.length) {
                isPrice = true;

                var priceArray = [];

                for(var p = 0; p < prices.length; p++) {
                    priceArray.push(prices[p].innerText.replace(regex, '').trim());
                }

                value = priceArray.join('-');
            } else {
                var element = self.cloneNode(true),
                    countHtml = element.querySelector(DY_SETTINGS.eventSelectors.layered_nav_filter_item_count);

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

            name = child.querySelector(DY_SETTINGS.eventSelectors.product_custom_options_swatch_attribute_value).innerText;
            value = child.querySelector(DY_SETTINGS.eventSelectors.product_custom_options_swatch_attribute_selected).innerText.trim();
        } else if (target.tagName.toLowerCase() === DY_SETTINGS.eventSelectors.product_custom_options_swatch_select) {
            var parent = getClosestElement(target, DY_SETTINGS.eventSelectors.product_custom_options_swatch_attribute_parent),
                selected = target.options[target.selectedIndex];

            name = parent.querySelector(DY_SETTINGS.eventSelectors.product_custom_options_swatch_attribute_value).innerText;
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
            parent = getClosestElement(self, DY_SETTINGS.eventSelectors.product_options_regular_attribute_parent),
            name = parent.querySelector(DY_SETTINGS.eventSelectors.product_options_regular_attribute_name_label)
                .querySelector(DY_SETTINGS.eventSelectors.product_options_regular_attribute_name_container).innerText,
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
            control = getClosestElement(target, DY_SETTINGS.eventSelectors.product_custom_options_control),
            parent = getClosestElement(control, DY_SETTINGS.eventSelectors.product_custom_options_control_parent),
            name,
            value;

        if (!parent) {
            return false;
        }

        var label = parent.querySelector(DY_SETTINGS.eventSelectors.product_custom_options_label);

        if (!label) {
            return false;
        }

        name = label.querySelector(DY_SETTINGS.eventSelectors.product_custom_options_label_container).innerText.trim();

        if (target.tagName.toLowerCase() === DY_SETTINGS.eventSelectors.product_custom_options_swatch_select) {
            if (target.getAttribute(DY_SETTINGS.eventSelectors.product_custom_options_select_multiple) === null) {
                var selected = target.options[target.selectedIndex];

                value = selected.value > 0 ? selected.innerText.trim() : false;
            }
        } else {
            var subParent = getClosestElement(target, DY_SETTINGS.eventSelectors.product_custom_options_control_parent),
                subLabel = subParent.querySelector(DY_SETTINGS.eventSelectors.product_custom_options_label);

            if (target.getAttribute('type') === DY_SETTINGS.eventSelectors.product_custom_options_type_checkbox) {
                if (target.checked) {
                    value = subLabel.querySelector(DY_SETTINGS.eventSelectors.product_custom_options_label_container).innerText.trim();
                }
            } else {
                value = subLabel.querySelector(DY_SETTINGS.eventSelectors.product_custom_options_label_container).innerText.trim();
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
            target = getClosestElement(caller, DY_SETTINGS.eventSelectors.toolbar_sorter_block),
            select = target.querySelector(DY_SETTINGS.eventSelectors.toolbar_sorter_type),
            option = select.options[select.selectedIndex];

        if (!option) {
            return;
        }

        var title = option.innerText.trim(),
            switcher = target.querySelector(DY_SETTINGS.eventSelectors.toolbar_sorter_order),
            sortOrder,
            changingDir = false,
            url = switcher.getAttribute(DY_SETTINGS.eventSelectors.toolbar_sorter_order_value);

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