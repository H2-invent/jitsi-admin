export let user = {};

export function loginUser(socketId, userId) {
    user[socketId] = userId;
}

export function logoutUser(sockId) {
    delete user[sockId];
}

export function getOnlineUSer() {
    var tmpUSer = [];
    for (var prop in user) {
    tmpUSer.push(user[prop]);
    }
    console.log(tmpUSer);
    return tmpUSer;
}