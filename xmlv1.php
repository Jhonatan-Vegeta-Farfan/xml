<?php
//--------------------PROGRAMAS DE ESTUDIO-------------
$ies = [];

//--------------------UNIDADES DIDÁCTICAS POR PERIODO-------------
$udp1 = [
    'ANÁLISIS Y DISEÑO DE SISTEMAS', 
    'FUNDAMENTOS DE PROGRAMACIÓN', 
    'REDES E INTERNET', 
    'INTRODUCCIÓN A BASE DE DATOS', 
    'ARQUITECTURA DE COMPUTADORAS', 
    'COMUNICACIÓN ORAL', 
    'APLICACIONES EN INTERNET'
];

$udp2 = [
    'OFIMÁTICA', 
    'INTERPRETACIÓN Y PRODUCCIÓN DE TEXTOS', 
    'METODOLOGÍA DE DESARROLLO DE SOFTWARE', 
    'PROGRAMACIÓN ORIENTADA A OBJETOS', 
    'APLICACIONES SISTEMATIZADAS', 
    'TALLER DE BASE DE DATOS'
];

$udp3 = [
    'ADMINISTRACIÓN DE BASE DE DATOS', 
    'PROGRAMACIÓN DE APLICACIONES WEB', 
    'DISEÑO DE INTERFACES WEB', 
    'PRUEBAS DE SOFTWARE', 
    'INGLÉS PARA LA COMUNICACIÓN ORAL'
];

$udp4 = [
    'DESARROLLO DE ENTORNOS WEB', 
    'PROGRAMACIÓN DE SOLUCIONES WEB', 
    'PROYECTOS DE SOFTWARE', 
    'SEGURIDAD EN APLICACIONES WEB', 
    'COMPRENSIÓN Y REDACCIÓN EN INGLÉS', 
    'COMPORTAMIENTO ÉTICO'
];

$udp5 = [
    'PROGRAMACIÓN DE APLICACIONES MÓVILES', 
    'MARKETING DIGITAL', 
    'DISEÑO DE SOLUCIONES WEB', 
    'GESTIÓN Y ADMINISTRACIÓN DE SITIOS WEB', 
    'DIAGRAMÁTICA DIGITAL', 
    'SOLUCIÓN DE PROBLEMAS', 
    'OPORTUNIDADES DE NEGOCIOS'
];

$udp6 = [
    'PLATAFORMA DE SERVICIOS WEB', 
    'ILUSTRACIÓN Y GRÁFICA DIGITAL', 
    'ADMINISTRACIÓN DE SERVIDORES WEB', 
    'COMERCIO ELECTRÓNICO', 
    'PLAN DE NEGOCIOS'
];

//--------------------PERIODOS-------------
$p1 = [];
$p1['nombre'] = "I";
$p1['unidades_didacticas'] = $udp1;

$p2 = [];
$p2['nombre'] = "II";
$p2['unidades_didacticas'] = $udp2;

$p3 = [];
$p3['nombre'] = "III";
$p3['unidades_didacticas'] = $udp3;

$p4 = [];
$p4['nombre'] = "IV";
$p4['unidades_didacticas'] = $udp4;

$p5 = [];
$p5['nombre'] = "V";
$p5['unidades_didacticas'] = $udp5;

$p6 = [];
$p6['nombre'] = "VI";
$p6['unidades_didacticas'] = $udp6;

//--------------------MÓDULOS-------------
$m1 = [];
$m1['nombre'] = "ANÁLISIS Y DISEÑO DE SISTEMAS";
$m1['periodos'] = [$p1, $p2];

$m2 = [];
$m2['nombre'] = "DESARROLLO DE APLICACIONES WEB";
$m2['periodos'] = [$p3, $p4];

$m3 = [];
$m3['nombre'] = "DISEÑO DE SERVICIOS WEB";
$m3['periodos'] = [$p5, $p6];

// ---------------PROGRAMAS DE ESTUDIO---------

$p1 = array();
$p1['nombre'] = "DISEÑO Y PROGRAMACIÓN WEB";
$p1['modulos'] = [$m1, $m2, $m3];

$p2 = array();
$p2['nombre'] = "ENFERMERIA TECNICA";
$p2['modulos'] = [$m1, $m2, $m3];

$p3 = array();
$p3['nombre'] = "INDUSTRIAS DE ALIMENTOS Y BEBIDAS";
$p3['modulos'] = [$m1, $m2, $m3];

$p4 = array();
$p4['nombre'] = "MECATRONICA AUTOMOTRIZ";
$p4['modulos'] = [$m1, $m2, $m3];

$p5 = array();
$p5['nombre'] = "PRODUCCION AGROPECUARIA";
$p5['modulos'] = [$m1, $m2, $m3];




$ies['nombre'] = "IES Publico HUANTA";
$ies['programas_estudio'] = [$p1, $p2, $p3, $p4, $p5];

$xml = new DOMDocument('1.0', 'UTF-8');
$xml->formatOutput = true;

$et1 = $xml->createElement('ies');
$xml->appendChild($et1);

$nombre_ies = $xml->createElement("nombre", $ies['nombre']);
$programas_ies = $xml->createElement("programas_estudio");

foreach ($ies["programas_estudio"] as $indice => $PEs) {
    $num_pe = $xml->createElement("pe".$indice+1);
    $nombre_pe = $xml->createElement("nombre", $PEs['nombre']);
    $num_pe->appendChild($nombre_pe);
    $programas_ies->appendChild($num_pe);
    

}


$archivo = "ies.xml";
$xml->save($archivo);

?>