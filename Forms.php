<?php

namespace Mfwks\Legacy;

/**
 * 
 * [Helper]
 * 
 * Forms
 * 
 * Solução genérica para projetos legados.
 *
 * Microframeworks <eskelsen@microframeworks.com>
 *
 * Este código está sob a MIT License.
 * 
 */

class Forms
{
	public static function info()
	{
		exit('Esta classe pertence ao repositório Legacy em https://github.com/Mfwks/Legacy' . PHP_EOL);
	}
	
	/*
	* Controle de formulário
	*/
	
	public static function iniciar()
	{
		$base_ctrl = $_SESSION['form_ctrl'] ?? null;
		$form_ctrl = $_REQUEST['form_ctrl'] ?? null;
		$_SESSION['form_ctrl'] = bin2hex(random_bytes(32));
		$_SESSION['form_true'] = ($form_ctrl AND $base_ctrl) ? $form_ctrl===$base_ctrl : false;
	}
	
	public static function validar()
	{
		return $_SESSION['form_true'] ?? false;
	}

	public static function input()
	{
		$form_ctrl = $_SESSION['form_ctrl'] ?? 'Form: controle de formulário não iniciado';
		return '<input type="hidden" id="form_ctrl" name="form_ctrl" value="' . $form_ctrl . '">' . "\n";
	}
	
	/*
	* Métodos de tratamento
	*/

	public static function validarEmail($email)
	{
		if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return false;
		}
		[$username, $domain] = explode('@', $email);
		return checkdnsrr($domain, 'MX');
	}

	public static function filtrarEmail($in)
	{
		return empty($in) ? false : strtolower(trim($in,' "\',.;:/\\][{}+-#!@$%¨&*()'));
	}

	public static function filtrarHash($in)
	{
		$in = empty($in) ? '' : trim($in);
		return preg_replace("/[^A-Za-z0-9]/",'',$in);
	}

	public static function filtrarNumeros($in)
	{
		$in = empty($in) ? '' : trim($in);
		return preg_replace('/[^0-9]/','',$in);
	}
	
	public static function stringfy($in){
		return strtolower(trim(preg_replace('~[^0-9a-z]+~i', '-', html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($in, ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8')), '-'));
	}

	public static function filtrarUTF8($in){
		$regex = '/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u';
		return trim(preg_replace($regex,'', $in),' ');
	}

	public static function filtrarSimbolos($in){
		$in = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $in); # Emoticons
		$in = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $in); # Symbols & Pictographs
		$in = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $in); # Transport & Map Symbols
		$in = preg_replace('/[\x{1F700}-\x{1F77F}]/u', '', $in); # Alphabetic Presentation Forms
		$in = preg_replace('/[\x{1F780}-\x{1F7FF}]/u', '', $in); # Geometric Shapes Extended
		return trim($in);
	}

	/*
	* Métodos de máscaras
	*/

	public static function telefone($in)
	{
		$in = Forms::filtrarNumeros($in);
		$len = strlen($in);
		if ($len==12) {
			return '+' . substr($in, 0, 2) . ' (' . substr($in, 2, 2) . ') ' . substr($in, 4, 4) . '-' . substr($in, 8, 4);
		} elseif ($len==13) {
			return '+' . substr($in, 0, 2) . ' (' . substr($in, 2, 2) . ') ' . substr($in, 4, 5) . '-' . substr($in, 9, 5);
		}
		return $in;
	}
	
	public static function formatarNome($name) {
		$name = mb_strtolower($name);
		$prepositions = ['de', 'da', 'do', 'dos', 'das', 'e'];
		$words = explode(' ', $name);
		$capitalizedWords = array_map(function($word) use ($prepositions) {
			if (!in_array($word, $prepositions)) {
				return Forms::capitalizar($word);
			} else {
				return mb_strtolower($word);
			}
		}, $words);
		return implode(' ', $capitalizedWords);
	}

	public static function capitalizar($name){
		return mb_strtoupper(mb_substr($name, 0, 1, 'utf-8'), 'utf-8') . mb_strtolower(mb_substr($name, 1, null, 'utf-8'));
	}
}
