import jwt from "jsonwebtoken";
import { setUserStatus, removeUser } from "./redisState.mjs";
import { WEBSOCKET_SECRET } from "./config.mjs";

export async function loginUser(socket) {
    const token = socket.handshake.query.token;
    const decoded = jwt.verify(token, WEBSOCKET_SECRET);

    await setUserStatus(decoded.sub, "online");
}

export async function disconnectUser(socket) {
    const token = socket.handshake.query.token;
    const decoded = jwt.decode(token);
    await removeUser(decoded.sub);
}

