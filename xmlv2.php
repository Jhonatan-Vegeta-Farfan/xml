<?php
// Configuración de la conexión a la base de datos
$host = 'localhost';
$dbname = 'sigi_huanta';
$username = 'root'; // Cambiar según tu configuración
$password = 'root'; // Cambiar según tu configuración

try {
    // Crear conexión PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Crear documento XML
    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = true;
    
    // Elemento raíz
    $root = $xml->createElement('plan_estudios_detallado');
    $root = $xml->appendChild($root);
    
    // Agregar atributos al elemento raíz
    $root->setAttribute('fecha_generacion', date('Y-m-d H:i:s'));
    $root->setAttribute('version', '2.0');
    $root->setAttribute('base_datos', $dbname);
    
    // 1. CONSULTAR TODOS LOS DATOS
    echo "Consultando datos de la base de datos...<br>";
    
    // Consultar programas de estudios
    $queryProgramas = "SELECT * FROM sigi_programa_estudios ORDER BY id";
    $stmtProgramas = $pdo->query($queryProgramas);
    $programas = $stmtProgramas->fetchAll(PDO::FETCH_ASSOC);
    
    // Consultar planes de estudio (excluyendo perfil_egresado)
    $queryPlanes = "SELECT id, id_programa_estudios, nombre, resolucion, fecha_registro FROM sigi_planes_estudio ORDER BY id";
    $stmtPlanes = $pdo->query($queryPlanes);
    $planes = $stmtPlanes->fetchAll(PDO::FETCH_ASSOC);
    
    // Consultar módulos formativos
    $queryModulos = "SELECT * FROM sigi_modulo_formativo ORDER BY id_plan_estudio, nro_modulo";
    $stmtModulos = $pdo->query($queryModulos);
    $modulos = $stmtModulos->fetchAll(PDO::FETCH_ASSOC);
    
    // Consultar semestres
    $querySemestres = "SELECT * FROM sigi_semestre ORDER BY id_modulo_formativo, descripcion";
    $stmtSemestres = $pdo->query($querySemestres);
    $semestres = $stmtSemestres->fetchAll(PDO::FETCH_ASSOC);
    
    // Consultar unidades didácticas
    $queryUnidades = "SELECT * FROM sigi_unidad_didactica ORDER BY id_semestre, orden";
    $stmtUnidades = $pdo->query($queryUnidades);
    $unidades = $stmtUnidades->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. GENERAR XML
    echo "Generando estructura XML...<br>";
    
    // SECCIÓN: INFORMACIÓN DEL SISTEMA
    $infoSistema = $xml->createElement('informacion_sistema');
    $root->appendChild($infoSistema);
    
    $infoSistema->appendChild($xml->createElement('titulo', 'Sistema de Gestión de Planes de Estudio - SIGI Huanta'));
    $infoSistema->appendChild($xml->createElement('fecha_exportacion', date('Y-m-d H:i:s')));
    $infoSistema->appendChild($xml->createElement('total_registros_exportados', 
        count($programas) + count($planes) + count($modulos) + count($semestres) + count($unidades)));
    
    // SECCIÓN: PROGRAMS DE ESTUDIOS
    echo "Procesando programas de estudios...<br>";
    
    $seccionProgramas = $xml->createElement('programas_de_estudios');
    $root->appendChild($seccionProgramas);
    
    $seccionProgramas->appendChild($xml->createElement('cantidad_total', count($programas)));
    
    foreach ($programas as $programa) {
        $programaNode = $xml->createElement('programa');
        
        $programaNode->appendChild($xml->createElement('id', $programa['id']));
        $programaNode->appendChild($xml->createElement('codigo', $programa['codigo']));
        $programaNode->appendChild($xml->createElement('tipo', $programa['tipo']));
        $programaNode->appendChild($xml->createElement('nombre_completo', $programa['nombre']));
        
        // Contar planes asociados a este programa
        $planesAsociados = array_filter($planes, function($plan) use ($programa) {
            return $plan['id_programa_estudios'] == $programa['id'];
        });
        
        $programaNode->appendChild($xml->createElement('total_planes', count($planesAsociados)));
        
        $seccionProgramas->appendChild($programaNode);
    }
    
    // SECCIÓN: PLANES DE ESTUDIO
    echo "Procesando planes de estudio...<br>";
    
    $seccionPlanes = $xml->createElement('planes_de_estudio');
    $root->appendChild($seccionPlanes);
    
    $seccionPlanes->appendChild($xml->createElement('cantidad_total', count($planes)));
    
    foreach ($planes as $plan) {
        $planNode = $xml->createElement('plan');
        
        $planNode->appendChild($xml->createElement('id', $plan['id']));
        $planNode->appendChild($xml->createElement('id_programa_estudios', $plan['id_programa_estudios']));
        $planNode->appendChild($xml->createElement('nombre_plan', $plan['nombre']));
        $planNode->appendChild($xml->createElement('resolucion', $plan['resolucion']));
        $planNode->appendChild($xml->createElement('fecha_registro', $plan['fecha_registro']));
        
        // Obtener nombre del programa asociado
        $programaAsociado = array_filter($programas, function($prog) use ($plan) {
            return $prog['id'] == $plan['id_programa_estudios'];
        });
        
        if (!empty($programaAsociado)) {
            $prog = reset($programaAsociado);
            $planNode->appendChild($xml->createElement('programa_codigo', $prog['codigo']));
            $planNode->appendChild($xml->createElement('programa_nombre', $prog['nombre']));
        }
        
        // Obtener módulos de este plan
        $modulosPlan = array_filter($modulos, function($modulo) use ($plan) {
            return $modulo['id_plan_estudio'] == $plan['id'];
        });
        
        $planNode->appendChild($xml->createElement('total_modulos', count($modulosPlan)));
        
        $seccionPlanes->appendChild($planNode);
    }
    
    // SECCIÓN: MÓDULOS FORMATIVOS
    echo "Procesando módulos formativos...<br>";
    
    $seccionModulos = $xml->createElement('modulos_formativos');
    $root->appendChild($seccionModulos);
    
    $seccionModulos->appendChild($xml->createElement('cantidad_total', count($modulos)));
    
    foreach ($modulos as $modulo) {
        $moduloNode = $xml->createElement('modulo');
        
        $moduloNode->appendChild($xml->createElement('id', $modulo['id']));
        $moduloNode->appendChild($xml->createElement('descripcion', $modulo['descripcion']));
        $moduloNode->appendChild($xml->createElement('numero_modulo', $modulo['nro_modulo']));
        $moduloNode->appendChild($xml->createElement('id_plan_estudio', $modulo['id_plan_estudio']));
        
        // Obtener información del plan asociado
        $planAsociado = array_filter($planes, function($plan) use ($modulo) {
            return $plan['id'] == $modulo['id_plan_estudio'];
        });
        
        if (!empty($planAsociado)) {
            $plan = reset($planAsociado);
            $moduloNode->appendChild($xml->createElement('plan_nombre', $plan['nombre']));
        }
        
        // Obtener semestres de este módulo
        $semestresModulo = array_filter($semestres, function($semestre) use ($modulo) {
            return $semestre['id_modulo_formativo'] == $modulo['id'];
        });
        
        $moduloNode->appendChild($xml->createElement('total_semestres', count($semestresModulo)));
        
        $seccionModulos->appendChild($moduloNode);
    }
    
    // SECCIÓN: SEMESTRES
    echo "Procesando semestres...<br>";
    
    $seccionSemestres = $xml->createElement('semestres');
    $root->appendChild($seccionSemestres);
    
    $seccionSemestres->appendChild($xml->createElement('cantidad_total', count($semestres)));
    
    foreach ($semestres as $semestre) {
        $semestreNode = $xml->createElement('semestre');
        
        $semestreNode->appendChild($xml->createElement('id', $semestre['id']));
        $semestreNode->appendChild($xml->createElement('descripcion', $semestre['descripcion']));
        $semestreNode->appendChild($xml->createElement('id_modulo_formativo', $semestre['id_modulo_formativo']));
        
        // Obtener información del módulo asociado
        $moduloAsociado = array_filter($modulos, function($modulo) use ($semestre) {
            return $modulo['id'] == $semestre['id_modulo_formativo'];
        });
        
        if (!empty($moduloAsociado)) {
            $mod = reset($moduloAsociado);
            $semestreNode->appendChild($xml->createElement('modulo_numero', $mod['nro_modulo']));
            $semestreNode->appendChild($xml->createElement('modulo_descripcion', $mod['descripcion']));
        }
        
        // Obtener unidades didácticas de este semestre
        $unidadesSemestre = array_filter($unidades, function($unidad) use ($semestre) {
            return $unidad['id_semestre'] == $semestre['id'];
        });
        
        $semestreNode->appendChild($xml->createElement('total_unidades', count($unidadesSemestre)));
        
        $seccionSemestres->appendChild($semestreNode);
    }
    
    // SECCIÓN: UNIDADES DIDÁCTICAS (DETALLADA CON CÁLCULOS)
    echo "Procesando unidades didácticas...<br>";
    
    $seccionUnidades = $xml->createElement('unidades_didacticas');
    $root->appendChild($seccionUnidades);
    
    $seccionUnidades->appendChild($xml->createElement('cantidad_total', count($unidades)));
    
    $totalHorasSemanales = 0;
    $totalHorasSemestrales = 0;
    $totalCreditos = 0;
    
    foreach ($unidades as $unidad) {
        $unidadNode = $xml->createElement('unidad');
        
        // Información básica
        $unidadNode->appendChild($xml->createElement('id', $unidad['id']));
        $unidadNode->appendChild($xml->createElement('nombre', $unidad['nombre']));
        $unidadNode->appendChild($xml->createElement('id_semestre', $unidad['id_semestre']));
        $unidadNode->appendChild($xml->createElement('creditos_teorico', $unidad['creditos_teorico']));
        $unidadNode->appendChild($xml->createElement('creditos_practico', $unidad['creditos_practico']));
        $unidadNode->appendChild($xml->createElement('tipo', $unidad['tipo']));
        $unidadNode->appendChild($xml->createElement('orden', $unidad['orden']));
        
        // CÁLCULOS DE HORAS (Fórmula: teórico x 1, práctico x 2)
        $horasSemanales = ($unidad['creditos_teorico'] * 1) + ($unidad['creditos_practico'] * 2);
        $horasSemestrales = $horasSemanales * 16; // 16 semanas por semestre
        $totalCreditosUnidad = $unidad['creditos_teorico'] + $unidad['creditos_practico'];
        
        // Agregar cálculos al nodo
        $unidadNode->appendChild($xml->createElement('horas_semanales', $horasSemanales));
        $unidadNode->appendChild($xml->createElement('horas_semestrales', $horasSemestrales));
        $unidadNode->appendChild($xml->createElement('total_creditos', $totalCreditosUnidad));
        
        // Obtener información del semestre asociado
        $semestreAsociado = array_filter($semestres, function($semestre) use ($unidad) {
            return $semestre['id'] == $unidad['id_semestre'];
        });
        
        if (!empty($semestreAsociado)) {
            $sem = reset($semestreAsociado);
            $unidadNode->appendChild($xml->createElement('semestre_descripcion', $sem['descripcion']));
            
            // Obtener información del módulo
            $moduloAsociado = array_filter($modulos, function($modulo) use ($sem) {
                return $modulo['id'] == $sem['id_modulo_formativo'];
            });
            
            if (!empty($moduloAsociado)) {
                $mod = reset($moduloAsociado);
                $unidadNode->appendChild($xml->createElement('modulo_numero', $mod['nro_modulo']));
                $unidadNode->appendChild($xml->createElement('modulo_descripcion', $mod['descripcion']));
                
                // Obtener información del plan
                $planAsociado = array_filter($planes, function($plan) use ($mod) {
                    return $plan['id'] == $mod['id_plan_estudio'];
                });
                
                if (!empty($planAsociado)) {
                    $plan = reset($planAsociado);
                    $unidadNode->appendChild($xml->createElement('plan_nombre', $plan['nombre']));
                    
                    // Obtener información del programa
                    $programaAsociado = array_filter($programas, function($prog) use ($plan) {
                        return $prog['id'] == $plan['id_programa_estudios'];
                    });
                    
                    if (!empty($programaAsociado)) {
                        $prog = reset($programaAsociado);
                        $unidadNode->appendChild($xml->createElement('programa_codigo', $prog['codigo']));
                        $unidadNode->appendChild($xml->createElement('programa_nombre', $prog['nombre']));
                    }
                }
            }
        }
        
        // Sumar a los totales
        $totalHorasSemanales += $horasSemanales;
        $totalHorasSemestrales += $horasSemestrales;
        $totalCreditos += $totalCreditosUnidad;
        
        $seccionUnidades->appendChild($unidadNode);
    }
    
    // SECCIÓN: RESUMEN Y ESTADÍSTICAS
    echo "Generando resumen estadístico...<br>";
    
    $seccionResumen = $xml->createElement('resumen_estadistico');
    $root->appendChild($seccionResumen);
    
    $seccionResumen->appendChild($xml->createElement('total_programas_estudios', count($programas)));
    $seccionResumen->appendChild($xml->createElement('total_planes_estudio', count($planes)));
    $seccionResumen->appendChild($xml->createElement('total_modulos_formativos', count($modulos)));
    $seccionResumen->appendChild($xml->createElement('total_semestres', count($semestres)));
    $seccionResumen->appendChild($xml->createElement('total_unidades_didacticas', count($unidades)));
    
    // Estadísticas de horas y créditos
    $seccionResumen->appendChild($xml->createElement('total_horas_semanales_todas_unidades', $totalHorasSemanales));
    $seccionResumen->appendChild($xml->createElement('total_horas_semestrales_todas_unidades', $totalHorasSemestrales));
    $seccionResumen->appendChild($xml->createElement('total_creditos_todas_unidades', $totalCreditos));
    
    // Promedios
    if (count($unidades) > 0) {
        $seccionResumen->appendChild($xml->createElement('promedio_horas_semanales_por_unidad', round($totalHorasSemanales / count($unidades), 2)));
        $seccionResumen->appendChild($xml->createElement('promedio_horas_semestrales_por_unidad', round($totalHorasSemestrales / count($unidades), 2)));
        $seccionResumen->appendChild($xml->createElement('promedio_creditos_por_unidad', round($totalCreditos / count($unidades), 2)));
    }
    
    // SECCIÓN: RELACIONES COMPLETAS (ESTRUCTURA JERÁRQUICA)
    echo "Generando estructura jerárquica completa...<br>";
    
    $seccionJerarquia = $xml->createElement('estructura_jerarquica_completa');
    $root->appendChild($seccionJerarquia);
    
    // Recorrer programas y construir jerarquía completa
    foreach ($programas as $programa) {
        $programaJerarquia = $xml->createElement('programa');
        $programaJerarquia->setAttribute('id', $programa['id']);
        $programaJerarquia->setAttribute('codigo', $programa['codigo']);
        $programaJerarquia->setAttribute('nombre', $programa['nombre']);
        
        // Obtener planes de este programa
        $planesPrograma = array_filter($planes, function($plan) use ($programa) {
            return $plan['id_programa_estudios'] == $programa['id'];
        });
        
        foreach ($planesPrograma as $plan) {
            $planJerarquia = $xml->createElement('plan');
            $planJerarquia->setAttribute('id', $plan['id']);
            $planJerarquia->setAttribute('nombre', $plan['nombre']);
            $planJerarquia->setAttribute('resolucion', $plan['resolucion']);
            
            // Obtener módulos de este plan
            $modulosPlan = array_filter($modulos, function($modulo) use ($plan) {
                return $modulo['id_plan_estudio'] == $plan['id'];
            });
            
            foreach ($modulosPlan as $modulo) {
                $moduloJerarquia = $xml->createElement('modulo');
                $moduloJerarquia->setAttribute('id', $modulo['id']);
                $moduloJerarquia->setAttribute('numero', $modulo['nro_modulo']);
                $moduloJerarquia->setAttribute('descripcion', $modulo['descripcion']);
                
                // Obtener semestres de este módulo
                $semestresModulo = array_filter($semestres, function($semestre) use ($modulo) {
                    return $semestre['id_modulo_formativo'] == $modulo['id'];
                });
                
                foreach ($semestresModulo as $semestre) {
                    $semestreJerarquia = $xml->createElement('semestre');
                    $semestreJerarquia->setAttribute('id', $semestre['id']);
                    $semestreJerarquia->setAttribute('descripcion', $semestre['descripcion']);
                    
                    // Obtener unidades de este semestre
                    $unidadesSemestre = array_filter($unidades, function($unidad) use ($semestre) {
                        return $unidad['id_semestre'] == $semestre['id'];
                    });
                    
                    foreach ($unidadesSemestre as $unidad) {
                        $unidadJerarquia = $xml->createElement('unidad');
                        $unidadJerarquia->setAttribute('id', $unidad['id']);
                        $unidadJerarquia->setAttribute('nombre', $unidad['nombre']);
                        $unidadJerarquia->setAttribute('tipo', $unidad['tipo']);
                        
                        // Cálculos de horas
                        $horasSemanales = ($unidad['creditos_teorico'] * 1) + ($unidad['creditos_practico'] * 2);
                        $horasSemestrales = $horasSemanales * 16;
                        
                        $unidadJerarquia->setAttribute('creditos_teorico', $unidad['creditos_teorico']);
                        $unidadJerarquia->setAttribute('creditos_practico', $unidad['creditos_practico']);
                        $unidadJerarquia->setAttribute('horas_semanales', $horasSemanales);
                        $unidadJerarquia->setAttribute('horas_semestrales', $horasSemestrales);
                        $unidadJerarquia->setAttribute('total_creditos', $unidad['creditos_teorico'] + $unidad['creditos_practico']);
                        
                        $semestreJerarquia->appendChild($unidadJerarquia);
                    }
                    
                    $moduloJerarquia->appendChild($semestreJerarquia);
                }
                
                $planJerarquia->appendChild($moduloJerarquia);
            }
            
            $programaJerarquia->appendChild($planJerarquia);
        }
        
        $seccionJerarquia->appendChild($programaJerarquia);
    }
    
    // SECCIÓN: METADATOS Y CONFIGURACIÓN
    $seccionMetadatos = $xml->createElement('metadatos');
    $root->appendChild($seccionMetadatos);
    
    $seccionMetadatos->appendChild($xml->createElement('formula_horas_semanales', 'horas_semanales = (creditos_teorico × 1) + (creditos_practico × 2)'));
    $seccionMetadatos->appendChild($xml->createElement('formula_horas_semestrales', 'horas_semestrales = horas_semanales × 16'));
    $seccionMetadatos->appendChild($xml->createElement('semanas_por_semestre', '16'));
    $seccionMetadatos->appendChild($xml->createElement('unidad_medida_creditos', 'Créditos académicos'));
    $seccionMetadatos->appendChild($xml->createElement('unidad_medida_horas', 'Horas pedagógicas'));
    $seccionMetadatos->appendChild($xml->createElement('tipo_programas', 'Modular (todos)'));
    
    // 3. MOSTRAR RESULTADO
    echo "<h2>XML generado exitosamente</h2>";
    echo "<h3>Resumen de datos exportados:</h3>";
    echo "<ul>";
    echo "<li>Programas de estudios: " . count($programas) . "</li>";
    echo "<li>Planes de estudio: " . count($planes) . "</li>";
    echo "<li>Módulos formativos: " . count($modulos) . "</li>";
    echo "<li>Semestres: " . count($semestres) . "</li>";
    echo "<li>Unidades didácticas: " . count($unidades) . "</li>";
    echo "<li>Total horas semanales: " . $totalHorasSemanales . "</li>";
    echo "<li>Total horas semestrales: " . $totalHorasSemestrales . "</li>";
    echo "<li>Total créditos: " . $totalCreditos . "</li>";
    echo "</ul>";
    
    echo "<h3>Vista previa del XML:</h3>";
    echo "<textarea style='width:100%; height:300px; font-family: monospace; font-size: 12px;'>";
    echo htmlspecialchars($xml->saveXML());
    echo "</textarea>";
    
    echo "<h3>Enlace para descargar el XML completo:</h3>";
    echo "<a href='data:application/xml;charset=utf-8," . urlencode($xml->saveXML()) . "' download='planes_estudio_detallado_" . date('Y-m-d') . ".xml'>";
    echo "Descargar archivo XML completo";
    echo "</a>";
    
    // También mostrar directamente el XML si se desea
    echo "<h3>Visualización directa del XML:</h3>";
    echo "<pre style='background:#f5f5f5; padding:10px; border:1px solid #ddd; max-height:500px; overflow:auto;'>";
    echo htmlspecialchars($xml->saveXML());
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<h1 style='color:red;'>Error de conexión a la base de datos</h1>";
    echo "<div style='background:#ffe6e6; padding:15px; border:1px solid #ff6666;'>";
    echo "<p><strong>Mensaje de error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Código de error:</strong> " . $e->getCode() . "</p>";
    echo "<p><strong>Verifica:</strong></p>";
    echo "<ul>";
    echo "<li>Nombre del host: " . $host . "</li>";
    echo "<li>Nombre de la base de datos: " . $dbname . "</li>";
    echo "<li>Usuario: " . $username . "</li>";
    echo "<li>¿La base de datos existe?</li>";
    echo "<li>¿El usuario tiene permisos?</li>";
    echo "</ul>";
    echo "</div>";
}

$archivo = "sigi.xml";
$xml->save($archivo);
?>

