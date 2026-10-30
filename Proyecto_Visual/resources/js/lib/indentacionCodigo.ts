export function insertarIndentacion(evento: KeyboardEvent): void {
    if (evento.key !== 'Tab' || evento.shiftKey || evento.ctrlKey || evento.altKey || evento.metaKey || evento.isComposing) {
        return;
    }

    const editor = evento.currentTarget as HTMLTextAreaElement | null;
    if (!editor || editor.disabled || editor.readOnly) {
        return;
    }

    evento.preventDefault();

    const espacios = '    ';
    const inicio = editor.selectionStart;
    const fin = editor.selectionEnd;
    const longitudNueva = editor.value.length - (fin - inicio) + espacios.length;
    if (editor.maxLength >= 0 && longitudNueva > editor.maxLength) {
        return;
    }

    editor.setRangeText(espacios, inicio, fin, 'end');
    editor.dispatchEvent(new Event('input', { bubbles: true }));
}
