/*
 * Trazas de dos programas concretos. No analizan ni ejecutan código Java libre.
 * Cada paso contiene una copia independiente de la memoria DESPUÉS de la línea.
 * El motor real podrá entregar este mismo contrato junto a su código fuente.
 */
export type ValorMemoria = number | number[];
export type Memoria = Record<string, ValorMemoria>;

export interface PasoTraza {
    linea: number; // Numeración del código desde 1.
    titulo: string;
    explicacion: string; // Mostrar solo después de confirmar el paso.
    memoria: Memoria;
    salida: string[];
    reto?: { variable: string; pista: string };
    ciclo?: { tipo: 'while' | 'for'; iteracion: number; condicion: string; resultado: boolean };
    acceso?: { arreglo: string; indice: number };
}

export interface EjemploDepuracion {
    id: string;
    titulo: string;
    descripcion: string;
    archivo: string;
    codigo: string;
    pasos: PasoTraza[];
}

function copiarMemoria(memoria: Memoria): Memoria {
    return Object.fromEntries(Object.entries(memoria).map(([nombre, valor]) => [nombre, Array.isArray(valor) ? [...valor] : valor]));
}

function crearRegistro() {
    const pasos: PasoTraza[] = [];
    const agregar = (paso: PasoTraza) => pasos.push({ ...paso, memoria: copiarMemoria(paso.memoria), salida: [...paso.salida] });
    return { pasos, agregar };
}

function ejemploWhile(): EjemploDepuracion {
    const { pasos, agregar } = crearRegistro();
    const memoria: Memoria = {};
    memoria.contador = 0;
    agregar({ linea: 3, titulo: 'Inicializar contador', explicacion: 'contador se inicializa con el valor 0.', memoria, salida: [], reto: { variable: 'contador', pista: 'Observa el valor que se asigna a contador.' } });
    memoria.suma = 0;
    agregar({ linea: 4, titulo: 'Inicializar suma', explicacion: 'suma se inicializa con el valor 0.', memoria, salida: [], reto: { variable: 'suma', pista: 'Observa el valor inicial de suma.' } });

    for (let contador = 0; contador <= 3; contador++) {
        const continua = contador < 3;
        const ciclo: NonNullable<PasoTraza['ciclo']> = {
            tipo: 'while', iteracion: continua ? contador + 1 : contador,
            condicion: `${contador} < 3`, resultado: continua,
        };
        agregar({ linea: 5, titulo: 'Evaluar condición', explicacion: continua ? `${contador} < 3 es verdadero: se entra al cuerpo del ciclo.` : '3 < 3 es falso: termina el ciclo.', memoria, salida: [], ciclo });
        if (!continua) break;
        memoria.contador = contador + 1;
        agregar({ linea: 6, titulo: 'Incrementar contador', explicacion: `contador pasa de ${contador} a ${contador + 1}.`, memoria, salida: [], ciclo, reto: { variable: 'contador', pista: 'El operador += 1 suma una unidad al valor anterior.' } });
        const sumaAnterior = memoria.suma as number;
        memoria.suma = sumaAnterior + (memoria.contador as number);
        agregar({ linea: 7, titulo: 'Acumular en suma', explicacion: `${sumaAnterior} + ${memoria.contador} = ${memoria.suma}.`, memoria, salida: [], ciclo, reto: { variable: 'suma', pista: 'Suma al acumulador el valor actual de contador.' } });
    }
    agregar({ linea: 9, titulo: 'Mostrar resultado', explicacion: 'Se escribe el valor de suma en la consola. El programa ha terminado.', memoria, salida: [String(memoria.suma)] });
    return {
        id: 'while', titulo: 'Contador y acumulador · while', descripcion: 'Predice cómo cambian contador y suma en cada iteración.', archivo: 'Main.java', pasos,
        codigo: `public class Main {
    public static void main(String[] args) {
        int contador = 0;
        int suma = 0;
        while (contador < 3) {
            contador += 1;
            suma += contador;
        }
        System.out.println(suma);
    }
}`,
    };
}

function ejemploArreglo(): EjemploDepuracion {
    const { pasos, agregar } = crearRegistro();
    const numeros = [2, 4, 6];
    const memoria: Memoria = { numeros };
    agregar({ linea: 3, titulo: 'Crear arreglo', explicacion: 'El arreglo numeros contiene tres elementos. Sus índices son 0, 1 y 2.', memoria, salida: [] });
    memoria.suma = 0;
    agregar({ linea: 4, titulo: 'Inicializar suma', explicacion: 'El acumulador suma comienza en 0.', memoria, salida: [], reto: { variable: 'suma', pista: 'Observa el valor que recibe suma al declararse.' } });
    memoria.i = 0;
    agregar({ linea: 5, titulo: 'Inicializar índice', explicacion: 'i comienza en 0. Esta inicialización ocurre una sola vez.', memoria, salida: [], reto: { variable: 'i', pista: 'La primera parte del for declara el índice inicial.' } });

    for (let i = 0; i <= numeros.length; i++) {
        const continua = i < numeros.length;
        const ciclo: NonNullable<PasoTraza['ciclo']> = {
            tipo: 'for', iteracion: continua ? i + 1 : i,
            condicion: `${i} < ${numeros.length}`, resultado: continua,
        };
        agregar({ linea: 5, titulo: 'Evaluar condición', explicacion: continua ? `${i} < ${numeros.length} es verdadero: se puede entrar al ciclo.` : 'El índice llega a la longitud del arreglo. La condición es falsa y no se accede a numeros[3].', memoria, salida: [], ciclo });
        if (!continua) break;
        const sumaAnterior = memoria.suma as number;
        memoria.suma = sumaAnterior + numeros[i];
        agregar({ linea: 6, titulo: 'Sumar elemento', explicacion: `numeros[${i}] vale ${numeros[i]}; ${sumaAnterior} + ${numeros[i]} = ${memoria.suma}.`, memoria, salida: [], ciclo, acceso: { arreglo: 'numeros', indice: i }, reto: { variable: 'suma', pista: 'Añade a suma el elemento del arreglo señalado por i.' } });
        memoria.i = i + 1;
        agregar({ linea: 5, titulo: 'Incrementar índice', explicacion: `i++ cambia el índice de ${i} a ${i + 1}. Después se vuelve a evaluar la condición.`, memoria, salida: [], ciclo, reto: { variable: 'i', pista: 'Después del cuerpo del for se ejecuta i++.' } });
    }
    // i fue declarado dentro del for; sale de su ámbito al terminar el ciclo.
    delete memoria.i;
    agregar({ linea: 8, titulo: 'Mostrar resultado', explicacion: 'Se imprime 12. i ya salió de su ámbito; numeros y suma siguen disponibles en main.', memoria, salida: [String(memoria.suma)] });
    return {
        id: 'arreglo', titulo: 'Suma de un arreglo · for', descripcion: 'Relaciona el índice i con cada elemento de numeros y con el acumulador suma.', archivo: 'Main.java', pasos,
        codigo: `public class Main {
    public static void main(String[] args) {
        int[] numeros = {2, 4, 6};
        int suma = 0;
        for (int i = 0; i < numeros.length; i++) {
            suma += numeros[i];
        }
        System.out.println(suma);
    }
}`,
    };
}

export const ejemplosDepuracion: EjemploDepuracion[] = [ejemploWhile(), ejemploArreglo()];
