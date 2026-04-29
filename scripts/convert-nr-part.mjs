import { readFileSync, writeFileSync } from 'node:fs';
import { basename } from 'node:path';

const args = process.argv.slice(2);

function usage() {
  console.log(`Usage:
node scripts/convert-nr-part.mjs <input.json> <output.json> [--index=0] [--type=0] [--typeName=head] [--images=array|object] [--pretty]

Input can be:
  1. [[18122,-5,-12],[18123,-9,-15]]
  2. [{"index":0,"type":0,"typeName":"head","images":[[18122,-5,-12]]}]
  3. [{"index":0,"type":0,"typeName":"head","images":[{"id":18122,"dx":-5,"dy":-12}]}]

Default output:
  {"index":0,"type":0,"typeName":"head","images":[[18122,-5,-12],[18123,-9,-15]]}`);
}

if (args.includes('--help') || args.includes('-h')) {
  usage();
  process.exit(0);
}

if (args.length < 2) {
  usage();
  process.exit(1);
}

const [inputPath, outputPath] = args;
const options = Object.fromEntries(
  args
    .slice(2)
    .filter((arg) => arg.startsWith('--') && arg.includes('='))
    .map((arg) => {
      const [key, ...value] = arg.slice(2).split('=');
      return [key, value.join('=')];
    }),
);

const defaultPart = {
  index: Number(options.index ?? 0),
  type: Number(options.type ?? 0),
  typeName: options.typeName ?? 'head',
};
const imageOutput = options.images ?? 'array';
const pretty = args.includes('--pretty');

if (!['array', 'object'].includes(imageOutput)) {
  throw new Error('--images must be "array" or "object".');
}

function toImage(value, path) {
  if (Array.isArray(value)) {
    if (value.length !== 3) {
      throw new Error(`${path} must have exactly 3 values [id, dx, dy].`);
    }

    const [id, dx, dy] = value;
    return { id: Number(id), dx: Number(dx), dy: Number(dy) };
  }

  if (
    value &&
    typeof value === 'object' &&
    'id' in value &&
    'dx' in value &&
    'dy' in value
  ) {
    return {
      id: Number(value.id),
      dx: Number(value.dx),
      dy: Number(value.dy),
    };
  }

  throw new Error(`${path} must be [id, dx, dy] or {id, dx, dy}.`);
}

function convertPart(part, fallback, partPath) {
  const formatImage = (image, imageIndex, prefix) => {
    const normalized = toImage(image, `${prefix}[${imageIndex}]`);
    return imageOutput === 'object'
      ? normalized
      : [normalized.id, normalized.dx, normalized.dy];
  };

  if (Array.isArray(part)) {
    return {
      ...fallback,
      images: part.map((image, imageIndex) => formatImage(image, imageIndex, partPath)),
    };
  }

  if (part && typeof part === 'object' && Array.isArray(part.images)) {
    return {
      index: Number(part.index ?? fallback.index),
      type: Number(part.type ?? fallback.type),
      typeName: String(part.typeName ?? fallback.typeName),
      images: part.images.map((image, imageIndex) => formatImage(image, imageIndex, `${partPath}.images`)),
    };
  }

  throw new Error(`${partPath} must be an images array or an object with an images array.`);
}

const raw = readFileSync(inputPath, 'utf8');
const data = JSON.parse(raw);

let converted;
if (Array.isArray(data) && data.every((item) => Array.isArray(item) && item.length === 3)) {
  converted = convertPart(data, defaultPart, basename(inputPath));
} else if (Array.isArray(data)) {
  converted = data.map((part, partIndex) =>
    convertPart(part, { ...defaultPart, index: partIndex }, `${basename(inputPath)}[${partIndex}]`),
  );
} else {
  converted = convertPart(data, defaultPart, basename(inputPath));
}

writeFileSync(outputPath, `${JSON.stringify(converted, null, pretty ? 2 : 0)}\n`, 'utf8');

console.log(`Converted ${inputPath} -> ${outputPath}`);
