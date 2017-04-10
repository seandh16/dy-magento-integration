xhook.after(function(request, response, callback) {
    var headers = response.headers;
    var targetHeader = DY_HEADER_NAME || 'dy-event-data';

    if(headers[targetHeader]) {
        try {
            var json = JSON.parse(headers[targetHeader]);

            console.log('DY.Api:Event', json);

            DY.API('event', json);
        } catch(e) {
            console.error('Failed to read event data.', e);
        }
    }

    return callback();
});