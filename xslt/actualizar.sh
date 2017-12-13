#!/bin/sh

# Mario Oyorzabal Salgado <tuxsoul@tuxsoul.com>

WGET="/usr/bin/wget"
GREP="/bin/grep"
SORT="/usr/bin/sort"
UNIQ="/usr/bin/uniq"
SED="/bin/sed"
RM="/bin/rm"

CADENA32="cadenaoriginal_3_2.xslt"
CADENA33="cadenaoriginal_3_3.xslt"
TEMP="temp.txt"

# se eliminan todos los archivos xslt
echo "-- Eliminando archivos xslt anteriores ..."
$RM *.xslt

# descargamos los archivos principales
echo "-- Descargando nuevos archivos principales ..."
$WGET -q http://www.sat.gob.mx/sitio_internet/cfd/3/cadenaoriginal_3_2/$CADENA32
$WGET -q http://www.sat.gob.mx/sitio_internet/cfd/3/cadenaoriginal_3_3/$CADENA33

echo "-- Descargando archivos xslt adicionales ..."
# extraemos url de los archivos include
$GREP -h -i "xsl:include" *.xslt | $GREP -Eo 'http://(.*).xslt' | $SORT | $UNIQ > $TEMP

# descargamos todos los archivos include
$WGET -q -i $TEMP

# eliminamos el archivo temporal
$RM $TEMP

# cambiamos de version 2 a version 1 los archivos xslt
echo "-- Cambiando a version 1.0 de version 2.0 ..."
$SED -i -e 's/<xsl:stylesheet version="2.0"/<xsl:stylesheet version="1.0"/g' *.xslt

# cambiamos includes de archivos principales, a forma local
echo "-- Editando archivos principales para carga local de archivos adicionales ..."
$SED -i -e 's/http:\/\/www.sat.gob.mx\/sitio_internet\/cfd\/\(.*\)\/\(.*\).xslt/\2.xslt/g' $CADENA32
$SED -i -e 's/http:\/\/www.sat.gob.mx\/sitio_internet\/cfd\/\(.*\)\/\(.*\).xslt/\2.xslt/g' $CADENA33

echo "-- Fin ;) ..."
