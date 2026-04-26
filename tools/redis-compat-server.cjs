const net = require('net');

const databases = new Map();
const expirations = new Map();

function getDb(index) {
  if (!databases.has(index)) {
    databases.set(index, new Map());
    expirations.set(index, new Map());
  }

  return {
    data: databases.get(index),
    ttl: expirations.get(index),
  };
}

function nowMs() {
  return Date.now();
}

function cleanupExpired(index) {
  const { data, ttl } = getDb(index);
  const current = nowMs();

  for (const [key, expiresAt] of ttl.entries()) {
    if (expiresAt <= current) {
      ttl.delete(key);
      data.delete(key);
    }
  }
}

function encodeSimple(value) {
  return `+${value}\r\n`;
}

function encodeError(value) {
  return `-${value}\r\n`;
}

function encodeInteger(value) {
  return `:${value}\r\n`;
}

function encodeBulk(value) {
  if (value === null || value === undefined) {
    return `$-1\r\n`;
  }

  const stringValue = String(value);
  return `$${Buffer.byteLength(stringValue)}\r\n${stringValue}\r\n`;
}

function encodeArray(values) {
  let output = `*${values.length}\r\n`;

  for (const value of values) {
    output += encodeBulk(value);
  }

  return output;
}

function parseResp(bufferString) {
  let offset = 0;

  function readLine() {
    const end = bufferString.indexOf('\r\n', offset);
    if (end === -1) {
      return null;
    }

    const line = bufferString.slice(offset, end);
    offset = end + 2;
    return line;
  }

  function readValue() {
    const type = bufferString[offset];
    if (!type) {
      return null;
    }

    offset += 1;

    if (type === '*') {
      const countLine = readLine();
      if (countLine === null) {
        return null;
      }

      const count = Number(countLine);
      const values = [];

      for (let i = 0; i < count; i += 1) {
        const value = readValue();
        if (value === null && bufferString[offset] !== undefined) {
          return null;
        }
        values.push(value);
      }

      return values;
    }

    if (type === '$') {
      const lengthLine = readLine();
      if (lengthLine === null) {
        return null;
      }

      const length = Number(lengthLine);
      if (length === -1) {
        return null;
      }

      const end = offset + length;
      if (bufferString.length < end + 2) {
        return null;
      }

      const value = bufferString.slice(offset, end);
      offset = end + 2;
      return value;
    }

    if (type === '+') {
      return readLine();
    }

    if (type === ':') {
      const line = readLine();
      return line === null ? null : Number(line);
    }

    return null;
  }

  const value = readValue();
  if (value === null && bufferString.length > 0) {
    return { value: null, complete: false };
  }

  return {
    value,
    complete: offset <= bufferString.length,
    remaining: bufferString.slice(offset),
  };
}

function handleCommand(state, commandParts) {
  if (!Array.isArray(commandParts) || commandParts.length === 0) {
    return encodeError('ERR invalid command');
  }

  const [rawCommand, ...args] = commandParts;
  const command = String(rawCommand).toUpperCase();
  cleanupExpired(state.dbIndex);

  if (command === 'PING') {
    return args[0] ? encodeBulk(args[0]) : encodeSimple('PONG');
  }

  if (command === 'SELECT') {
    state.dbIndex = Number(args[0] || 0);
    getDb(state.dbIndex);
    return encodeSimple('OK');
  }

  if (command === 'DBSIZE') {
    cleanupExpired(state.dbIndex);
    return encodeInteger(getDb(state.dbIndex).data.size);
  }

  if (command === 'FLUSHDB') {
    const { data, ttl } = getDb(state.dbIndex);
    data.clear();
    ttl.clear();
    return encodeSimple('OK');
  }

  if (command === 'GET') {
    const { data } = getDb(state.dbIndex);
    return encodeBulk(data.get(String(args[0])) ?? null);
  }

  if (command === 'MGET') {
    const { data } = getDb(state.dbIndex);
    return encodeArray(args.map((key) => data.get(String(key)) ?? null));
  }

  if (command === 'SET') {
    const { data, ttl } = getDb(state.dbIndex);
    data.set(String(args[0]), String(args[1] ?? ''));
    ttl.delete(String(args[0]));
    return encodeSimple('OK');
  }

  if (command === 'SETEX') {
    const { data, ttl } = getDb(state.dbIndex);
    const key = String(args[0]);
    const seconds = Number(args[1] || 0);
    const value = String(args[2] ?? '');

    data.set(key, value);
    ttl.set(key, nowMs() + (seconds * 1000));
    return encodeSimple('OK');
  }

  if (command === 'DEL') {
    const { data, ttl } = getDb(state.dbIndex);
    let removed = 0;

    for (const arg of args) {
      const key = String(arg);
      if (data.delete(key)) {
        removed += 1;
      }
      ttl.delete(key);
    }

    return encodeInteger(removed);
  }

  if (command === 'QUIT') {
    state.quit = true;
    return encodeSimple('OK');
  }

  return encodeError(`ERR unknown command '${command}'`);
}

const server = net.createServer((socket) => {
  const state = { dbIndex: 0, quit: false };
  let pending = '';

  socket.on('data', (chunk) => {
    pending += chunk.toString('utf8');

    while (pending.length > 0) {
      const parsed = parseResp(pending);
      if (!parsed.complete || parsed.value === null) {
        break;
      }

      pending = parsed.remaining || '';
      const response = handleCommand(state, parsed.value);
      socket.write(response);

      if (state.quit) {
        socket.end();
        return;
      }
    }
  });
});

server.listen(6379, '127.0.0.1', () => {
  process.stdout.write('redis-compat listening on 127.0.0.1:6379\n');
});
