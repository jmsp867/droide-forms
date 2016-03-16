<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_droideforms
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * modDroideformsHelper contrala as validacoes e os ajax e recursos externos
 */
class modDroideformsHelper
{
	private static $pass_cript_decript = 'droideFomrs@@_645A';
	public static $errors = array();

	/**
	 * Pega o ajax
	 */
	public static function getAjax()
	{

		$input = JFactory::getApplication()->input;
		$id_extension  = $input->get('id_ext',0,'STRING');
		$post = $input->post->get('droideform',0,'ARRAY');
		$params = self::getModule($id_extension);


		if(self::validateField($params,$post)){

				return  self::_sendEmail($params, $post);
		}else{

			$error = array(
				'error'=>1,
				'msn'=>self::$errors
				);

			return  json_encode($error);
		} 

	}
	/**
	 * Pega o modulo
	 * @param int $id id do modulo
	 */
	private function getModule($id){
		$id = self::Decrypt($id);
		jimport('joomla.application.module.helper');
		$module = JModuleHelper::getModule('droideforms');
		$params = new JRegistry();
		if(is_array($module)){
			foreach ($module as $k => $mod) {
				if($mod->id == $id){
						$params->loadString($mod->params);
				}
			}
		}else{
			$params->loadString($module->params);
		}

		return $params;

	}
	/**
	 * Validar os elementos do field
	 * @param array $data lista de validacões
	 * @return bolean true false
	 */
	private function validateField($data,$post){
		$validFiltros = json_decode($data->get('filtros'),true);

		$return = true;
		$tratamento = array();
		$org_Errors = array();
		//organizo os erros listados no adm e organizando em uma lista com o indice do field name
		foreach ($validFiltros['field_name'] as $k => $fild_name) {
			$org_Errors[$fild_name] = array(
					'tipo'=>$validFiltros['tipo'][$k],
					'condition'=>$validFiltros['field_condition'][$k],
					'msn'=>$validFiltros['text_validador'][$k]
				);
		}
		// verico se nos campos de validação existe post, se sim aplico a validação
		foreach ($post as $index => $attr) {
			if(isset($org_Errors[$attr['name']])){
				self::__validate($attr,$org_Errors[$attr['name']]);
			}
		}

		//verifico se existe erros na validação
		
		if(count(self::$errors)){
			$return = false;
		}
		

		return $return;

	}
    
    //aplico as validacoes de acordo com o tipo
	private function __validate($attr_post, $validate){

		if($validate['tipo'] == 'f_required' ){
			self::_required($attr_post['value'], $validate['msn']);
		}

		if($validate['tipo'] == 'f_email' ){
			self::__checkEmail($attr_post['value'], $validate['msn']);
		}

		if($validate['tipo'] == 'f_integer'){
			self::_interger($attr_post['value'], $validate['msn']);
		}

		if($validate['tipo'] == 'f_file'){
			self::__file($attr_post['value'], $validate);
		}

		if($validate['tipo'] == 'f_size'){
			self::__size($attr_post['value'], $validate);	
		}

	}

	private function _interger($valor, $msn){
		$filter_options = array( 
		    'options' => array( 'min_range' => 0) 
		);
		$validatedValue = filter_var($valor, FILTER_VALIDATE_INT, $filter_options);

		if($validatedValue === FALSE){
			self::$errors[] = $msn;
		}
	}

	/**
	 * Verico se o campo é requirido
	 * @param  [type] $valor [description]
	 * @param  [type] $msn   [description]
	 * @return [type]        [description]
	 */
	private function _required($valor, $msn){
		if(empty($valor)){
			self::$errors[] = $msn;
		}
	}

	/**
	 * Verifico se o email é valido
	 * @param  [type] $valor [description]
	 * @param  [type] $msn   [description]
	 * @return [type]        [description]
	 */
	private function __checkEmail($valor, $msn){
		if(empty($valor) ||  !filter_var($valor, FILTER_VALIDATE_EMAIL)){
			self::$errors[] = $msn;
		}
	}

	/**
	 * Verifco se a extensao do file é valido
	 * @param  [type] $valor [description]
	 * @param  [type] $attr  [description]
	 * @return [type]        [description]
	 */
	private function __file($valor, $attr){
		$ext = JFile::getExt($valor);
		$condition = explode(';',$attr['condition']);
		if(!in_array($ext, $condition)){
			self::$errors[] = $attr['msn'];
		}
	}

	/**
	 * Verifico se o tamanho do file é valido 
	 * @param  [type] $valor [description]
	 * @param  [type] $attr  [description]
	 * @return [type]        [description]
	 */
	private function __size($valor, $attr){
		$size = $attr['condition'] / 1024;
		if($valor['size'] > $size){
			self::$errors[] = $attr['msn'];	
		}
	}




	/**
	 * Envio de e-mail 
	 * @param  object $module Objeto com o conteúdo do módulo
	 * @param  array $post   post do formulário
	 * @return string         mensagem de sucesso ou de erro
	 */
	private function _sendEmail($module, $post){
			$config = JFactory::getConfig();
			$return = '';
			$frommail = "";
			$fromname = "";
			$layout = $module->get('layout_envio');

			foreach ($post as $k => $field) {

				if($field['name'] = $module->get('nome_de_cliente')){
					$fromname = $field['value'];
				}

				if($field['name'] = $module->get('email_de_cliente')){
					$fromname = $field['value'];
				}

				if(strrpos($layout, '{'.$field['name'].'}')){
					$layout = str_replace('{'.$field['name'].'}', $field, $layout);
				}
			}

			$sender = array(
			   $frommail,
			   $fromname
			);

			$emailTO = $config->get( 'mailfrom' );
			$mailmodulepara = $module->get('para');
			if(!empty($mailmodulepara)){
				$emailTO = $module->get('para');
			}

			$mail = JFactory::getMailer();
			$mail->isHTML(true);
			$mail->Encoding = 'base64';
			$mail->addRecipient($emailTO);
			$mail->setSender($sender);
			$mail->setSubject($module->get('assunto'));
			$mail->setBody($layout);
			if($mail->Send()){
				$sucesso = array(
				'error'=>0,
				'msn'=>$mod->get('resp_sucesso',JText::_('MOD_DROIDEFORMS_RESP_SUCESSO_DEFAULT'))
				);
				$return = json_encode($sucesso);
			}else{

				self::$errors[] = JText::_('MOD_DROIDEFORMS_RESP_ERROR_DEFAULT');

				$error = array(
					'error'=>1,
					'msn'=>self::$errors
				);

				$return = json_encode($error);
			}

			return $return;
	}


/**
 * Encrypt text
 * @param int $data id para criptar
 * @return string criptada
 */
public function Encrypt($data)
{
	$password = self::$pass_cript_decript;
    $salt = substr(md5(mt_rand(), true), 8);

    $key = md5($password . $salt, true);
    $iv  = md5($key . $password . $salt, true);

    $ct = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv);

    return base64_encode('Salted__' . $salt . $ct);
}

/**
 * Encrypt text
 * @param int $data id para decriptar
 * @return string decriptada
 */
private function Decrypt($data)
{
		$password = self::$pass_cript_decript;

    $data = base64_decode($data);
    $salt = substr($data, 8, 8);
    $ct   = substr($data, 16);

    $key = md5($password . $salt, true);
    $iv  = md5($key . $password . $salt, true);

    $pt = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $ct, MCRYPT_MODE_CBC, $iv);

    return $pt;
}



}