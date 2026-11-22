export var WEBSOCKET_SECRET = process.env.WEBSOCKET_SECRET || "MY_SECRET";
export var MERCURE_INTERNAL_URL = process.env.MERCURE_INTERNAL_URL || "/.well-known/mercure";
export var PORT = process.env.PORT || 3000;
export var AWAY_TIME = process.env.AWAY_TIME || 5;
export var DEFAULT_STATE = process.env.DEFAULT_STATE || "offline";
// Milliseconds since a user's Redis entry was last refreshed (heartbeat ~10s) before the user is
// treated as gone. Defaults to 30s = 3 missed heartbeats.
export var STALE_TIMEOUT_MS = parseInt(process.env.WEBSOCKET_STALE_TIMEOUT_MS, 10) || 30 * 1000;
export var KEY_FILE = process.env.KEY_FILE || "./tls_certificate/key.pem";
export var CERT_FILE = process.env.CERT_FILE || "./tls_certificate/cert.pem";

// Redis für Clusterbetrieb
export var REDIS_ENABLED = process.env.REDIS_ENABLED === "true";
export var REDIS_HOST = process.env.REDIS_HOST || "redis";
export var REDIS_PORT = process.env.REDIS_PORT || 6379;
