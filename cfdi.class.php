<?php

/*
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @package    sat-cfdi
 * @author     Mario Oyorzabal Salgado <tuxsoul@tuxsoul.com>
 * @copyright  2016
 * @license    https://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU GPL v2
 *
 */

// phpseclib
@require_once ('Math/BigInteger.php');
@require_once ('Crypt/Hash.php');
@require_once ('Crypt/RSA.php');
@require_once ('File/X509.php');

// establece la zona horaria
date_default_timezone_set('America/Mexico_City');

class satCfdi {
	private $xmlns = array(
		'cfdi' => 'http://www.sat.gob.mx/cfd/3',
		'xsi' => array(
			0 => 'http://www.w3.org/2000/xmlns/',
			1 => 'http://www.w3.org/2001/XMLSchema-instance',
		),
		'location' => 'http://www.sat.gob.mx/sitio_internet/cfd/3/cfdv32.xsd',
		'version' => '3.2',
	);

	private $emisor;
	private $receptor;
	private $conceptos;
	private $impuestos;
	private $opciones;

	private $comprobantes;

	private $xml;
	private $dom;

	public $fechaEmision;
	public $numeroCertificado;


	function __construct($comprobantes, $emisor, $receptor, $conceptos, $impuestos, $opciones) {
		$this->emisor = $emisor;
		$this->receptor = $receptor;
		$this->conceptos = $conceptos;
		$this->impuestos = $impuestos;
		$this->opciones = $opciones;

		$this->comprobantes = $comprobantes;

		// inicia estructura del xml
		$this->dom = new DOMDocument('1.0','UTF-8');

		// xmlns:cfdi
		$nodo = $this->dom->createElementNS($this->xmlns['cfdi'], 'cfdi:Comprobante');
		$this->xml = $this->dom->appendChild($nodo);

		// xmlns:xsi
		$this->xml->setAttributeNS($this->xmlns['xsi'][0] ,'xmlns:xsi', $this->xmlns['xsi'][1]);

		// schema:location
		$this->xml->setAttributeNS($this->xmlns['xsi'][1], 'xsi:schemaLocation', $this->xmlns['cfdi'] . ' ' . $this->xmlns['location']);

		// version
		$nodo->setAttribute('version', $this->xmlns['version']);
	}

	// informacion del certificado (certificado en base64, noCertificado)
	private function comprobante() {
		// agrega certificado: el comprobante en formato (.pem) en base64
		$datos = $this->comprobantes;
		$archivo = $datos . $this->emisor['rfc'] . '.cer';

		if(file_exists($archivo)) {
			$certificado = file_get_contents($archivo);
			$pem = base64_encode($certificado);

			$nodo = $this->dom->getElementsByTagName('Comprobante')->item(0);
			$nodo->setAttribute('certificado', $pem);
		}

		// agrega noCertificado
		if(class_exists('File_X509')) {
			$x509 = new File_X509();
			$cert = $x509->loadX509(file_get_contents($archivo));
			$serie = hex2bin($cert['tbsCertificate']['serialNumber']->toHex());

			$nodo->setAttribute('noCertificado', $serie);
			$this->numeroCertificado = $serie;
		}
	}

	// opciones base del cfdi
	private function opciones() {
		$datos = $this->opciones;
		$nodo = $this->dom->getElementsByTagName('Comprobante')->item(0);

		// fecha actual del sistema
		if(isset($datos['fecha'])) {
			$fecha = strtotime($datos['fecha']);
		}
		else {
			$fecha = time();
		}

		// es necesario quitar tiempo, para el campo fechaEmision al parecer los
		// servidores del SAT no son tan precisos, se le restan 2 minutos al tiempo
		$fecha = date("Y-m-d\TH:i:s", $fecha - 160);

		$nodo->setAttribute('fecha', $fecha);
		$this->fechaEmision = $fecha;

		// serie
		if(isset($datos['serie']) && !empty($datos['serie'])) {
			$nodo->setAttribute('serie', $datos['serie']);
		}

		// formaDePago
		if(isset($datos['formaDePago'])) {
			$nodo->setAttribute('formaDePago', $datos['formaDePago']);
		}

		// metodoDePago
		if(isset($datos['metodoDePago'])) {
			$nodo->setAttribute('metodoDePago', $datos['metodoDePago']);
		}

		// LugarExpedicion
		if(isset($this->emisor['localidad']) && isset($this->emisor['estado'])) {
			$nodo->setAttribute('LugarExpedicion',
								$this->emisor['localidad'] . ' ' . $this->emisor['estado']);
		}

		// tipoDeComprobante
		if(isset($datos['tipoDeComprobante'])) {
			$nodo->setAttribute('tipoDeComprobante', strtolower($datos['tipoDeComprobante']));
		}

		// folio
		if(isset($datos['folio']) && !empty($datos['folio'])) {
			$nodo->setAttribute('folio', $datos['folio']);
		}
	}

	// datos de emisor
	private function emisor() {
		$datos = $this->emisor;

		// datos personales del emisor
		$nodo = $this->dom->createElement('cfdi:Emisor');
		$this->xml->appendChild($nodo);

		// nombre
		if(isset($datos['nombre'])) {
			$nodo->setAttribute('nombre', $datos['nombre']);
		}

		// rfc
		if(isset($datos['rfc'])) {
			$nodo->setAttribute('rfc', $datos['rfc']);
		}

		// regimen
		$elemento = $this->dom->createElement('cfdi:RegimenFiscal');
		$nodo->appendChild($elemento);

		if(isset($datos['regimen'])) {
			$elemento->setAttribute('Regimen', $datos['regimen']);
		}
	}

	// datos de receptor
	private function receptor() {
		$datos = $this->receptor;

		// datos personales del receptor
		$nodo = $this->dom->createElement('cfdi:Receptor');
		$this->xml->appendChild($nodo);

		// nombre
		if(isset($datos['nombre'])) {
			$nodo->setAttribute('nombre', $datos['nombre']);
		}

		// rfc
		if(isset($datos['rfc'])) {
			$nodo->setAttribute('rfc', $datos['rfc']);
		}

		// domicilio
		$elemento = $this->dom->createElement('cfdi:Domicilio');
		$nodo->appendChild($elemento);

		// calle
		if(isset($datos['calle'])) {
			$elemento->setAttribute('calle', $datos['calle']);
		}

		// noInterior
		if(isset($datos['noInterior'])) {
			$elemento->setAttribute('noInterior', $datos['noInterior']);
		}

		// noExterior
		if(isset($datos['noExterior'])) {
			$elemento->setAttribute('noExterior', $datos['noExterior']);
		}

		// colonia
		if(isset($datos['colonia'])) {
			$elemento->setAttribute('colonia', $datos['colonia']);
		}

		// municipio
		if(isset($datos['municipio'])) {
			$elemento->setAttribute('municipio', $datos['municipio']);
		}

		// localidad
		if(isset($datos['localidad'])) {
			$elemento->setAttribute('localidad', $datos['localidad']);
		}

		// estado
		if(isset($datos['estado'])) {
			$elemento->setAttribute('estado', $datos['estado']);
		}

		// pais
		if(isset($datos['pais'])) {
			$elemento->setAttribute('pais', $datos['pais']);
		}

		// codigoPostal
		if(isset($datos['codigoPostal'])) {
			$elemento->setAttribute('codigoPostal', $datos['codigoPostal']);
		}
	}

	// datos de conceptos
	private function conceptos() {
		$datos = $this->conceptos;

		// datos de los conceptos
		$nodo = $this->dom->createElement('cfdi:Conceptos');
		$this->xml->appendChild($nodo);

		//agrega cada concepto
		foreach($datos as $concepto) {
			$elemento = $this->dom->createElement('cfdi:Concepto');
			$nodo->appendChild($elemento);

			$elemento->setAttribute('descripcion', $concepto['descripcion']);
			$elemento->setAttribute('unidad', $concepto['unidad']);
			$elemento->setAttribute('cantidad', str_replace(',', '', $concepto['cantidad']));
			$elemento->setAttribute('valorUnitario', str_replace(',', '', $concepto['valorUnitario']));
			$elemento->setAttribute('importe', str_replace(',', '', $concepto['importe']));

			unset($elemento);
		}
	}

	// datos de impuestos
	private function impuestos() {
		$datos = $this->impuestos;

		// se agregan subtotal y total en el nodo Comprobante
		$nodo = $this->dom->getElementsByTagName('Comprobante')->item(0);
		$nodo->setAttribute('subTotal', str_replace(',', '', $datos['subTotal']));
		$nodo->setAttribute('total', str_replace(',', '', $datos['total']));

		// datos de impuestos
		$nodo = $this->dom->createElement('cfdi:Impuestos');
		$this->xml->appendChild($nodo);

		$nodo->setAttribute('totalImpuestosTrasladados', '0.00');

		$trasladados = $this->dom->createElement('cfdi:Traslados');
		$nodo->appendChild($trasladados);

		$impuesto = $this->dom->createElement('cfdi:Traslado');
		$trasladados->appendChild($impuesto);

		$impuesto->setAttribute('impuesto', 'IVA');
		$impuesto->setAttribute('importe', '0.00');
		$impuesto->setAttribute('tasa', '0.00');
	}

	// crea el sello de la facturacion
	public function sello() {
		$datos = $this->comprobantes;
		$archivo = $datos . $this->emisor['rfc'] . '.key.pem';

		if(class_exists('Crypt_RSA')) {
			if(file_exists($archivo)) {
				$cadenaOriginal = $this->cadenaOriginal();

				$rsa = new Crypt_RSA();
				//$rsa->setPassword('12345678a');
				$comprobanteKey = file_get_contents($archivo);
				$rsa->loadKey($comprobanteKey);

				$rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
				$sello = $rsa->sign($cadenaOriginal);

				$nodo = $this->dom->getElementsByTagName('Comprobante')->item(0);
				$nodo->setAttribute('sello', base64_encode($sello));
			}
		}
	}

	// regresa la cadena original
	public function cadenaOriginal() {
		$xml = $this->dom->saveXML();

		$factura = new DOMDocument();
		$factura->loadXML($xml);

		$xslt = new DOMDocument();
		$xslt->load(dirname(__FILE__) . '/xslt/cadenaoriginal_3_3.xslt');
		//$xslt->documentURI = dirname(__FILE__) . '/xslt/';

		$xsltProcesador = new XSLTProcessor;
		$xsltProcesador->importStyleSheet($xslt);

		$cadenaOriginal = $xsltProcesador->transformToXML($factura);
		return $cadenaOriginal;
	}

	// introduce los datos en el xml
	public function crear() {
		$this->opciones();

		$this->emisor();
		$this->receptor();
		$this->conceptos();
		$this->impuestos();

		$this->comprobante();

		$this->sello();
	}

	// regresa el xml para ser mostrado
	public function xml() {
		return $this->dom->saveXML();
	}
}

?>
