document.addEventListener('DOMContentLoaded', () => {
    // 1. Asignar fecha actual automáticamente al cargar
    setFechaActual();

    // 2. Event Listener para agregar fila
    document.getElementById('btnAddRow').addEventListener('click', addRow);

    // 3. Event Listener para limpiar formulario
    document.getElementById('btnReset').addEventListener('click', resetForm);

    // 4. Event Listener para imprimir
    document.getElementById('btnPrint').addEventListener('click', () => {
        window.print();
    });
});

function setFechaActual() {
    const today = new Date();
    const meses = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

    document.getElementById('dia').value = today.getDate();
    document.getElementById('mes').value = meses[today.getMonth()];
    document.getElementById('anio').value = today.getFullYear();
}

function addRow() {
    const tableBody = document.getElementById('itemsTable').getElementsByTagName('tbody')[0];
    const newRow = tableBody.insertRow();

    const cellUnits = newRow.insertCell(0);
    const cellDesc = newRow.insertCell(1);

    cellUnits.innerHTML = '<input type="text" name="unidades[]">';
    cellDesc.innerHTML = '<input type="text" name="descripcion[]">';
}

function resetForm() {
    if (confirm("¿Deseas restablecer todos los campos del formulario?")) {
        document.getElementById('solicitudForm').reset();
        setFechaActual();
    }
}