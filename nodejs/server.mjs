import cluster from "cluster";
import os from "os";

const cpuCount = os.cpus().length;

if (cluster.isPrimary) {
    console.log(`Master ${process.pid} running`);

    for (let i = 0; i < cpuCount; i++) {
        cluster.fork();
    }

    cluster.on("exit", (worker) => {
        console.log(`Worker ${worker.process.pid} died – restarting`);
        cluster.fork();
    });

} else {
    await import("./websocket.mjs");
}

