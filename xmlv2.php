<?php
// Conexión a la base de datos MySQL usando PDO (PHP Data Objects)
$conexion = new mysqli("localhost", "root", "root", "sigi_huanta");
if ($conexion->connect_error) {
    echo "Error de conexión a MySQL: (" . $conexion->connect_error . ")" . $conexion->connect_error;
}
$xml = new DOMDocument('1.0', 'UTF-8');
$xml->formatOutput = true;

$et1 = $xml->createElement('programas_estudio');
$xml->appendChild($et1);

$consulta = "SELECT * FROM sigi_programa_estudios";
$resultado = $conexion->query($consulta);
while ($pe = mysqli_fetch_assoc($resultado)) {
    echo $pe['nombre'] . "<br>";
    $num_pe = $xml->createElement('pe_'.$pe['id']);
    $codigo_pe = $xml->createElement('codigo', $pe['codigo']);
    $num_pe->appendChild($codigo_pe);
    $tipo_pe = $xml->createElement('tipo', $pe['tipo']);
    $num_pe->appendChild($tipo_pe);
    $nombre_pe = $xml->createElement('nombre', $pe['nombre']);
    $num_pe->appendChild($nombre_pe);
    $et_plan = $xml->createElement('planes_estudio');
    $consulta_plan = "SELECT * FROM sigi_planes_estudio WHERE id_programa_estudios=".$pe['id'];
    $resultado_plan = $conexion->query($consulta_plan);
    while ($resultado_pla = mysqli_fetch_assoc($resultado_plan)) {
        $plan = $xml->createElement('plan_'.$resultado_pla['id']);
        $nombre_plan = $xml->createElement('nombre', $resultado_pla['nombre']);
        $plan->appendChild($nombre_plan);
        $resolucion_plan = $xml->createElement('resolucion', $resultado_pla['resolucion']);
        $plan->appendChild($resolucion_plan);
        $fecha_registro_plan = $xml->createElement('fecha_registro', $resultado_pla['fecha_registro']);
        $plan->appendChild($fecha_registro_plan);
        
        // ---------------------------Obtener módulos formativos para este plan---------------------------
        $consulta_modulo = "SELECT * FROM sigi_modulo_formativo WHERE id_plan_estudio=".$resultado_pla['id'];
        $resultado_modulo = $conexion->query($consulta_modulo);
        $et_modulos = $xml->createElement('modulos_formativos');
        
        while ($modulo = mysqli_fetch_assoc($resultado_modulo)) {
            $modulo_element = $xml->createElement('modulo_'.$modulo['id']);
            $descripcion_modulo = $xml->createElement('descripcion', $modulo['descripcion']);
            $modulo_element->appendChild($descripcion_modulo);
            $nro_modulo = $xml->createElement('nro_modulo', $modulo['nro_modulo']);
            $modulo_element->appendChild($nro_modulo);
            
            // ---------------------------Obtener periodos para este módulo---------------------------
            $consulta_periodo = "SELECT * FROM sigi_semestre WHERE id_modulo_formativo=".$modulo['id'];
            $resultado_periodo = $conexion->query($consulta_periodo);
            $et_periodos = $xml->createElement('periodos');
            
            while ($periodo = mysqli_fetch_assoc($resultado_periodo)) {
                $periodo_element = $xml->createElement('periodo_'.$periodo['id']);
                $descripcion_periodo = $xml->createElement('descripcion', $periodo['descripcion']);
                $periodo_element->appendChild($descripcion_periodo);
                
                // ---------------------------Obtener unidades didácticas para este periodo---------------------------
                $consulta_unidad = "SELECT * FROM sigi_unidad_didactica WHERE id_semestre=".$periodo['id'];
                $resultado_unidad = $conexion->query($consulta_unidad);
                $et_unidades = $xml->createElement('unidades_didacticas');
                
                while ($unidad = mysqli_fetch_assoc($resultado_unidad)) {
                    $unidad_element = $xml->createElement('unidad_'.$unidad['id']);
                    
                    // ---------------------------Añadir datos básicos de la unidad---------------------------
                    $nombre_unidad = $xml->createElement('nombre', $unidad['nombre']);
                    $unidad_element->appendChild($nombre_unidad);
                    
                    $creditos_teorico = $xml->createElement('creditos_teorico', $unidad['creditos_teorico']);
                    $unidad_element->appendChild($creditos_teorico);
                    
                    $creditos_practico = $xml->createElement('creditos_practico', $unidad['creditos_practico']);
                    $unidad_element->appendChild($creditos_practico);
                    
                    $tipo_unidad = $xml->createElement('tipo', $unidad['tipo']);
                    $unidad_element->appendChild($tipo_unidad);
                    
                    $orden_unidad = $xml->createElement('orden', $unidad['orden']);
                    $unidad_element->appendChild($orden_unidad);
                    
                    // ---------------------------Calcular horas semanales y semestrales---------------------------
                    $horas_teoricas_semanal = $unidad['creditos_teorico'] * 1;
                    $horas_practicas_semanal = $unidad['creditos_practico'] * 2;
                    $horas_totales_semanal = $horas_teoricas_semanal + $horas_practicas_semanal;
                    $horas_semestrales = $horas_totales_semanal * 16;
                    
                    // ---------------------------Crear elemento horas_semanales---------------------------
                    $et_horas_semanales = $xml->createElement('horas_semanales');
                    
                    $horas_teoricas_semanal_elem = $xml->createElement('teoricas', $horas_teoricas_semanal);
                    $et_horas_semanales->appendChild($horas_teoricas_semanal_elem);
                    
                    $horas_practicas_semanal_elem = $xml->createElement('practicas', $horas_practicas_semanal);
                    $et_horas_semanales->appendChild($horas_practicas_semanal_elem);
                    
                    $horas_totales_semanal_elem = $xml->createElement('totales', $horas_totales_semanal);
                    $et_horas_semanales->appendChild($horas_totales_semanal_elem);
                    
                    $unidad_element->appendChild($et_horas_semanales);
                    
                    // ---------------------------Crear elemento horas_semestrales---------------------------
                    $et_horas_semestrales = $xml->createElement('horas_semestrales');
                    
                    $horas_semestrales_elem = $xml->createElement('totales', $horas_semestrales);
                    $et_horas_semestrales->appendChild($horas_semestrales_elem);
                    
                    // ---------------------------Calcular horas teóricas y prácticas semestrales---------------------------
                    $horas_teoricas_semestrales = $horas_teoricas_semanal * 16;
                    $horas_practicas_semestrales = $horas_practicas_semanal * 16;
                    
                    $horas_teoricas_semestrales_elem = $xml->createElement('teoricas', $horas_teoricas_semestrales);
                    $et_horas_semestrales->appendChild($horas_teoricas_semestrales_elem);
                    
                    $horas_practicas_semestrales_elem = $xml->createElement('practicas', $horas_practicas_semestrales);
                    $et_horas_semestrales->appendChild($horas_practicas_semestrales_elem);
                    
                    $unidad_element->appendChild($et_horas_semestrales);
                    
                    $et_unidades->appendChild($unidad_element);
                }
                $periodo_element->appendChild($et_unidades);
                $et_periodos->appendChild($periodo_element);
            }
            $modulo_element->appendChild($et_periodos);
            $et_modulos->appendChild($modulo_element);
        }
        $plan->appendChild($et_modulos);
        $et_plan->appendChild($plan);
    }
    $num_pe->appendChild($et_plan);
    $et1->appendChild($num_pe);
}

$archivo = "sigi.xml";
$xml->save($archivo);
?>