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

class Auth_model extends CI_Model {
	function __construct() {
		parent::__construct ();
	}
	function verify_login($username, $password) {
		$q = "SELECT COUNT(*) as cnt FROM accounts WHERE (number = BINARY '" . $this->db->escape_str ( $username ) . "'";
		$q .= " OR email = BINARY '" . $this->db->escape_str ( $username ) . "')";
		$q .= " AND password = '" . $this->db->escape_str ( $password ) . "'";
		$q .= " AND type IN (1,2,3,4,5,6,7,8,0,-1) AND deleted = 0 AND status = 0";
		$query = $this->db->query ( $q );
		if ($query->num_rows () > 0) {
			$row = $query->row ();
			if ($row->cnt > 0) {
				$this->session->set_userdata ( 'user_name', $username );
				return 1;
			} else {
				return 0;
			}
		}
		
		return 0;
	}
	function validar_usuario($usuario, $senha) {
	        $this->db->select('*');
	        $this->db->from('usuarios');
	        $this->db->where('usuario', $usuario);
	        $this->db->where('senha', md5($senha));  // ou bcrypt, dependendo de como você está armazenando a senha
	        $this->db->where('status', 'ativo');
	        $query = $this->db->get();
	        
	        if ($query->num_rows() == 1) {
	            return $query->row();
	        } else {
	            return false;
	        }
	    }
}

?>
