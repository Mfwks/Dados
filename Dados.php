<?php

namespace Mfwks\Legacy;

use PDO;

/**
 * 
 * [Helper]
 * 
 * Dados
 * 
 * Solução genérica para projetos legados.
 *
 * https://github.com/Mfwks/Legacy
 *
 * Microframeworks <eskelsen@microframeworks.com>
 *
 * Este código está sob a MIT License.
 * 
 */

class Dados
{
	private PDO $conex;
	
	public function __construct(PDO $conex, $options = [])
	{
		$this->conex = $conex;
		$this->config($options);
	}
	
	protected function config($options)
	{
		# Configurações adicionais do projeto: implementar nas classes estendidas
	}
	
	public function tabela($t)
	{
		$ok = $this->consultar("SHOW TABLES LIKE '$t';");
		if (!is_array($ok)) {
			return false;
		}
		$stmt = $this->executar("DESCRIBE $t");
		$data = $stmt ? $stmt->fetchAll() : false;
		return $data ? array_column($data,'Field') : false;
	}

	public function cabecalhos($t)
	{
		if ($f = $this->tabela($t)) {
			return implode(',',$f);
		}
		return false;
	}

	public function rotulos($t,$key,$value,$cond = null,$v = false)
	{
		if (!$cols = $this->todos($t,"$key,$value",$cond,$v)) {
			return null;
		}
		foreach ($cols as $row) {
			$n[$row[$key]] = $row[$value];
		}
		return $n ?? null;
	}
	
	public function inserir($t,$vs)
	{
		[$f,$v] = $this->preparar($vs);
		$h      = implode(',',array_fill(0,count($v),'?'));
		$stmt   = $this->executar("INSERT INTO $t ($f) VALUES ($h);",$v);
		return $stmt ? $this->conex->lastInsertId() : false;
	}

	private function preparar($in)
	{
		return [
			0 => implode(',',array_keys($in)),
			1 => array_values($in)
		];
	}
	
	public function campo($t,$f,$cond = null,$v = false)
	{
		$stmt = $this->executar("SELECT $f FROM $t $cond;",$v);
		return $stmt ? $stmt->fetchColumn() : false;
	}

	public function linha($t,$f = '*',$cond = null,$v = false)
	{
		$stmt = $this->executar("SELECT $f FROM $t $cond;",$v);
		return $stmt ? $stmt->fetch() : false;
	}

	public function coluna($t,$f,$cond = null,$v = false)
	{
		$stmt = $this->executar("SELECT $f FROM $t $cond;",$v);
		$data = $stmt ? $stmt->fetchAll() : false;
		return $data ? array_column($data,$f) : false;
	}

	public function todos($t,$f = '*',$cond = null,$v = false)
	{
		$stmt = $this->executar("SELECT $f FROM $t $cond;",$v);
		return $stmt ? $stmt->fetchAll() : false;
	}

	public function quantos($t,$f = '*',$cond = null,$v = false)
	{
		return $this->contar($t,$f,'COUNT',$cond,$v);
	}

	public function somar($t,$f = '*',$cond = null,$v = false)
	{
		return $this->contar($t,$f,'SUM',$cond,$v);
	}

	private function contar($t,$f,$op,$cond = null,$v = false)
	{
		$field = "$op($f)";
		$stmt = $this->executar("SELECT $field FROM $t $cond;",$v);
		$n = $stmt ? $stmt->fetch() : false;
		return (isset($n[$field])) ? $n[$field] : false;
	}

	public function atualizar($t,$a,$c,$cvs = [])
	{
		[$f,$fvs] = $this->parametrizar($a);
		$vs = array_merge($fvs,$cvs);
		$stmt = $this->executar("UPDATE $t SET $f WHERE $c;",$vs);
		return $stmt ? $stmt->rowCount() : false;
	}
	
	private function parametrizar($array)
	{
		foreach ($array as $field => $value) {
			$sets[] = "$field=?";
			$values[] = trim($value,' \'');
		}
		return [implode(',',$sets),$values];
	}

	public function consultar($q, $tudo = true)
	{
		if ($stmt = $this->executar($q)) {
			if ($affected = $stmt->rowCount()) {
				return $all ? $stmt->fetchAll() : $stmt->fetch();
			}
			return true;
		}
		return false;
	}
	
	public function excluir($t,$c,$v = [])
	{
		$stmt = $this->executar("DELETE FROM $t WHERE $c;",$v);
		return $stmt ? $stmt->rowCount() : false;
	}
	
	private function executar($sql,$v = false)
	{
		$stmt = $this->conex->prepare($sql);
		try {
			$made = $v ? $stmt->execute($v) : $stmt->execute();
			return $made ? $stmt : false;
		} catch (PDOException $e) {
			exit('Falha na requisição ao banco de dados: ' . $e->getMessage());
		}
	}
}
