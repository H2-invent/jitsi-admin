// Mocha setup — set env vars before any imports
process.env.NETWORK_ACCESS = "true";
process.env.WEBSOCKET_SECRET = process.env.WEBSOCKET_SECRET || "test-secret-do-not-use-in-production";
