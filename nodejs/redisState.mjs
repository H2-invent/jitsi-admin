import { createRedisClient } from "./redisClient.mjs";

export const redis = createRedisClient();
await redis.connect();

const KEY = "online_users";

export async function setUserStatus(userId, status) {
    await redis.hSet(KEY, userId, status);
}

export async function removeUser(userId) {
    await redis.hDel(KEY, userId);
}

export async function getOnlineUsers() {
    return await redis.hGetAll(KEY);
}

