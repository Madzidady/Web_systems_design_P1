const net = require('net');

const args = process.argv.slice(2);
const socket = net.createConnection({ host: '127.0.0.1', port: 6379 });

function encodeArray(values) {
  let output = `*${values.length}\r\n`;

  for (const value of values) {
    const stringValue = String(value);
    output += `$${Buffer.byteLength(stringValue)}\r\n${stringValue}\r\n`;
  }

  return output;
}

socket.on('connect', () => {
  socket.write(encodeArray(args));
});

socket.on('data', (chunk) => {
  const text = chunk.toString('utf8');

  if (text.startsWith('+')) {
    console.log(text.slice(1).trim());
  } else if (text.startsWith(':')) {
    console.log(text.slice(1).trim());
  } else if (text.startsWith('$')) {
    const parts = text.split('\r\n');
    console.log(parts[1] ?? '');
  } else if (text.startsWith('-')) {
    console.error(text.slice(1).trim());
    process.exitCode = 1;
  } else {
    console.log(text.trim());
  }

  socket.end();
});

socket.on('error', (error) => {
  console.error(error.message);
  process.exit(1);
});
