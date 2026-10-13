import type { EjemploDepuracion, Memoria, PasoTraza, ValorMemoria } from './ejemplosDepuracion';

export const codigoEjemploSemantico = 'contador = 0\nsuma = 0\nwhile contador < 3:\n    contador += 1\n    suma += contador';

type Objeto = Record<string, unknown>;
const objeto = (valor: unknown): valor is Objeto => valor !== null && typeof valor === 'object' && !Array.isArray(valor);
const propio = (valor: Objeto, clave: string) => Object.hasOwn(valor, clave);
export const normalizarFuente = (codigo: string) => codigo.replace(/\r\n?/g, '\n');
export const formatoNormalizado = (formato: string) => formato.trim().toLowerCase();
export const mostrarValor = (valor: ValorMemoria) => JSON.stringify(valor);
export const valoresIguales = (a: unknown, b: unknown) => JSON.stringify(a) === JSON.stringify(b);

function fallo(texto: string): never { throw new Error(texto); }
function texto(valor: unknown, campo: string): string {
    return typeof valor === 'string' && valor.trim() && !valor.includes('\0') ? valor : fallo(`${campo} debe ser texto no vacío.`);
}
function variable(valor: unknown): string {
    const nombre = texto(valor, 'variable');
    if (!/^[\p{L}_][\p{L}\p{N}_]*$/u.test(nombre) || ['__proto__', 'constructor', 'prototype'].includes(nombre)) fallo('Nombre de variable no válido.');
    return nombre;
}
function esValor(valor: unknown, lista = true): valor is ValorMemoria {
    return valor === null || typeof valor === 'boolean'
        || (typeof valor === 'string' && !valor.includes('\0'))
        || (typeof valor === 'number' && Number.isFinite(valor) && Math.abs(valor) <= Number.MAX_SAFE_INTEGER)
        || (lista && Array.isArray(valor) && valor.length <= 1000 && valor.every(item => esValor(item, false)));
}
const copiarValor = (valor: ValorMemoria): ValorMemoria => Array.isArray(valor) ? [...valor] : valor;
const copiarMemoria = (memoria: Memoria): Memoria => Object.fromEntries(Object.entries(memoria).map(([nombre, valor]) => [nombre, copiarValor(valor)]));

/** Lee eventos del motor. Nunca interpreta ni ejecuta las expresiones del programa. */
export function convertirTraza(documento: unknown, fuente: string, archivo = 'contador_suma.py', titulo = 'Contador y suma'): EjemploDepuracion {
    if (!objeto(documento) || !Array.isArray(documento['@graph']) || !documento['@graph'].length || documento['@graph'].length > 5000) fallo('La traza debe tener @graph con entre 1 y 5000 pasos.');
    const eventos = [...documento['@graph']];
    for (const evento of eventos) {
        if (!objeto(evento) || evento['@type'] !== 'PasoDeEjecucion' || !Number.isInteger(evento.numeroPaso)) fallo('Cada evento debe ser PasoDeEjecucion con numeroPaso entero.');
    }
    eventos.sort((a, b) => a.numeroPaso - b.numeroPaso);
    const cantidadLineas = normalizarFuente(fuente).split('\n').length;
    let memoria: Memoria = {};
    let salida: string[] = [];
    const pasos: PasoTraza[] = [];

    for (const [indice, evento] of eventos.entries()) {
        const numero = indice + 1;
        if (evento.numeroPaso !== numero) fallo('Los pasos deben ser consecutivos desde 1, sin duplicados.');
        if (!Number.isInteger(evento.linea) || evento.linea < 1 || evento.linea > cantidadLineas) fallo(`Línea no válida en el paso ${numero}.`);
        const instruccion = texto(evento.instruccion, 'instruccion');
        const iteracion = evento.iteracion ?? null;
        if (iteracion !== null && (!Number.isInteger(iteracion) || iteracion < 0)) fallo('iteracion debe ser un entero no negativo o null.');
        const paso: PasoTraza = { numeroPaso: numero, linea: evento.linea, titulo: instruccion, explicacion: '', memoria: {}, salida: [], iteracion };
        const explicaciones: string[] = [];
        let accion = false;

        if (propio(evento, 'modifica')) {
            accion = true;
            const cambio = evento.modifica;
            if (!objeto(cambio) || cambio['@type'] !== 'EstadoDeMemoria' || !propio(cambio, 'valorAnterior') || !propio(cambio, 'valorNuevo')) fallo(`modifica no es válido en el paso ${numero}.`);
            const nombre = variable(cambio.variable);
            if (!esValor(cambio.valorAnterior) || !esValor(cambio.valorNuevo)) fallo('Los valores deben ser escalares JSON o arreglos planos.');
            const previo = Object.hasOwn(memoria, nombre) ? memoria[nombre] : null;
            if (!valoresIguales(previo, cambio.valorAnterior)) fallo(`valorAnterior de ${nombre} no coincide en el paso ${numero}.`);
            const existia = Object.hasOwn(memoria, nombre);
            memoria = { ...memoria, [nombre]: copiarValor(cambio.valorNuevo) };
            if (cambio.ambito !== undefined) paso.ambito = texto(cambio.ambito, 'ambito');
            explicaciones.push(existia ? `${nombre}: ${mostrarValor(cambio.valorAnterior)} → ${mostrarValor(cambio.valorNuevo)}.` : `${nombre} se inicializa con ${mostrarValor(cambio.valorNuevo)}.`);
            // Las modificaciones escalares admiten predicción. Los arreglos se muestran directamente.
            if (!Array.isArray(cambio.valorNuevo)) paso.reto = { variable: nombre, pista: 'Usa el valor anterior y la instrucción resaltada para predecir el nuevo valor.' };
        }
        if (propio(evento, 'evalua')) {
            accion = true;
            const condicion = evento.evalua;
            if (!objeto(condicion) || condicion['@type'] !== 'Condicion' || typeof condicion.resultado !== 'boolean') fallo('evalua debe incluir Condicion y resultado booleano.');
            const expresion = texto(condicion.expresion, 'expresion');
            const dependencias = condicion.dependeDe ?? [];
            if (!Array.isArray(dependencias)) fallo('dependeDe debe ser una lista.');
            const dependeDe = dependencias.map(variable);
            if (dependeDe.some(nombre => !Object.hasOwn(memoria, nombre))) fallo('La condición usa una variable no declarada.');
            paso.condicion = { expresion, resultado: condicion.resultado, dependeDe };
            explicaciones.push(`La condición ${expresion} es ${condicion.resultado ? 'verdadera' : 'falsa'}.`);
        }
        if (propio(evento, 'salida')) {
            accion = true;
            const nueva = typeof evento.salida === 'string' ? [evento.salida] : evento.salida;
            if (!Array.isArray(nueva) || nueva.some(item => typeof item !== 'string' || item.includes('\0'))) fallo('salida debe ser un texto o una lista de textos.');
            salida = [...salida, ...nueva];
            if (nueva.length) explicaciones.push('Se agrega una salida a la consola.');
        }
        if (!accion) fallo(`El paso ${numero} no contiene modifica, evalua ni salida.`);
        paso.explicacion = explicaciones.join(' ');
        paso.memoria = copiarMemoria(memoria);
        paso.salida = [...salida];
        pasos.push(paso);
    }
    return { id: 'traza-semantica', titulo, descripcion: 'Traza JSON generada por el motor. Recorre las líneas y sus cambios de memoria.', archivo, codigo: fuente, pasos };
}


/** Vista de instrucciones: el JSON no incluye un archivo fuente completo. */
export function fuenteDesdeJSON(documento: unknown): { codigo: string; esEjemplo: boolean } {
    if (!objeto(documento) || !Array.isArray(documento['@graph']) || !documento['@graph'].length) fallo('El JSON debe incluir @graph.');
    const instrucciones = new Map<number, Set<string>>();
    const conocidas: Record<number, string> = { 1: 'contador = 0', 2: 'suma = 0', 3: 'evaluar contador < 3', 4: 'contador += 1', 5: 'suma += contador' };
    let esEjemplo = true;
    for (const evento of documento['@graph']) {
        if (!objeto(evento) || typeof evento.linea !== 'number' || !Number.isInteger(evento.linea) || evento.linea < 1 || evento.linea > 10000) fallo('La línea de cada paso debe estar entre 1 y 10000.');
        const instruccion = texto(evento.instruccion, 'instruccion');
        esEjemplo &&= conocidas[evento.linea] === instruccion.trim();
        const fila = instrucciones.get(evento.linea) ?? new Set<string>();
        fila.add(instruccion);
        instrucciones.set(evento.linea, fila);
    }
    esEjemplo &&= instrucciones.size === 5;
    if (esEjemplo) return { codigo: codigoEjemploSemantico, esEjemplo: true };
    const ultimaLinea = Math.max(...instrucciones.keys());
    return { codigo: Array.from({ length: ultimaLinea }, (_, i) => [...(instrucciones.get(i + 1) ?? [])].join(' | ')).join('\n'), esEjemplo: false };
}
