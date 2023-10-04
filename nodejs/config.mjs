export var WEBSOCKET_SECRET=process.env.WEBSOCKET_SECRET ||"MY_SECRET"
export var MERCURE_INTERNAL_URL=process.env.MERCURE_INTERNAL_URL || "/.well-known/mercure";
export var PORT =process.env.PORT || 3000;
export var AWAY_TIME =process.env.AWAY_TIME || 5;
export var DEFAULT_STATE =process.env.DEFAULT_STATE || 'offline';
export var KEY_FILE =process.env.KEY_FILE || './tls_certificate/key.pem';
export var CERT_FILE =process.env.CERT_FILE || './tls_certificate/cert.pem';