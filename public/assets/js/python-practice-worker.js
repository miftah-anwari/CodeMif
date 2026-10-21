'use strict';

const pyodideBaseUrl = 'https://cdn.jsdelivr.net/pyodide/v0.27.7/full/';
let pyodidePromise;

async function initializePython() {
  if (!pyodidePromise) {
    importScripts(`${pyodideBaseUrl}pyodide.js`);
    pyodidePromise = loadPyodide({ indexURL: pyodideBaseUrl });
  }
  return pyodidePromise;
}

self.addEventListener('message', async (event) => {
  try {
    const pyodide = await initializePython();
    self.postMessage({ type: 'ready' });
    pyodide.setStdout({ batched: (text) => self.postMessage({ type: 'output', text: `${text}\n` }) });
    pyodide.setStderr({ batched: (text) => self.postMessage({ type: 'output', text: `${text}\n` }) });
    let inputBuffer = (event.data.inputs || []).map((value) => `${value}\n`).join('');
    pyodide.setStdin({
      stdin: () => {
        if (!inputBuffer.length) return null;
        const codePoint = inputBuffer.codePointAt(0);
        inputBuffer = inputBuffer.slice(codePoint > 0xffff ? 2 : 1);
        return codePoint;
      },
    });
    await pyodide.runPythonAsync(event.data.code);
    self.postMessage({ type: 'done' });
  } catch (error) {
    self.postMessage({ type: 'error', message: error instanceof Error ? error.message : String(error) });
  }
});
