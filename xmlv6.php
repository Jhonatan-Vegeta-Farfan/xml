<?php
// 1. Conectar al servidor MySQL
$conexion = new mysqli("localhost", "root", "root");

// Verificar si la base de datos ya existe
$resultado = $conexion->query("SHOW DATABASES LIKE 'xml2_bd'");
if ($resultado->num_rows == 0) {
    // Crear base de datos solo si no existe
    $conexion->query("CREATE DATABASE xml2_bd");
    echo "Base de datos creada exitosamente<br>";
} else {
    echo "La base de datos ya existe<br>";
}

// Conectar a la base de datos
$conexion = new mysqli("localhost", "root", "root", "xml2_bd");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// 2. Crear tabla de programas (si no existe)
$conexion->query("CREATE TABLE IF NOT EXISTS sigi_programa_estudios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    nombre VARCHAR(100) NOT NULL
)");

// 3. Crear tabla de planes (si no existe)
$conexion->query("CREATE TABLE IF NOT EXISTS sigi_planes_estudio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_programa INT NOT NULL,
    nombre VARCHAR(20) NOT NULL,
    resolucion VARCHAR(100) NOT NULL,
    fecha_registro DATETIME NOT NULL,
    perfil_egresado TEXT NOT NULL
)");

// 4. Crear tabla de módulos (si no existe)
$conexion->query("CREATE TABLE IF NOT EXISTS sigi_modulo_formativo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descripcion TEXT NOT NULL,
    nro_modulo INT NOT NULL,
    id_plan INT NOT NULL
)");

// 5. Crear tabla de semestres (si no existe)
$conexion->query("CREATE TABLE IF NOT EXISTS sigi_semestre (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(5) NOT NULL,
    id_modulo INT NOT NULL
)");

// 6. Crear tabla de unidades (si no existe)
$conexion->query("CREATE TABLE IF NOT EXISTS sigi_unidad_didactica (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    id_semestre INT NOT NULL,
    creditos_teorico INT NOT NULL,
    creditos_practico INT NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    horas_semanal INT,
    horas_semestral INT,
    orden INT NOT NULL
)");

echo "Tablas verificadas/creadas exitosamente<br>";

// Cargar XML
$xml = simplexml_load_file('ies_db.xml') or die('Error no se cargo el xml');

// Opcional: Limpiar tablas existentes si quieres datos frescos cada vez
// $conexion->query("DELETE FROM sigi_unidad_didactica");
// $conexion->query("DELETE FROM sigi_semestre");
// $conexion->query("DELETE FROM sigi_modulo_formativo");
// $conexion->query("DELETE FROM sigi_planes_estudio");
// $conexion->query("DELETE FROM sigi_programa_estudios");

foreach ($xml as $i_pe => $pe) {
    echo 'codigo: ' . $pe->codigo . "<br>";
    echo 'tipo: ' . $pe->tipo . "<br>";
    echo 'nombre: ' . $pe->nombre . "<br>";
    
    // Insertar programa en BD
    $consulta = "INSERT INTO sigi_programa_estudios (codigo, tipo, nombre) 
                 VALUES ('" . $conexion->real_escape_string($pe->codigo) . "', 
                         '" . $conexion->real_escape_string($pe->tipo) . "', 
                         '" . $conexion->real_escape_string($pe->nombre) . "')";
    $conexion->query($consulta);
    $id_programa = $conexion->insert_id;
    
    // Recorrer planes
    foreach ($pe->planes_estudio[0] as $i_ple => $plan) {
        echo '--' . $plan->nombre . "<br>";
        echo '--' . $plan->resolucion . "<br>";
        echo '--' . $plan->fecha_registro . "<br>";
        
        // Insertar plan en BD
        $consulta = "INSERT INTO sigi_planes_estudio (id_programa, nombre, resolucion, fecha_registro, perfil_egresado) 
             VALUES ($id_programa, 
                     '" . $conexion->real_escape_string($plan->nombre) . "', 
                     '" . $conexion->real_escape_string($plan->resolucion) . "', 
                     '" . $conexion->real_escape_string($plan->fecha_registro) . "', 
                     '" . $conexion->real_escape_string($plan->perfil_egresado) . "')";
        $conexion->query($consulta);
        $id_plan = $conexion->insert_id;
        
        // Recorrer módulos
        foreach ($plan->modulos_formativos[0] as $id_mod => $modulo) {
            echo '----' . $modulo->nro_modulo . "<br>";
            echo '----' . $modulo->descripcion . "<br>";
            
            // Insertar módulo en BD
            $consulta = "INSERT INTO sigi_modulo_formativo (descripcion, nro_modulo, id_plan) 
                         VALUES ('" . $conexion->real_escape_string($modulo->descripcion) . "', 
                                 " . intval($modulo->nro_modulo) . ", 
                                 $id_plan)";
            $conexion->query($consulta);
            $id_modulo = $conexion->insert_id;
            
            // Recorrer semestres
            foreach ($modulo->periodos[0] as $i_per => $periodo) {
                echo '------' . $periodo->descripcion . "<br>";
                
                // Insertar semestre en BD
                $consulta = "INSERT INTO sigi_semestre (id_modulo, descripcion) 
                             VALUES ($id_modulo, 
                                     '" . $conexion->real_escape_string($periodo->descripcion) . "')";
                $conexion->query($consulta);
                $id_semestre = $conexion->insert_id;
                
                // Recorrer unidades
                $orden = 1;
                foreach ($periodo->unidades_didacticas[0] as $id_ud => $ud) {
                    echo '--------' . $ud->nombre . "<br>";
                    echo '--------' . $ud->creditos_teorico . "<br>";
                    echo '--------' . $ud->creditos_practico . "<br>";
                    echo '--------' . $ud->tipo . "<br>";
                    echo '--------' . $ud->horas_semanal . "<br>";
                    echo '--------' . $ud->horas_semestral . "<br>";
                    
                    // Insertar unidad en BD
                    $consulta = "INSERT INTO sigi_unidad_didactica (id_semestre, nombre, creditos_teorico, creditos_practico, tipo, horas_semanal, horas_semestral, orden) 
                                 VALUES ($id_semestre, 
                                         '" . $conexion->real_escape_string($ud->nombre) . "', 
                                         " . intval($ud->creditos_teorico) . ", 
                                         " . intval($ud->creditos_practico) . ", 
                                         '" . $conexion->real_escape_string($ud->tipo) . "', 
                                         " . intval($ud->horas_semanal) . ", 
                                         " . intval($ud->horas_semestral) . ", 
                                         $orden)";
                    $conexion->query($consulta);
                    
                    $orden++;
                }
            }
        }
    }
    echo "<hr>";
}

echo "¡Base de datos verificada y datos insertados correctamente!";
?>