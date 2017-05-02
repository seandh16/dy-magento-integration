/* global xhook, DY_HEADER_NAME, DY, console */
xhook.after(function(request, response, callback) {
    "use strict";

    var headers = response.headers;
    var targetHeader = DY_HEADER_NAME || 'dy-event-data';

    if(headers[targetHeader]) {
        try {
            var json = JSON.parse(headers[targetHeader]);

            DY.API('event', json);
        } catch(e) {}
    }

    return callback();
});