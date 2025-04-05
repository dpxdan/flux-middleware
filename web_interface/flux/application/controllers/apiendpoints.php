<?php
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );
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



require APPPATH . '/libraries/Rest_Controller.php';

class Apiendpoints extends Rest_Controller
{
    function __construct()
    {
        // Construct the parent class
        parent::__construct();
    }


   public function news_get()
   {
     //Web service of type GET method
     $this->response(["Hello World"], Rest_Controller::HTTP_OK);
   }
   
    public function users_get()
    {
        // Users from a data store e.g. database
        $users = [
            ['id' => 0, 'name' => 'John', 'email' => 'john@example.com'],
            ['id' => 1, 'name' => 'Jim', 'email' => 'jim@example.com'],
        ];

        $id = $this->get( 'id' );

        if ( $id === null )
        {
            // Check if the users data store contains users
            if ( $users )
            {
                // Set the response and exit
                $this->response( $users, 200 );
            }
            else
            {
                // Set the response and exit
                $this->response( [
                    'status' => false,
                    'message' => 'No users were found'
                ], 404 );
            }
        }
        else
        {
            if ( array_key_exists( $id, $users ) )
            {
                $this->response( $users[$id], 200 );
            }
            else
            {
                $this->response( [
                    'status' => false,
                    'message' => 'No such user found'
                ], 404 );
            }
        }
    }
   public function news_post()
   {  
     //Web service of type POST method
     $this->response(["Hello World"], Rest_Controller::HTTP_OK);
   }
   public function news_put()
   {  
     //Web service of type PUT method
     $this->response(["Hello World"], Rest_Controller::HTTP_OK);
   }
   public function news_delete()
   {  
     //Web service of type DELETE method
     $this->response(["Hello World"], Rest_Controller::HTTP_OK);
   }
}

?>
