/*
 * Welcome to your app's main JavaScript file!
 *
 */

class jitsiErrorHandling {
    api = null;
    error = [];
    log=[]

    constructor(api) {
        this.api = api;
        this.error = [{}];
        api.addListener('errorOccurred', data => {
            // update mute state for newly joined participant
            this.error.push(data)
        });

        api.addListener('log', data => {
            // update mute state for newly joined participant
            this.log.push(data);
        });
    }


}


export {jitsiErrorHandling}
