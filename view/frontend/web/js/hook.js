/* global xhook, DY_HEADER_NAME, MGB, DY */
xhook.after(function(request, response, callback) {
    "use strict";

    var headers = response.headers;
    var targetHeader = DY_HEADER_NAME || 'dy-event-data';

    if(headers[targetHeader]) {
        var json = JSON.parse(headers[targetHeader]);

        try {
            DY.API('event', json);
        } catch(e) {
            MGB.StorageUtils.setData(json);
        }
    }

    return callback();
});