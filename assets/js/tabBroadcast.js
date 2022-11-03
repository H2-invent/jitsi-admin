/*
Jitbit TabUtils - helper for multiple browser tabs. version 1.0
https://github.com/jitbit/TabUtils
- executing "interlocked" function call - only once per multiple tabs
- broadcasting a message to all tabs (including the current one) with some message "data"
- handling a broadcasted message
MIT license: https://github.com/jitbit/TabUtils/blob/master/LICENSE
*/

var TabUtils = new (function () {

    var keyPrefix = "_tabutils_"; //just a unique id for key-naming

    //runs code only once in multiple tabs
    //the lock holds for 4 seconds (in case the function is async and returns right away, for example, an ajax call intiated)
    //then it is cleared
    this.CallOnce = function (lockname, fn, timeout) {

        timeout = timeout || 3000; //lock held for 3 seconds by default

        if (!lockname) throw "empty lockname";

        if (!window.localStorage) { //no local storage. old browser. screw it, just run the function
            fn();
            return;
        }

        var localStorageKey = keyPrefix + lockname;

        localStorage.setItem(localStorageKey, myTabId);
        //re-read after a delay (after all tabs have saved their tabIDs into ls)
        setTimeout(function () {
            if (localStorage.getItem(localStorageKey) == myTabId)
                fn();
        }, 150);

        //cleanup - release the lock after 3 seconds and on window unload (just in case user closed the window while the lock is still held)
        setTimeout(function () {
            localStorage.removeItem(localStorageKey);
        }, timeout);
    }

    this.BroadcastMessageToAllTabs = function (messageId, eventData) {
        if (!window.localStorage) return; //no local storage. old browser

        var data = {
            data: eventData,
            timeStamp: (new Date()).getTime()
        }; //add timestamp because overwriting same data does not trigger the event

        //this triggers 'storage' event for all other tabs except the current tab
        localStorage.setItem(keyPrefix + "event" + messageId, JSON.stringify(data));

        //now we also need to manually execute handler in the current tab too, because current tab does not get 'storage' events
        try {
            handlers[messageId](eventData);
        } //"try" in case handler not found
        catch (x) {
        }

        //cleanup
        setTimeout(function () {
            localStorage.removeItem(keyPrefix + "event" + messageId);
        }, 3000);
    }

    var handlers = {};

    this.OnBroadcastMessage = function (messageId, fn) {
        if (!window.localStorage) return; //no local storage. old browser

        //first register a handler for "storage" event that we trigger above
        window.addEventListener('storage', function (ev) {
            if (ev.key != keyPrefix + "event" + messageId) return; // ignore other keys
            if (!ev.newValue) return; //called by cleanup?
            var messageData = JSON.parse(ev.newValue);
            fn(messageData.data);
        });

        //second, add callback function to the local array so we can access it directly
        handlers[messageId] = fn;
    }

    this.lockFunction = function (lockname, fn, timeout) {
        timeout = timeout || 3000; //lock held for 3 seconds by default
        if (!lockname) throw "empty lockname";

        if (!window.localStorage) { //no local storage. old browser. screw it, just run the function
            fn();
            return;
        }

        var localStorageKey = keyPrefix + lockname;
        if (localStorage.getItem(localStorageKey) === null) {
            localStorage.setItem(localStorageKey, myTabId);
            fn();
        }

        //re-read after a delay (after all tabs have saved their tabIDs into ls)
        //cleanup - release the lock after 3 seconds and on window unload (just in case user closed the window while the lock is still held)
        setTimeout(function () {
            localStorage.removeItem(localStorageKey);
        }, timeout);
    }

    function someNumber() {
        return Math.random() * 1000000000 | 0;
    }

    var myTabId = performance.now() + ":" + someNumber();
})();

export {TabUtils}