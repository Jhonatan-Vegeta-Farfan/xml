<?php
// Conexión a la base de datos MySQL usando PDO (PHP Data Objects)
// Se establece conexión con el servidor localhost, base de datos 'sigi_huanta'
// Se especifica codificación UTF-8 y se usan credenciales root/root
$pdo = new PDO(
    "mysql:host=localhost;dbname=sigi_huanta;charset=utf8", 
    "root", 
    "root");

// Crear un nuevo documento XML con versión 1.0 y codificación UTF-8
// DOMDocument es una clase de PHP para manipular documentos XML
$dom = new DOMDocument('1.0', 'UTF-8');
// Activar formato de salida con sangrías y saltos de línea para mejor legibilidad
$dom->formatOutput = true;
// Crear el elemento raíz del XML llamado 'instituto'
$instituto = $dom->createElement('IESP_HUANTA');
// Añadir el elemento raíz como hijo del documento XML
$dom->appendChild($instituto);

// Mapear tablas de la base de datos a elementos XML
// Cada tabla tiene: nombre de elemento principal, consulta SQL y nombre de elementos hijos
$tablas = [
    'programas_estudios' => ['elemento' => 'programa', 'sql' => "SELECT id, codigo, nombre FROM sigi_programa_estudios"],
    'planes_estudio' => ['elemento' => 'plan', 'sql' => "SELECT id, id_programa_estudios, nombre, resolucion, fecha_registro FROM sigi_planes_estudio"],
    'modulos_formativos' => ['elemento' => 'modulo', 'sql' => "SELECT id, descripcion, nro_modulo, id_plan_estudio FROM sigi_modulo_formativo"],
    'semestres' => ['elemento' => 'semestre', 'sql' => "SELECT id, descripcion, id_modulo_formativo FROM sigi_semestre"]
];

// Recorrer cada tabla definida en el arreglo $tablas
foreach ($tablas as $seccion => $config) {
    // Crear un elemento XML con el nombre de la sección (ej: 'programas_estudios')
    $elementoSeccion = $dom->createElement($seccion);
    // Añadir esta sección como hijo del elemento raíz 'instituto'
    $instituto->appendChild($elementoSeccion);
    
    // Ejecutar la consulta SQL definida para esta tabla
    $stmt = $pdo->query($config['sql']);
    // Recorrer cada fila de resultados de la consulta
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Crear un elemento hijo con el nombre especificado (ej: 'programa')
        $item = $dom->createElement($config['elemento']);
        // Añadir este elemento como hijo de la sección
        $elementoSeccion->appendChild($item);
        
        // Recorrer cada columna de la fila actual
        foreach ($row as $key => $value) {
            // Validar que el nombre de la columna sea válido para XML
            // Reemplazar caracteres no alfanuméricos con guión bajo
            $elementName = preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
            // Si el nombre no comienza con letra o guión bajo, agregar prefijo 'field_'
            if (!preg_match('/^[a-zA-Z_]/', $elementName)) {
                $elementName = 'field_' . $elementName;
            }
            // Crear elemento XML con el nombre validado y el valor escapado
            // htmlspecialchars previene problemas con caracteres especiales XML
            $item->appendChild($dom->createElement($elementName, htmlspecialchars($value)));
        }
    }
}

// Sección especial para unidades didácticas (requiere cálculos adicionales)
// Crear elemento 'unidades_didacticas' como hijo de 'instituto'
$unidades = $dom->createElement('unidades_didacticas');
$instituto->appendChild($unidades);

// Consulta SQL especial que incluye cálculos de horas:
// - horas_teoricas_semanal: créditos teóricos × 1 hora
// - horas_practicas_semanal: créditos prácticos × 2 horas
// - horas_totales_semanal: suma de horas teóricas y prácticas semanales
// - horas_semestrales: horas totales semanales × 16 semanas
$sql = "SELECT id, nombre, id_semestre, creditos_teorico, creditos_practico, tipo, orden,
        (creditos_teorico * 1) as horas_teoricas_semanal,
        (creditos_practico * 2) as horas_practicas_semanal,
        ((creditos_teorico * 1) + (creditos_practico * 2)) as horas_totales_semanal,
        (((creditos_teorico * 1) + (creditos_practico * 2)) * 16) as horas_semestrales
        FROM sigi_unidad_didactica";

// Ejecutar consulta de unidades didácticas
$stmt = $pdo->query($sql);
// Recorrer cada unidad didáctica
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Crear elemento 'unidad' para cada registro
    $unidad = $dom->createElement('unidad');
    $unidades->appendChild($unidad);
    
    // Elementos regulares (datos directos de la base de datos)
    $camposRegulares = ['id', 'nombre', 'id_semestre', 'creditos_teorico', 'creditos_practico', 'tipo', 'orden'];
    foreach ($camposRegulares as $campo) {
        // Verificar que el campo exista en los resultados
        if (isset($row[$campo])) {
            // Añadir elemento con el nombre del campo y su valor
            $unidad->appendChild($dom->createElement($campo, htmlspecialchars($row[$campo])));
        }
    }
    
    // Crear sección especial 'horas' para agrupar los cálculos
    $horas = $dom->createElement('horas');
    $unidad->appendChild($horas);
    // Añadir cada tipo de horas calculadas como elementos hijos de 'horas'
    $horas->appendChild($dom->createElement('teoricas_semanal', $row['horas_teoricas_semanal']));
    $horas->appendChild($dom->createElement('practicas_semanal', $row['horas_practicas_semanal']));
    $horas->appendChild($dom->createElement('totales_semanal', $row['horas_totales_semanal']));
    $horas->appendChild($dom->createElement('semestrales', $row['horas_semestrales']));
}

// Guardar el documento XML completo en el archivo 'sigi.xml'
$dom->save('sigi.xml');
// Mostrar mensaje de confirmación
echo "sigi.xml creado exitosamente";
?>