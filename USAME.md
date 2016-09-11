# USAME

A continuacion se muestra en ejemplos, el uso de sat-cfdi.

El proyecto esta escrito en php, al momento de escribir este archivo, se ha
probado con php5.

Primero es necesario crear una instancia de sat-cfdi.

```php
include ruta_clase/cfdi.class.php;

$factura = new satCfdi();
$resultado = $factura->crear();
```

Para el funcionamiento de sat-cfdi, es necesario pasar algunos valores con una
estructura, un ejemplo de estas estructuras son las siguientes.

Tanto el emisor como el receptor, tienen la siguiente estructura:

```php
$emisor = array(
	'nombre'=>'PUBLICO GENERAL',
	'rfc'=>'XAXX010101000',
	'calle'=>'ALGUNA CALLE',
	'noInterior'=>'35',
	'noExterior'=>'W',
	'colonia'=>'ALGUNA COLONIA',
	'municipio'=>'Cuernavaca',
	'localidad'=>'Cuernavaca',
	'estado'=>'Morelos',
	'pais'=>'México',
	'codigoPostal'=>'00000',
	'regimen'=>'Régimen General de Ley de Personas Morales',
);
```

Otro conjunto de variables que son necesarios:

```php
$opciones = array(
	'fecha' => '2016-08-10T19:16:17',
	'serie' => 'A',
	'folio' => '100',
	'formaDePago' => 'Pago en una sola exhibición.',
	'metodoDePago' => '01',
	'tipoDeComprobante' => 'ingreso',
);
```
