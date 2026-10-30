import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import assert from 'node:assert/strict';
import ts from 'typescript';

const root = new URL('../../', import.meta.url);
const source = readFileSync(new URL('resources/js/lib/trazaSemantica.ts', root), 'utf8');
const js = ts.transpileModule(source, { compilerOptions: { target: ts.ScriptTarget.ES2022, module: ts.ModuleKind.ES2022 } }).outputText;
const { convertirTraza } = await import(`data:text/javascript;base64,${Buffer.from(js).toString('base64')}`);
const trace = () => JSON.parse(readFileSync(new URL('tests/Fixtures/actividad/parser_contador_v1.json', root), 'utf8'));
const code = 'contador = 0\nsuma = 0\nwhile contador < 3:\n    contador += 1\n    suma += contador';

test('consume el resultado del paso y conserva el estado de memoria en la traza 1.0', () => {
    const view = convertirTraza(trace(), code);
    assert.equal(view.pasos.length, 12);
    assert.deepEqual(view.pasos.at(-1).memoria, { contador: 3, suma: 6 });
    assert.equal(view.pasos.at(-1).condicion.resultado, false);
    assert.equal(view.pasos.at(-1).iteracion, 3);
    assert.equal(view.pasos[2].iteracion, 0);
    assert.equal(view.pasos[2].condicion.resultado, true);
});

test('ignora campos nuevos de una version menor', () => {
    const doc = trace();
    doc.versionFormato = '1.1';
    doc.nuevoCampo = { detalle: true };
    assert.equal(convertirTraza(doc, code).pasos.length, 12);
});

test('rechaza versiones mayores desconocidas', () => {
    const doc = trace();
    doc.versionFormato = '2.0';
    assert.throws(() => convertirTraza(doc, code), /Versión/);
});

test('rechaza pasos fuera del orden de ejecucion', () => {
    const doc = trace();
    [doc['@graph'][0], doc['@graph'][1]] = [doc['@graph'][1], doc['@graph'][0]];
    assert.throws(() => convertirTraza(doc, code), /consecutivos/);
});

test('una traza versionada no acepta resultado dentro de evalua', () => {
    const doc = trace();
    doc['@graph'][2].evalua.resultado = doc['@graph'][2].resultado;
    delete doc['@graph'][2].resultado;
    assert.throws(() => convertirTraza(doc, code), /resultado booleano/);
});
