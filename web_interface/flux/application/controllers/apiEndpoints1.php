<?php
// ##############################################################################
// Flux SBC - Unindo pessoas e negócios
//
// Copyright (C) 2022 Flux Telecom
// Daniel Paixao <daniel@flux.net.br>
// Flux SBC Version 4.0 and above
// License https://www.gnu.org/licenses/agpl-3.0.html
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program. If not, see <http://www.gnu.org/licenses/>.
// ##############################################################################

class ApiEndpoints extends CI_Controller {
	function __construct() {
		parent::__construct ();
		$this->load->model ( "db_model" );
		$this->load->library ( "flux/common" );
		$this->load->model("common_model");
		$this->load->library("flux_log");
		$this->load->library('flux/permission');
		

		
	}
	function index() {
		/*$url = "https://api.apilayer.com/exchangerates_data/latest?base=".Common_model::$global_config ['system_config'] ['base_currency'];
		$currencyData = $this->curl_response ( $url );
		$currencyData = json_decode($currencyData);
		if($currencyData->success != 1){
			$this->session->set_flashdata ( "flux_notification","Currency exchange rates not updated successfully for this reason ".$currencyData->error->type." please check from fixer side." );
			redirect ( base_url () . "systems/currency_list/" );
		}
		$base_currency = $currencyData->base;
		$last_updated = date("Y-m-d H:i:s",$currencyData->timestamp);
		$currency_rates = (array)$currencyData->rates;
		foreach ($currency_rates as $key => $value) {
			$value = number_format((float)$value, 3, '.', '');
			$sql = "UPDATE currency SET currencyrate = '".$value."',last_updated = '".$last_updated."' WHERE currency = '" . $key . "'\n";
			$this->db->query ( $sql );
		}
		$updatebasecurrency = "UPDATE currency SET currencyrate = '1.000',last_updated = '" .$last_updated."' WHERE currency = '" . Common_model::$global_config ['system_config'] ['base_currency'] . "'";
		$this->db->query ( $updatebasecurrency );
		$this->session->set_flashdata ( "flux_errormsg", "Currency exchange rates successfully updated." );
		redirect ( base_url () . "systems/currency_list/" );*/
	}
	function curl_response($url) {
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, array(
		    "Content-Type: text/plain",
		    "apikey: GMCGgGk7JpMVofqRLemJ8CBKxDsXzR25"
		  ) );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_ENCODING, "" );
		curl_setopt ( $ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.2) Gecko/20100115 Firefox/3.6 (.NET CLR 3.5.30729)" );
		$data = curl_exec ( $ch );
		curl_close ( $ch );
		return $data;
	}
	function curl_request() {
	
	$api_url = "https://echo.paw.cloud";

// Captura os dados da requisição original
$post_data = file_get_contents("php://input");

// Inicializa o cURL
$ch = curl_init($api_url);

// Configurações do cURL para fazer a requisição POST
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

// Encaminha os headers originais
$headers = [];
foreach (getallheaders() as $key => $value) {
    $headers[] = "$key: $value";
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Executa a requisição
$response = curl_exec($ch);

// Captura possíveis erros
if (curl_errno($ch)) {
    echo 'Erro no cURL: ' . curl_error($ch);
}

// Fecha a conexão cURL
curl_close($ch);

// Retorna a resposta da API de destino para o cliente original
//echo $response;
$this->flux_log->write_log('flux_endpoint', json_encode($response));
}
	function PrintLogger($Error_flag,$Message)
    {
		if (!$Error_flag) {
			if (is_array($Message)) {
				foreach ($Message as $MessageKey => $MessageValue) {
					if (is_array($MessageValue)) {
						foreach ($MessageValue as $LogKey => $LogValue) {
							fwrite($this->fp, "::::: " . $LogKey . " ::::: " . $LogValue . " :::::\n");
						}
					} else {
						fwrite($this->fp, "::::: " . $MessageKey . " ::::: " . $MessageValue . " :::::\n");
					}
				}
			} else {
					fwrite($this->fp, "::::: " . $Message . " :::::\n");
			}
		}
    }
}

?>
