import { createAdapter } from "@socket.io/redis-adapter";
import { createRedisClient } from "./redisClient.mjs";

export async function setupRedisAdapter(io) {
    const pubClient = createRedisClient();
    const subClient = pubClient.duplicate();

    await pubClient.connect();
    await subClient.connect();

    io.adapter(createAdapter(pubClient, subClient));

    pubClient.on("error", console.error);
    subClient.on("error", console.error);

    console.log("Redis adapter connected (Sentinel)");
}

