import { createClient } from "redis";
import {
    REDIS_SENTINELS,
    REDIS_MASTER_NAME,
    REDIS_PASSWORD
} from "./config.mjs";

function parseSentinels(value) {
    return value.split(",").map(entry => {
        const [host, port] = entry.trim().split(":");
        return {
            host,
            port: Number(port)
        };
    });
}

export function createRedisClient() {
    return createClient({
        socket: {
            sentinels: parseSentinels(REDIS_SENTINELS),
            name: REDIS_MASTER_NAME
        },
        password: REDIS_PASSWORD || undefined
    });
}

