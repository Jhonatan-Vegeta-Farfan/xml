<?php
// Conexión a la base de datos MySQL usando PDO (PHP Data Objects)
$conexion = new mysqli("localhost", "root", "root", "sigi_huanta");
if ($conexion->connect_error) {
    echo "Error de conexión a MySQL: (" . $conexion->connect_error . ")" . $conexion->connect_error;
}
$xml =new DOMDocument('1.0', 'UTF-8');
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
        $et_plan->appendChild($plan);
        $consulta_modulo = "SELECT * FROM sigi_modulo_formativo WHERE id_plan_estudio=".$resultado_pla['id'];
        $resultado_modulo = $conexion->query($consulta_modulo);
        if ($resultado_modulo->num_rows > 0) {
            $et_modulos = $xml->createElement('modulos_formativos');
            
            while ($modulo = mysqli_fetch_assoc($resultado_modulo)) {
                $modulo_element = $xml->createElement('modulo_'.$modulo['id']);
                $descripcion_modulo = $xml->createElement('descripcion', $modulo['descripcion']);
                $modulo_element->appendChild($descripcion_modulo);
                $nro_modulo = $xml->createElement('nro_modulo', $modulo['nro_modulo']);
                $modulo_element->appendChild($nro_modulo);
                
                // Obtener periodos para este módulo
                $consulta_periodo = "SELECT * FROM sigi_semestre WHERE id_modulo_formativo=".$modulo['id'];
                $resultado_periodo = $conexion->query($consulta_periodo);
                if ($resultado_periodo->num_rows > 0) {
                    $et_periodos = $xml->createElement('periodos');
                    
                    while ($periodo = mysqli_fetch_assoc($resultado_periodo)) {
                        $periodo_element = $xml->createElement('periodo_'.$periodo['id']);
                        $descripcion_periodo = $xml->createElement('descripcion', $periodo['descripcion']);
                        $periodo_element->appendChild($descripcion_periodo);
                        
                        // Obtener unidades didácticas para este periodo
                        $consulta_unidad = "SELECT * FROM sigi_unidad_didactica WHERE id_semestre=".$periodo['id'];
                        $resultado_unidad = $conexion->query($consulta_unidad);
                    }
                    
                }
            }
        }

    }

    $num_pe->appendChild($et_plan);
    $et1->appendChild($num_pe);
}

$archivo = "ies_db.xml";
$xml->save($archivo);