<?php
$host = 'localhost';
$dbname = 'sigi_huanta';
$username = 'root';
$password = 'root';

try {
    // Conectar a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Crear XML
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;
    $instituto = $dom->createElement('instituto');
    $dom->appendChild($instituto);
    
    // Programas
    $programasEstudios = $dom->createElement('programas_estudios');
    $instituto->appendChild($programasEstudios);
    
    $stmt = $pdo->query("SELECT id, codigo, nombre FROM sigi_programa_estudios");
    while ($row = $stmt->fetch()) {
        $programa = $dom->createElement('programa');
        $programasEstudios->appendChild($programa);
        
        $programa->appendChild($dom->createElement('id', $row['id']));
        $programa->appendChild($dom->createElement('codigo', $row['codigo']));
        $programa->appendChild($dom->createElement('nombre', $row['nombre']));
    }
    
    // Planes
    $planesEstudio = $dom->createElement('planes_estudio');
    $instituto->appendChild($planesEstudio);
    
    $stmt = $pdo->query("SELECT id, id_programa_estudios, nombre, resolucion, fecha_registro FROM sigi_planes_estudio");
    while ($row = $stmt->fetch()) {
        $plan = $dom->createElement('plan');
        $planesEstudio->appendChild($plan);
        
        $plan->appendChild($dom->createElement('id', $row['id']));
        $plan->appendChild($dom->createElement('id_programa_estudios', $row['id_programa_estudios']));
        $plan->appendChild($dom->createElement('nombre', $row['nombre']));
        $plan->appendChild($dom->createElement('resolucion', $row['resolucion']));
        $plan->appendChild($dom->createElement('fecha_registro', $row['fecha_registro']));
    }
    
    // Módulos
    $modulosFormativos = $dom->createElement('modulos_formativos');
    $instituto->appendChild($modulosFormativos);
    
    $stmt = $pdo->query("SELECT id, descripcion, nro_modulo, id_plan_estudio FROM sigi_modulo_formativo");
    while ($row = $stmt->fetch()) {
        $modulo = $dom->createElement('modulo');
        $modulosFormativos->appendChild($modulo);
        
        $modulo->appendChild($dom->createElement('id', $row['id']));
        $modulo->appendChild($dom->createElement('descripcion', $row['descripcion']));
        $modulo->appendChild($dom->createElement('nro_modulo', $row['nro_modulo']));
        $modulo->appendChild($dom->createElement('id_plan_estudio', $row['id_plan_estudio']));
    }
    
    // Semestres
    $semestresElement = $dom->createElement('semestres');
    $instituto->appendChild($semestresElement);
    
    $stmt = $pdo->query("SELECT id, descripcion, id_modulo_formativo FROM sigi_semestre");
    while ($row = $stmt->fetch()) {
        $semestre = $dom->createElement('semestre');
        $semestresElement->appendChild($semestre);
        
        $semestre->appendChild($dom->createElement('id', $row['id']));
        $semestre->appendChild($dom->createElement('descripcion', $row['descripcion']));
        $semestre->appendChild($dom->createElement('id_modulo_formativo', $row['id_modulo_formativo']));
    }
    
    // Unidades didácticas
    $unidadesDidacticas = $dom->createElement('unidades_didacticas');
    $instituto->appendChild($unidadesDidacticas);
    
    $sql = "SELECT id, nombre, id_semestre, creditos_teorico, creditos_practico, tipo, orden,
            (creditos_teorico * 1) as horas_teoricas_semanal,
            (creditos_practico * 2) as horas_practicas_semanal,
            ((creditos_teorico * 1) + (creditos_practico * 2)) as horas_totales_semanal,
            (((creditos_teorico * 1) + (creditos_practico * 2)) * 16) as horas_semestrales
            FROM sigi_unidad_didactica";
    
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch()) {
        $unidad = $dom->createElement('unidad');
        $unidadesDidacticas->appendChild($unidad);
        
        $unidad->appendChild($dom->createElement('id', $row['id']));
        $unidad->appendChild($dom->createElement('nombre', $row['nombre']));
        $unidad->appendChild($dom->createElement('id_semestre', $row['id_semestre']));
        $unidad->appendChild($dom->createElement('creditos_teorico', $row['creditos_teorico']));
        $unidad->appendChild($dom->createElement('creditos_practico', $row['creditos_practico']));
        $unidad->appendChild($dom->createElement('tipo', $row['tipo']));
        $unidad->appendChild($dom->createElement('orden', $row['orden']));
        
        $horas = $dom->createElement('horas');
        $unidad->appendChild($horas);
        $horas->appendChild($dom->createElement('teoricas_semanal', $row['horas_teoricas_semanal']));
        $horas->appendChild($dom->createElement('practicas_semanal', $row['horas_practicas_semanal']));
        $horas->appendChild($dom->createElement('totales_semanal', $row['horas_totales_semanal']));
        $horas->appendChild($dom->createElement('semestrales', $row['horas_semestrales']));
    }
    
    // Guardar en archivo
    $dom->save('sigi.xml');
    
    echo "XML generado y guardado como: sigi.xml";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>