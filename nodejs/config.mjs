export var WEBSOCKET_SECRET=process.env.WEBSOCKET_SECRET ||"MY_SECRET"
export var MERCURE_INTERNAL_URL=process.env.MERCURE_INTERNAL_URL || "/.well-known/mercure";
export var PORT =process.env.PORT || 3000;
export var AWAY_TIME =process.env.AWAY_TIME || 5;
export var DEFAULT_STATE =process.env.DEFAULT_STATE || 'offline';
export var KEY_FILE =process.env.KEY_FILE || './tls_certificate/key.pem';
export var CERT_FILE =process.env.CERT_FILE || './tls_certificate/cert.pem';

export const REDIS_SENTINELS = process.env.REDIS_SENTINELS || "127.0.0.1:26379";
export const REDIS_MASTER_NAME = process.env.REDIS_MASTER_NAME || "mymaster";
export const REDIS_PASSWORD = process.env.REDIS_PASSWORD || null;
