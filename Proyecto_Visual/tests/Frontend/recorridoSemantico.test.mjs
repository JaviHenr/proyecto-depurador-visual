import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { effectScope, shallowRef } from 'vue';
import ts from 'typescript';

const root = new URL('../../', import.meta.url);
async function importarTS(path) {
    const codigo = readFileSync(new URL(path, root), 'utf8').replace("from 'vue'", `from '${import.meta.resolve('vue')}'`);
    const js = ts.transpileModule(codigo, { compilerOptions: { target: ts.ScriptTarget.ES2022, module: ts.ModuleKind.ES2022 } }).outputText;
    return import(`data:text/javascript;base64,${Buffer.from(js).toString('base64')}`);
}
const { convertirTraza, codigoEjemploSemantico } = await importarTS('resources/js/lib/trazaSemantica.ts');
const { useDepurador } = await importarTS('resources/js/composables/useDepurador.ts');
const documento = () => JSON.parse(readFileSync(new URL('tests/Fixtures/actividad/parser_contador_v1.json', root), 'utf8'));
const referencia = { codigo: 5, huella_traza: 'a'.repeat(64) };
const resultado = (numeroPaso, correcto, valorEsperado) => ({ numeroPaso, correcto, valorEsperado, tipoError: correcto ? null : 'CondicionErronea', mensaje: 'Retroalimentación del motor' });

function recorrido(t, validar, traza = documento()) {
    const scope = effectScope();
    const ejemplo = shallowRef({ ...convertirTraza(traza, codigoEjemploSemantico), referenciaTraza: referencia });
    const visor = scope.run(() => useDepurador(ejemplo, validar));
    t.after(() => scope.stop());
    return { visor, ejemplo };
}

async function responder(visor, texto) {
    visor.respuesta.value = texto;
    await visor.confirmar();
}

test('las condiciones detienen el recorrido y el motor recibe false como booleano', async t => {
    const peticiones = [];
    const { visor } = recorrido(t, async (origen, paso, valor) => {
        peticiones.push([origen, paso, valor]);
        return paso === 3 ? resultado(paso, valor === true, true) : resultado(paso, true, 0);
    });
    visor.iniciar();
    await responder(visor, '0');
    visor.avanzar();
    await responder(visor, '0');
    visor.avanzar();
    assert.equal(visor.paso.value.reto.tipo, 'condicion');
    assert.equal(visor.esperando.value, true);
    await responder(visor, 'false');
    assert.equal(visor.confirmado.value, false);
    assert.deepEqual(peticiones.at(-1), [referencia, 3, false]);
    assert.equal(visor.feedback.value.texto, 'Retroalimentación del motor');
    assert.equal(visor.feedback.value.valorEsperado, true);
    visor.avanzar();
    assert.equal(visor.indice.value, 2);
    await responder(visor, 'true');
    assert.equal(visor.puedeAvanzar.value, true);
    assert.deepEqual(peticiones[0], [referencia, 1, 0]);
});

test('el último paso solo finaliza al confirmar el resultado falso de la condición', async t => {
    const { visor } = recorrido(t, async (_, paso, valor) => resultado(paso, paso !== 12 || valor === false, paso === 12 ? false : valor));
    const respuestas = ['0', '0', 'true', '1', '1', 'true', '2', '3', 'true', '3', '6'];
    for (const valor of respuestas) {
        visor.avanzar();
        await responder(visor, valor);
    }
    visor.avanzar();
    await responder(visor, 'true');
    assert.equal(visor.terminado.value, false);
    await responder(visor, 'false');
    assert.equal(visor.terminado.value, true);
    assert.equal(visor.feedback.value.valorEsperado, false);
    assert.deepEqual(visor.memoria.value, { contador: 3, suma: 6 });
});

test('reiniciar cancela la consulta y descarta una respuesta que llega tarde', async t => {
    let resolver, signal, cantidad = 0;
    const { visor } = recorrido(t, (_, _paso, _valor, control) => {
        ++cantidad;
        signal = control;
        return new Promise(resolve => { resolver = resolve; });
    });
    visor.avanzar();
    const pendiente = responder(visor, '0');
    await visor.confirmar();
    assert.equal(cantidad, 1);
    assert.equal(visor.validando.value, true);
    visor.reiniciar();
    assert.equal(signal.aborted, true);
    resolver(resultado(1, true, 0));
    await pendiente;
    assert.equal(visor.indice.value, -1);
    assert.equal(visor.confirmado.value, false);
    assert.equal(visor.feedback.value, null);
});

test('una falla de conexión deja el paso pendiente y permite reintentar', async t => {
    let intentos = 0;
    const { visor } = recorrido(t, async (_, paso) => {
        if (++intentos === 1) throw new Error('El motor no está disponible');
        return resultado(paso, true, 0);
    });
    visor.avanzar();
    await responder(visor, '0');
    assert.equal(visor.esperando.value, true);
    assert.equal(visor.validando.value, false);
    assert.match(visor.feedback.value.texto, /no está disponible/);
    await visor.confirmar();
    assert.equal(visor.confirmado.value, true);
});

test('observar y las trazas con versiones no admitidas no consultan el motor', async t => {
    const traza = documento();
    traza.versionFormato = '1.1';
    const { visor, ejemplo } = recorrido(t, async () => assert.fail('No debía consultar el motor'), traza);
    visor.avanzar();
    assert.equal(visor.modo.value, 'observar');
    assert.equal(visor.confirmado.value, true);
    await visor.confirmar();
    ejemplo.value = { ...convertirTraza(documento(), codigoEjemploSemantico), referenciaTraza: referencia };
    visor.modo.value = 'observar';
    visor.avanzar();
    await visor.confirmar();
    assert.equal(visor.confirmado.value, true);
});

test('una modificación con valor decimal queda en el recorrido sin pedir un entero', () => {
    const traza = documento();
    const paso = traza['@graph'][0];
    paso.modifica.valorNuevo = 1.5;
    traza['@graph'] = [paso];
    const vista = convertirTraza(traza, codigoEjemploSemantico);
    assert.equal(vista.pasos[0].reto, undefined);
    assert.equal(vista.pasos[0].memoria.contador, 1.5);
});
