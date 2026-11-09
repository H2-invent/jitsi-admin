// Redis helper for cluster tests.
// Tries connecting to REDIS_URL, then DDEV redis, then spins up a temp docker container.
// Returns { redisClient, cleanup } or null if unavailable.

let redisClient = null;
let containerId = null;

export async function startRedis() {
  const urls = [
    process.env.REDIS_URL,
    process.env.WEBSOCKET_REDIS_URL,
    "redis://localhost:6379",
    "redis://ddev-jitsi-admin-redis:6379",
  ].filter(Boolean);

  const { createClient } = await import("redis");

  for (const url of urls) {
    try {
      const client = createClient({ url, socket: { connectTimeout: 2000 } });
      await client.connect();
      await client.ping();
      containerId = null;
      redisClient = client;
      return { redisClient: client, cleanup: () => client.quit() };
    } catch (e) {
      // try next
    }
  }

  // Try Docker
  try {
    const { execSync } = await import("child_process");
    const id = execSync(
      'docker run --rm -d -p 16379:6379 redis:7-alpine 2>/dev/null',
      { timeout: 10000 }
    ).toString().trim();

    if (id) {
      // Wait for Redis to be ready
      for (let i = 0; i < 30; i++) {
        try {
          const client = createClient({ url: "redis://localhost:16379", socket: { connectTimeout: 1000 } });
          await client.connect();
          await client.ping();
          containerId = id;
          redisClient = client;
          return {
            redisClient: client,
            cleanup: async () => {
              await client.quit();
              try { execSync(`docker rm -f ${id} 2>/dev/null`, { timeout: 5000 }); } catch {}
            }
          };
        } catch {
          await new Promise(r => setTimeout(r, 1000));
        }
      }
    }
  } catch {}

  return null;
}

export async function cleanupRedis() {
  if (redisClient) {
    try { await redisClient.quit(); } catch {}
    redisClient = null;
  }
  if (containerId) {
    try {
      const { execSync } = await import("child_process");
      execSync(`docker rm -f ${containerId} 2>/dev/null`, { timeout: 5000 });
    } catch {}
    containerId = null;
  }
}
