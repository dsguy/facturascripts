<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_model('agente.php');
require_model('albaran_cliente.php');
require_model('cliente.php');
require_model('cuenta_banco_cliente.php');
require_model('direccion_cliente.php');
require_model('divisa.php');
require_model('forma_pago.php');
require_model('grupo_clientes.php');
require_model('pais.php');
require_model('serie.php');

class general_cliente extends fs_controller
{
   public $agente;
   public $buscar_lineas;
   public $cliente;
   public $cuenta_banco;
   public $divisa;
   public $forma_pago;
   public $grupo;
   public $listado;
   public $listar;
   public $pais;
   public $offset;
   public $serie;
   
   public function __construct()
   {
      parent::__construct('general_cliente', 'Cliente', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('general_clientes');
      $this->agente = new agente();
      $this->cuenta_banco = new cuenta_banco_cliente();
      $this->divisa = new divisa();
      $this->forma_pago = new forma_pago();
      $this->grupo = new grupo_clientes();
      $this->pais = new pais();
      $this->serie = new serie();
      
      
      /// cargamos el cliente
      $cliente = new cliente();
      $this->cliente = FALSE;
      if( isset($_POST['codcliente']) )
         $this->cliente = $cliente->get( $_POST['codcliente'] );
      else if( isset($_GET['cod']) )
         $this->cliente = $cliente->get($_GET['cod']);
      
      
      /// ¿Hay que hacer algo más?
      if( isset($_POST['buscar_lineas']) )
      {
         $this->buscar_lineas();
      }
      else if( isset($_GET['delete_cuenta']) )
      {
         $cuenta = $this->cuenta_banco->get($_GET['delete_cuenta']);
         if($cuenta)
         {
            if( $cuenta->delete() )
               $this->new_message('Cuenta bancaria eliminada correctamente.');
            else
               $this->new_error_msg('Imposible eliminar la cuenta bancaria.');
         }
         else
            $this->new_error_msg('Cuenta bancaria no encontrada.');
      }
      else if( isset($_GET['delete_dir']) )
      {
         $dir = new direccion_cliente();
         $dir0 = $dir->get($_GET['delete_dir']);
         if($dir0)
         {
            if( $dir0->delete() )
               $this->new_message('Dirección eliminada correctamente.');
            else
               $this->new_error_msg('Imposible eliminar la dirección.');
         }
         else
            $this->new_error_msg('Dirección no encontrada.');
      }
      else if( isset($_POST['coddir']) )
      {
         $dir = new direccion_cliente();
         if($_POST['coddir'] != '')
            $dir = $dir->get($_POST['coddir']);
         $dir->apartado = $_POST['apartado'];
         $dir->ciudad = $_POST['ciudad'];
         $dir->codcliente = $this->cliente->codcliente;
         $dir->codpais = $_POST['pais'];
         $dir->codpostal = $_POST['codpostal'];
         $dir->descripcion = $_POST['descripcion'];
         $dir->direccion = $_POST['direccion'];
         $dir->domenvio = isset($_POST['direnvio']);
         $dir->domfacturacion = isset($_POST['dirfact']);
         $dir->provincia = $_POST['provincia'];
         if( $dir->save() )
            $this->new_message("Dirección guardada correctamente.");
         else
            $this->new_message("¡Imposible guardar la dirección!");
      }
      else if( isset($_POST['iban']) )
      {
         if( isset($_POST['codcuenta']) )
         {
            $cuentab = $this->cuenta_banco->get($_POST['codcuenta']);
         }
         else
         {
            $cuentab = new cuenta_banco_cliente();
            $cuentab->codcliente = $_POST['codcliente'];
         }
         $cuentab->descripcion = $_POST['descripcion'];
         $cuentab->iban = $_POST['iban'];
         if( $cuentab->save() )
            $this->new_message('Cuenta bancaria guardada correctamente.');
         else
            $this->new_error_msg('Imposible guardar la cuenta bancaria.');
      }
      else if( isset($_POST['codcliente']) )
      {
         $this->cliente->nombre = $_POST['nombre'];
         $this->cliente->nombrecomercial = $_POST['nombrecomercial'];
         $this->cliente->cifnif = $_POST['cifnif'];
         $this->cliente->telefono1 = $_POST['telefono1'];
         $this->cliente->telefono2 = $_POST['telefono2'];
         $this->cliente->fax = $_POST['fax'];
         $this->cliente->web = $_POST['web'];
         $this->cliente->email = $_POST['email'];
         $this->cliente->observaciones = $_POST['observaciones'];
         $this->cliente->codserie = $_POST['codserie'];
         $this->cliente->codpago = $_POST['codpago'];
         $this->cliente->coddivisa = $_POST['coddivisa'];
         $this->cliente->regimeniva = $_POST['regimeniva'];
         $this->cliente->dtopor = floatval($_POST['dtopor']);
         $this->cliente->zona = $_POST['zona'];
         $this->cliente->ruta = $_POST['ruta'];
         $this->cliente->tipo_portes = $_POST['tipo_portes'];
         $this->cliente->agencia_transporte = $_POST['agenciat'];
         $this->cliente->dias_pago = $_POST['dias_pago'];
         $this->cliente->i343 = isset($_POST['i343']);
         
         if($_POST['codagente'] == '---')
            $this->cliente->codagente = NULL;
         else
            $this->cliente->codagente = $_POST['codagente'];
         
         if($_POST['codgrupo'] == '---')
            $this->cliente->codgrupo = NULL;
         else
            $this->cliente->codgrupo = $_POST['codgrupo'];
         
         if( $this->cliente->save() )
            $this->new_message("Datos del cliente modificados correctamente.");
         else
            $this->new_error_msg("¡Imposible modificar los datos del cliente!");
      }
      
      if($this->cliente)
      {
         $this->page->title = $this->cliente->codcliente;
         $this->buttons[] = new fs_button_img('b_eliminar', 'eliminar', 'trash.png', '#', TRUE);
         
         $this->offset = 0;
         if( isset($_GET['offset']) )
            $this->offset = intval($_GET['offset']);
         
         $this->listar = 'albaranes';
         if( isset($_GET['listar']) )
         {
            if( in_array($_GET['listar'], array('albaranes','facturas','stats')) )
               $this->listar = $_GET['listar'];
         }
         
         if($this->listar == 'facturas')
            $this->listado = $this->cliente->get_facturas($this->offset);
         else
            $this->listado = $this->cliente->get_albaranes($this->offset);
      }
      else if( !isset($_POST['buscar_lineas']) )
         $this->new_error_msg("¡Cliente no encontrado!");
   }
   
   public function url()
   {
      if( !isset($this->cliente) )
         return parent::url();
      else if($this->cliente)
         return $this->cliente->url();
      else
         return $this->ppage->url();
   }
   
   public function anterior_url()
   {
      if($this->offset > '0')
         return $this->url()."&listar=".$this->listar."&offset=".($this->offset-FS_ITEM_LIMIT);
      else
         return '';
   }
   
   public function siguiente_url()
   {
      if(count($this->listado) == FS_ITEM_LIMIT)
         return $this->url()."&listar=".$this->listar."&offset=".($this->offset+FS_ITEM_LIMIT);
      else
         return '';
   }
   
   public function buscar_lineas()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/general_lineas_albaranes_cli';
      
      $this->buscar_lineas = $_POST['buscar_lineas']; /// necesario para el html
      $linea = new linea_albaran_cliente();
      $this->lineas = $linea->search_from_cliente($_POST['codcliente'], $this->buscar_lineas);
   }
   
   public function stats_last_months()
   {
      $albaran = new albaran_cliente();
      return $albaran->stats_from_cli($this->cliente->codcliente);
   }
   
   public function this_year($previous = 0)
   {
      return intval(Date('Y')) - $previous;
   }
}

?>