<?php

$conexion = new mysqli("localhost", "root", "root");

$conexion->query("CREATE DATABASE IF NOT EXISTS iesp2_bd");

$conexion = new mysqli("localhost", "root", "root", "iesp2_bd");

$conexion->query("CREATE TABLE IF NOT EXISTS sigi_programa_estudios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    nombre VARCHAR(100) NOT NULL
)");


$conexion->query("CREATE TABLE IF NOT EXISTS sigi_planes_estudio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_programa INT NOT NULL,
    nombre VARCHAR(20) NOT NULL,
    resolucion VARCHAR(100) NOT NULL,
    fecha_registro DATETIME NOT NULL,
    perfil_egresado TEXT NOT NULL
)");


$conexion->query("CREATE TABLE IF NOT EXISTS sigi_modulo_formativo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descripcion TEXT NOT NULL,
    nro_modulo INT NOT NULL,
    id_plan INT NOT NULL
)");


$conexion->query("CREATE TABLE IF NOT EXISTS sigi_semestre (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(5) NOT NULL,
    id_modulo INT NOT NULL
)");


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




$xml = simplexml_load_file('ies_db.xml') or die('Error no se cargo el xml');

foreach ($xml as $i_pe => $pe) {
    echo 'codigo: ' . $pe->codigo . "<br>";
    echo 'tipo: ' . $pe->tipo . "<br>";
    echo 'nombre: ' . $pe->nombre . "<br>";
    
    
    $consulta = "INSERT INTO sigi_programa_estudios (codigo, tipo, nombre) 
                 VALUES ('$pe->codigo', '$pe->tipo', '$pe->nombre')";
    $conexion->query($consulta);
    $id_programa = $conexion->insert_id;
    
   
    foreach ($pe->planes_estudio[0] as $i_ple => $plan) {
        echo '--' . $plan->nombre . "<br>";
        echo '--' . $plan->resolucion . "<br>";
        echo '--' . $plan->fecha_registro . "<br>";
        
       
       $consulta = "INSERT INTO sigi_planes_estudio (id_programa, nombre, resolucion, fecha_registro, perfil_egresado) 
             VALUES ($id_programa, '$plan->nombre', '$plan->resolucion', '$plan->fecha_registro', '$plan->perfil_egresado')";
        $conexion->query($consulta);
        $id_plan = $conexion->insert_id;
        
       
        foreach ($plan->modulos_formativos[0] as $id_mod => $modulo) {
            echo '----' . $modulo->nro_modulo . "<br>";
            echo '----' . $modulo->descripcion . "<br>";
            
            
            $consulta = "INSERT INTO sigi_modulo_formativo (descripcion, nro_modulo, id_plan ) 
                         VALUES ('$modulo->descripcion', $modulo->nro_modulo, $id_plan )";
            $conexion->query($consulta);
            $id_modulo = $conexion->insert_id;
            
           
            foreach ($modulo->periodos[0] as $i_per => $periodo) {
                echo '------' . $periodo->descripcion . "<br>";
                
                
                $consulta = "INSERT INTO sigi_semestre (id_modulo, descripcion) 
                             VALUES ($id_modulo, '$periodo->descripcion')";
                $conexion->query($consulta);
                $id_semestre = $conexion->insert_id;
                
                
                $orden = 1;
                foreach ($periodo->unidades_didacticas[0] as $id_ud => $ud) {
                    echo '--------' . $ud->nombre . "<br>";
                    echo '--------' . $ud->creditos_teorico . "<br>";
                    echo '--------' . $ud->creditos_practico . "<br>";
                    echo '--------' . $ud->tipo . "<br>";
                    echo '--------' . $ud->horas_semanal . "<br>";
                    echo '--------' . $ud->horas_semestral . "<br>";
                    
                   
                    $consulta = "INSERT INTO sigi_unidad_didactica (id_semestre, nombre, creditos_teorico, creditos_practico, tipo, horas_semanal, horas_semestral, orden) 
                                 VALUES ($id_semestre, '$ud->nombre', $ud->creditos_teorico, $ud->creditos_practico, '$ud->tipo', $ud->horas_semanal, $ud->horas_semestral, $orden)";
                    $conexion->query($consulta);
                    
                    $orden++;
                }
            }
        }
    }
    echo "<hr>";
}


?>