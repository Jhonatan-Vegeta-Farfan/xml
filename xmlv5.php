<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "xml_db";

// Crear conexión
$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Crear base de datos si no existe
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "Base de datos creada con exito<br>";
} else {
    echo "Error al crear la base de datos: " . $conn->error . "<br>";
}

// Seleccionar la base de datos
$conn->select_db($dbname);

// Crear tabla programas_estudios
$sql = "CREATE TABLE IF NOT EXISTS programas_estudios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    tipo VARCHAR(100) NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    UNIQUE(codigo)
)";

if ($conn->query($sql) === TRUE) {
    echo "La tabla programas_estudios creada con exito<br>";
} else {
    echo "Error al crear tabla: " . $conn->error . "<br>";
}

// Crear tabla planes_estudio
$sql = "CREATE TABLE IF NOT EXISTS planes_estudio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    programa_estudio_id INT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    resolucion VARCHAR(100),
    fecha_registro DATE,
    FOREIGN KEY (programa_estudio_id) REFERENCES programas_estudios(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "La tabla planes_estudio creada con exito<br>";
} else {
    echo "Error al crear la tabla: " . $conn->error . "<br>";
}

// Crear tabla modulos_formativos
$sql = "CREATE TABLE IF NOT EXISTS modulos_formativos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_estudio_id INT NOT NULL,
    nro_modulo INT NOT NULL,
    descripcion TEXT,
    FOREIGN KEY (plan_estudio_id) REFERENCES planes_estudio(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "La tabla modulos_formativos creada con exito<br>";
} else {
    echo "Error error al tabla: " . $conn->error . "<br>";
}

// Crear tabla periodos
$sql = "CREATE TABLE IF NOT EXISTS periodos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modulo_formativo_id INT NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    FOREIGN KEY (modulo_formativo_id) REFERENCES modulos_formativos(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "La tabla periodos creada creada con exito<br>";
} else {
    echo "Error al crear la tabla: " . $conn->error . "<br>";
}

// Crear tabla unidades_didacticas
$sql = "CREATE TABLE IF NOT EXISTS unidades_didacticas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    periodo_id INT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    creditos_teorico DECIMAL(5,2),
    creditos_practico DECIMAL(5,2),
    tipo VARCHAR(50),
    horas_semanal INT,
    horas_semestral INT,
    FOREIGN KEY (periodo_id) REFERENCES periodos(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "La tabla unidades_didacticas creada con exito<br><br>";
} else {
    echo "Error al crear la tabla: " . $conn->error . "<br>";
}

// Cargar el archivo XML
$xml = simplexml_load_file('ies_db.xml') or die('Error: no se cargó el XML. Escribe correctamente el nombre del archivo XML');

// Recorrer los programas de estudio
foreach($xml as $i_pe => $pe){
    echo 'Codigo: '.$pe->codigo.'<br>';
    echo 'Tipo: '.$pe->tipo.'<br>';
    echo 'Nombre: '.$pe->nombre.'<br>';
    
    // Insertar programa de estudio
    $consulta = "INSERT INTO programas_estudios (codigo, tipo, nombre) 
                 VALUES ('" . $conn->real_escape_string((string)$pe->codigo) . "', 
                         '" . $conn->real_escape_string((string)$pe->tipo) . "', 
                         '" . $conn->real_escape_string((string)$pe->nombre) . "')
                 ON DUPLICATE KEY UPDATE 
                 tipo = VALUES(tipo), 
                 nombre = VALUES(nombre)";
    
    if ($conn->query($consulta) === TRUE) {
        $programa_id = $conn->insert_id;
        if ($programa_id == 0) {
            // Si es un duplicado, obtener el ID existente
            $result = $conn->query("SELECT id FROM programas_estudios WHERE codigo = '" . $conn->real_escape_string((string)$pe->codigo) . "'");
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $programa_id = $row['id'];
            }
        }
    } else {
        echo "Error al inserta el programa: " . $conn->error . "<br>";
        continue;
    }
    
    // Recorrer planes de estudio
    foreach($pe->planes_estudio[0] as $i_ple => $plan){
        echo '--'.$plan->nombre.'<br>';
        echo '--'.$plan->resolucion.'<br>';
        echo '--'.$plan->fecha_registro.'<br>';
        
        // Insertar plan de estudio
        $consulta = "INSERT INTO planes_estudio (programa_estudio_id, nombre, resolucion, fecha_registro) 
                     VALUES ($programa_id, 
                             '" . $conn->real_escape_string((string)$plan->nombre) . "', 
                             '" . $conn->real_escape_string((string)$plan->resolucion) . "', 
                             '" . $conn->real_escape_string((string)$plan->fecha_registro) . "')";
        
        if ($conn->query($consulta) === TRUE) {
            $plan_id = $conn->insert_id;
        } else {
            echo "Error al insertar el plan: " . $conn->error . "<br>";
            continue;
        }
        
        // Recorrer módulos formativos
        foreach($plan->modulos_formativos[0] as $id_mod => $modulo) {
            echo '---'.$modulo->nro_modulo.'<br>';
            echo '---'.$modulo->descripcion.'<br>';
            
            // Insertar módulo formativo
            $consulta = "INSERT INTO modulos_formativos (plan_estudio_id, nro_modulo, descripcion) 
                         VALUES ($plan_id, 
                                 " . (int)$modulo->nro_modulo . ", 
                                 '" . $conn->real_escape_string((string)$modulo->descripcion) . "')";
            
            if ($conn->query($consulta) === TRUE) {
                $modulo_id = $conn->insert_id;
            } else {
                echo "Error al insertar el módulo: " . $conn->error . "<br>";
                continue;
            }
            
            // Recorrer periodos
            foreach($modulo->periodos[0] as $id_per => $periodo){
                echo '----'.$periodo->descripcion.'<br>';
                
                // Insertar periodo
                $consulta = "INSERT INTO periodos (modulo_formativo_id, descripcion) 
                             VALUES ($modulo_id, 
                                     '" . $conn->real_escape_string((string)$periodo->descripcion) . "')";
                
                if ($conn->query($consulta) === TRUE) {
                    $periodo_id = $conn->insert_id;
                } else {
                    echo "Error al insertar el periodo: " . $conn->error . "<br>";
                    continue;
                }
                
                // Recorrer unidades didácticas
                foreach($periodo->unidades_didacticas[0] as $id_ud => $ud){
                    echo '-----'.$ud->nombre.'<br>';
                    echo '-----'.$ud->creditos_teorico.'<br>';
                    echo '-----'.$ud->creditos_practico.'<br>';
                    echo '-----'.$ud->tipo.'<br>';
                    echo '-----'.$ud->horas_semanal.'<br>';
                    echo '-----'.$ud->horas_semestral.'<br>';
                    
                    // Insertar unidad didáctica
                    $consulta = "INSERT INTO unidades_didacticas (periodo_id, nombre, creditos_teorico, creditos_practico, tipo, horas_semanal, horas_semestral) 
                                 VALUES ($periodo_id, 
                                         '" . $conn->real_escape_string((string)$ud->nombre) . "', 
                                         " . (float)$ud->creditos_teorico . ", 
                                         " . (float)$ud->creditos_practico . ", 
                                         '" . $conn->real_escape_string((string)$ud->tipo) . "', 
                                         " . (int)$ud->horas_semanal . ", 
                                         " . (int)$ud->horas_semestral . ")";
                    
                    if (!$conn->query($consulta)) {
                        echo "Error al insertar la unidad didáctica: " . $conn->error . "<br>";
                    }
                }
            }
        }
    }
    echo "<hr>";
}

?>