export const WEBSOCKET_SECRET = process.env.WEBSOCKET_SECRET || "MY_SECRET";
export const MERCURE_INTERNAL_URL = process.env.MERCURE_INTERNAL_URL || "/.well-known/mercure";
export const PORT = process.env.PORT || 3000;
export const AWAY_TIME = process.env.AWAY_TIME || 5;
export const DEFAULT_STATE = process.env.DEFAULT_STATE || "offline";
export const KEY_FILE = process.env.KEY_FILE || "./tls_certificate/key.pem";
export const CERT_FILE = process.env.CERT_FILE || "./tls_certificate/cert.pem";

export const REDIS_HOST = process.env.REDIS_HOST || "redis";
export const REDIS_PORT = parseInt(process.env.REDIS_PORT || "6379", 10);
export const REDIS_PASSWORD = process.env.REDIS_PASSWORD || "";
export const LOG_LEVEL = process.env.LOG_LEVEL || "info";
export const REDIS_RETRY_DELAY = parseInt(process.env.REDIS_RETRY_DELAY || "2000", 10);
