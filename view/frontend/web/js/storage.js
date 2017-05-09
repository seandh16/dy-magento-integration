/* global MGB, DY_STORAGE_URL */
(function(MGB, exports, doc) {
    "use strict";

    /**
     * Custom Exception
     *
     * @param message
     * @constructor
     */
    function MGBStorageException (message) {
        this.message = message;
        this.name = "MGBStorageException";
    }

    /**
     * Event Queue Storage Utility
     *
     * @constructor
     */
    function MGB_Storage () {}

    MGB_Storage.prototype.xhrOK = 200;
    MGB_Storage.prototype.xhrDONE = 4;
    MGB_Storage.prototype.xhr = new XMLHttpRequest();

    /**
     * Send data to storage
     *
     * @param url
     * @param data
     */
    MGB_Storage.prototype.send = function (url, data) {
        this.xhr.open('POST', url  + '?data=' + JSON.stringify(data), true);
        this.xhr.setRequestHeader('Content-Type', 'application/json');

        this.xhr.onreadystatechange = function () {
            if (this.xhr.readyState === this.xhrDONE) {
                if (this.xhr.status === this.xhrOK) {
                    return true;
                } else {
                    throw new MGBStorageException('Failed to send data to storage');
                }
            }
        }.bind(this);

        this.xhr.send();
    };

    /**
     * Get Storage Data
     *
     * @param url
     */
    MGB_Storage.prototype.get = function (url) {
        this.xhr.open('GET', url);
        this.xhr.setRequestHeader('Content-Type', 'application/json');

        this.xhr.onreadystatechange = function () {
            if (this.xhr.readyState === this.xhrDONE) {
                if (this.xhr.status === this.xhrOK) {
                    return JSON.parse(this.xhr.responseText);
                } else {
                    throw new MGBStorageException('Failed to receive data');
                }
            }
        }.bind(this);

        this.xhr.send();
    };

    /**
     * Set data to storage
     *
     * @param data
     */
    MGB_Storage.prototype.setData = function (data) {
        try {
            this.send(DY_STORAGE_URL, data);
        } catch (e) {}
    };

    /**
     * Get data from storage
     */
    MGB_Storage.prototype.getData = function () {
        try {
            this.get(DY_STORAGE_URL);
        } catch (e) {}
    };

    MGB = MGB || {};
    MGB.StorageUtils = new MGB_Storage();
}(MGB, window, document));