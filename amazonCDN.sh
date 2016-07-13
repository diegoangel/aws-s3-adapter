#!/bin/bash

# script para interactuar con Amazon Web Services
# @author Diego <diego.rivarola@gointegro.com>
# @version 0.1

# CONFIGURACION GENERAL
# nombre del cliente de la consola para conectarse a Amazon S3 aws (amazon web service)
S3CMD="s3cmd"
# aplicacion para mostrar dialogos GTK+ desde la consola
ZENITY="zenity"
# setea en "on" el uso de zenity
ZENITY_MODE="on"

# CONFIGURACION AWS
# bucket en Amazon; direccion de Amazon S3 donde se almacenaran
BUCKET="s3://gointegro-devel/"
# acces key id
ACCESS_KEY_ID="AKIAJEVAE3ZP7IO6PXOA"
# secret access key
SECRET_ACCESS_KEY="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"

# CONFIGURACION DE PATHS PLATAFORMA Y APLICACIONES
# path por defecto donde se iran a buscar los archivos en local
BASE_DIR="../proyectoComunidad/public"

BASE_DIRS[]=""
BASE_DIRS[]=""
BASE_DIRS[]=""
BASE_DIRS[]=""

# chequeamos que este instalado s3cdm
if [ "i" != $(dpkg -l | grep $S3CMD | gawk '{print $1}' | head -n 1 | cut -c2) ]; then
	echo "instalando cliente de conección a Amazon S3 ..."
	echo ""
	echo "ingrese su contraseña de usuario cuando le sea solicitada..."
	sleep 2
	sudo apt-get install -y s3cmd
fi

# chequeamos que este instalado zenity
if [ "i" != $(dpkg -l | grep $ZENITY | gawk '{print $1}' | head -n 1 | cut -c2) ]; then
	$ZENITY_MODE="off"
fi
#######################################################################
# funcion para sincronizar archivos desde local hacia Amazon S3
#######################################################################
sincronizarArchivos() {
	if [[ $ZENITY_MODE = "on" ]]; then
		DIR=$(zenity --file-selection --title="Seleccione la carpeta a sincronizar" --filename="$BASE_DIR" --directory  2> /dev/null)
		echo $DIR
	else
		read -p "ingrese el directorio a sincronizar" DIR
	fi
	
	echo "confirma sincronizar el directorio $DIR ? [n/y]"
	read -n1 CONFIRM
	
	if [[ $CONFIRM == "Y" || $CONFIRM == "y" || $CONFIRM = "" ]]; then
		echo "sincronizando archivos desde local hacia Amazon S3" 
		
		s3cmd sync -v --recursive --delete-removed $DIR $BUCKET | tee /tmp/s3cmd_sync.log
		
		echo ""
		echo "se genero un archivo de log en /tmp/s3cmd_sync.log"
	else
		echo ""
		echo "cancelando la sincronizacion..."				
	fi

	return $?
}
#######################################################################
# listar los archivos almacenados en Amazon S3
#######################################################################
listarArchivos() {
	for FILE in $(s3cmd sync -ls -r $BUCKET); do
		echo ${FILE}\n
	done
	
	return $?
}
# simular sincronizacion de archivos desde local hacia A,azon S3
simularSincronizarArchivos() {
	
	if [[ $ZENITY_MODE = "on" ]]; then
		DIR=$(zenity --file-selection --title="Seleccione la carpeta a sincronizar" --filename="$BASE_DIR" --directory  2> /dev/null)
		echo $DIR
	else
		read -p "ingrese el directorio a sincronizar" DIR
	fi
	
	echo "confirma sincronizar el directorio $DIR ? [n/y]"
	read -n1 CONFIRM
	
	if [[ $CONFIRM == "Y" || $CONFIRM == "y" || $CONFIRM = "" ]]; then
		echo "sincronizando archivos desde local hacia Amazon S3" 
		
		s3cmd sync -v --dry-run --recursive --delete-removed $DIR $BUCKET | tee /tmp/s3cmd_sync.log
		
		echo ""
		echo "se genero un archivo de log en /tmp/s3cmd_sync.log"
	else
		echo ""
		echo "cancelando la sincronizacion..."				
	fi	
	
	return $?
}
#######################################################################
# buscar un archivo en Amazon S3
#######################################################################
buscarArchivo() {
	if [[ $ZENITY_MODE = "on" ]]; then
		FILE=$(zenity  --title "Buscar archivo" --entry --text "Ingrese el nombre del archivo que desea buscar")
	else
		read -p "Ingrese el nombre del archivo que desea buscar" FILE
	fi
	
	if [ -z FILE ]; then
	
		for FILE in $(s3cmd sync -ls -r $BUCKET); do
			echo ${FILE}\n
		done
	fi
	
	$?
}
#######################################################################
# funcion principal del script
#######################################################################
main() {
	# menu para seleccionar la tarea a ajecutar contra
	echo ""
	echo 'a) Conectarse a Amazon S3' 
	echo 'b) Listar archivos'
	echo 'c) Sincronizar archivos'
	echo 'd) Simular Sincronizar archivos'
	echo 'e) Buscar archivo'
	echo ""
	echo 'Seleccione: '

	read CHOICE

	case $CHOICE in
		a|A) listarArchivos;;
		b|B) sincronizarArchivos;;
		c|C) simularSincronizarArchivos;;
		d|D) listarArchivos;;		
		e|E) buscarArchivos;;
		*) echo "No eligió ninguna opción valida";; 
	esac
	
	return $?
}

# verificamos si se le pasa algun argumento al script
if [ $@ ]; then
	while getopts "h" FLAG; do
		case $FLAG in
			h) echo "There is no help, Es gibt keine Hilfe, tu vieja en tanga"
			   exit 0
			   ;;
			*) echo "opcion incorrecta! tu router no es compatible con mi configuracion lan/ethernet token ring..."
			   exit 0
			   ;;
		esac
	done
fi

# ejecutamos el script
main

# end













# s3cmd sync -r -F --exclude '.htaccess'  --exclude '.svn*' --exclude 'redirect/*' --exclude '*.php' --exclude 'tmp/*' --exclude 'report'  ../test/VAMO/app_galerias  s3://gointegro-devel | tee ~/s3cmd_sync.log

#TRUE=$(echo $DIR | grep  "contenidos"); echo $TRUE

