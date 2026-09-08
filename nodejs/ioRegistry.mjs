// Shared Socket.IO instance reference
// Set by server.mjs after the Server is created.
// This avoids a circular dependency between server.mjs and User.mjs.

let _io = null;

export function setIO(instance) {
  _io = instance;
}

export function getIO() {
  return _io;
}
