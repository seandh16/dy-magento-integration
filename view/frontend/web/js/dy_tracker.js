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
     * MFTF test mode boolean
     *
     * @type {boolean}
     */
    if (localStorage.getItem('mftfTestMode') !== null) {
        DY.mftfTestMode = JSON.parse(localStorage.getItem('mftfTestMode'));
    } else {
        DY.mftfTestMode = false;
    }

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

            if (DY_SETTINGS.currentPage === 'catalogsearch_result_index') {
                return "catalogsearch"
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
     * Remove child element by selector and return current element
     *
     * @param selector
     * @returns {Node}
     */
    Element.prototype.removeChildElement = function(selector) {

        var element = this.cloneNode(true),
            countHtml = element.querySelector(selector);

        if (countHtml !== null) {
            countHtml.remove();
        }

        return element;
    };

    /**
     * Return child node by index
     *
     * @param index
     * @returns {*}
     */
    Element.prototype.getChildNode = function(index) {
        return this.childNodes[index];
    };

    /**
     * Return selected option
     *
     * @returns {*}
     */
    Element.prototype.getSelectedOption = function() {
        var selected = this.selectedIndex;
        return this.options[selected];
    };

    /**
     * Getting the relative element based on the structure
     * Structure has to be in JSON type, except regex (for example replace) params should not be strings
     */
    DynamicYield_Tracking.prototype.applySelectors = function (target, relations) {
        for(var key in relations) {
            try{
                var index = relations[key].match(/\|return=(\d*)/);
                var trimmedKey = key.replace(/[0-9]/g, '');
                target = relations[key] ? (Array.isArray(relations[key]) ? target[trimmedKey].apply(target,relations[key]) : target[trimmedKey](relations[key])) : target[trimmedKey];
                if(index != null) {
                    target = index ? target[index[1]] : target;
                }
            } catch(e){
                return;
            }
        }
        return target;
    };

    /**
     * Workaround for Page Cache
     */
    DynamicYield_Tracking.prototype.fetchEvents = function () {
        try{
            if (window.XMLHttpRequest) {
                var xhr = new XMLHttpRequest();
            } else {
                var xhr = new ActiveXObject("Microsoft.XMLHTTP");
            }
            xhr.open('GET', '/dyIntegration/storage/index?requestId="' + getUniqueId() + '"');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if(response.events) {
                        response.events.forEach(function (event) {
                            try{ DY.API('event', event.properties);

                                if(DY.mftfTestMode === true) {
                                    window.localStorage.setItem(event.properties.properties.dyType, JSON.stringify(event.properties));
                                }

                            }catch(e){}
                        });
                    }
                }
            };
            xhr.send();
        } catch (e){}
    };

    /**
     * Workaround for Page Cache issue.
     * Check if this is a new session
     * If a new session send a Sync Cart event
     */
    DynamicYield_Tracking.prototype.syncCartEvent = function () {
        try{
            if (!sessionStorage.isNewSession) {
                sessionStorage.isNewSession = true;
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
                            try{ DY.API('event', eventData);

                                if(DY.mftfTestMode === true) {
                                    window.localStorage.setItem(eventData.properties.dyType, JSON.stringify(eventData));
                                }

                            }catch(e){}
                        }
                    }
                };
                xhr.send();
            }
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
                            try { DY.API('event', json);

                                if(DY.mftfTestMode === true) {
                                    window.localStorage.setItem(json.properties.dyType, JSON.stringify(json));
                                }

                            } catch (e){}
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
        this.fetchEvents();

        if(type === 'category') {
            /**
             * Layered navigation
             */
            DYO.waitForElement(DY_SETTINGS.eventSelectors.layered_nav_trigger, function(element) {
                var layeredNavTrigger = document.querySelectorAll(DY_SETTINGS.eventSelectors.layered_nav_trigger);
                for (var s = 0; s < layeredNavTrigger.length; s++) {
                    layeredNavTrigger[s].addEventListener('click',function(event){
                        DynamicYield_Tracking.prototype.onLayeredNavClick(event);
                    },false);
                }
                var layeredSwatchTrigger = document.querySelectorAll(DY_SETTINGS.eventSelectors.layered_nav_swatch_trigger);
                for (var s = 0; s < layeredSwatchTrigger.length; s++) {
                    layeredSwatchTrigger[s].addEventListener('click',function(event){
                        DynamicYield_Tracking.prototype.onLayeredNavClick(event);
                    },false);
                }
            }, 1, 100, 100);
            /**
             * Catalog sorting
             */
            DYO.waitForElement(DY_SETTINGS.eventSelectors.category_page_sort_order_trigger, function() {
                var sorterTrigger = document.querySelectorAll(DY_SETTINGS.eventSelectors.category_page_sort_order_trigger);
                var switcherTrigger = document.querySelector(DY_SETTINGS.eventSelectors.category_page_sort_order_switcher_trigger);
                for (var s = 0; s < sorterTrigger.length; s++) {
                    sorterTrigger[s].onchange = function (event) {
                        DynamicYield_Tracking.prototype.onSortChange(event,false);
                    }
                }
                switcherTrigger.addEventListener('click',function(event){
                    DynamicYield_Tracking.prototype.onSortChange(event,true);
                },false);
            }, 1, 100, 100);
        } else if(type === 'product') {
            /**
             * Regular dropdown attributes
             */
            DYO.waitForElement(DY_SETTINGS.eventSelectors.product_page_attribute_trigger, function() {
                var dropDownTrigger = document.querySelectorAll(DY_SETTINGS.eventSelectors.product_page_attribute_trigger);
                for (var s = 0; s < dropDownTrigger.length; s++) {
                    dropDownTrigger[s].onchange = function (event) {
                        DynamicYield_Tracking.prototype.onProductAttributeSelectChange(event);
                    }
                }
            }, 1, 100, 100);
            /**
             * Product custom options
             */
            DYO.waitForElement(DY_SETTINGS.eventSelectors.product_page_custom_option_trigger, function() {
                var customOptions = document.querySelectorAll(DY_SETTINGS.eventSelectors.product_page_custom_option_trigger);
                for (var s = 0; s < customOptions.length; s++) {
                    customOptions[s].onclick = function (event) {
                        DynamicYield_Tracking.prototype.onProductCustomOptionChange(event);
                    }
                }
            }, 1, 100, 100);
            /**
             * Swatch based attributes
             */
            DYO.waitForElement(DY_SETTINGS.eventSelectors.product_page_swatch_trigger, function(element) {
                var swatchTrigger = document.querySelectorAll(DY_SETTINGS.eventSelectors.product_page_swatch_trigger);
                for (var s = 0; s < swatchTrigger.length; s++) {
                    swatchTrigger[s].onclick = function (event) {
                        DynamicYield_Tracking.prototype.onProductSwatchClick(event);
                    }
                }
            }, 1, 100, 100);
        } else if (type === "catalogsearch") {
            let urlParams = new URLSearchParams(window.location.search);
            let searchTerm = urlParams.get('q');
            if(searchTerm) {
                this.callEvent('Keyword Search', {
                    dyType: 'keyword-search-v1',
                    keywords: searchTerm
                });
            }
        }
    };

    /**
     * Layered navigation filter
     *
     * @param event
     */
    DynamicYield_Tracking.prototype.onLayeredNavClick = function(event) {
        var self = event.currentTarget;
        var filterString = false;
        var name = this.applySelectors(self,DY_CUSTOM_STRUCTURE.category_page_filters_type);
        var value = this.applySelectors(self,DY_CUSTOM_STRUCTURE.category_page_filters_price_value)
            || this.applySelectors(self,DY_CUSTOM_STRUCTURE.category_page_filters_regular_value)
            || this.applySelectors(self,DY_CUSTOM_STRUCTURE.category_page_filters_swatch_value)
            || this.applySelectors(self,DY_CUSTOM_STRUCTURE.category_page_filters_swatch_image_value);

        if (value && value.toString().match(/[a-z]/i)) {
            filterString = true;
        }
        if(name && value) {
            var eventProperties = {
                dyType: 'filter-items-v1',
                filterType: name,
            };
            filterString ? eventProperties.filterStringValue = value : eventProperties.filterNumericValue = value;
            this.callEvent('Filter Items', eventProperties);
        }
    };

    /**
     * Handles swatch based product attribute switcher
     *
     * @param event
     */
    DynamicYield_Tracking.prototype.onProductSwatchClick = function(event) {
        var target = event.currentTarget;
        if (!target) {
            return false;
        }
        var name = this.applySelectors(target,DY_CUSTOM_STRUCTURE.product_page_swatch_type);
        var value = this.applySelectors(target,DY_CUSTOM_STRUCTURE.product_page_swatch_value) ||
            this.applySelectors(target,DY_CUSTOM_STRUCTURE.product_page_swatch_image_value);

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
        var self = event.currentTarget;
        var name = this.applySelectors(self,DY_CUSTOM_STRUCTURE.product_page_attribute_type);
        var value = this.applySelectors(self,DY_CUSTOM_STRUCTURE.product_page_attribute_value);
        if(name && value) {
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
        var self = event.currentTarget;
        var name = this.applySelectors(self,DY_CUSTOM_STRUCTURE.product_page_custom_option_type);
        var value = this.applySelectors(self,DY_CUSTOM_STRUCTURE.product_page_custom_option_value);
        if(!value && self.getAttribute("type")) {
            value = this.applySelectors(self,DY_CUSTOM_STRUCTURE.product_page_custom_option_alt_value);
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
     * Handles sort event
     *
     * @param event
     */
    DynamicYield_Tracking.prototype.onSortChange = function(event,switcher) {
        var caller = event.currentTarget;
        var sortBy = this.applySelectors(caller,DY_CUSTOM_STRUCTURE.category_page_sort_order_by);
        var sortOrder = this.applySelectors(caller,DY_CUSTOM_STRUCTURE.category_page_sort_order_direction);
        if(sortOrder) {
            sortOrder = switcher ? sortOrder.toUpperCase() : (sortOrder === "desc" ? "ASC" : "DESC");
            if(sortBy && sortOrder) {
                this.callEvent('Sort Items', {
                    dyType: 'sort-items-v1',
                    sortBy:  sortBy,
                    sortOrder: sortOrder
                });
            }
        }
    };
    DY = DY || {};
    DY.Tracker = new DynamicYield_Tracking();
})(DY, window, document);
