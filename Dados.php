<?php

namespace Mfwks;

use PDO;

/**
 * 
 * [Helper]
 * 
 * Dados
 * 
 * Solução genérica para projetos legados.
 *
 * Microframeworks <eskelsen@microframeworks.com>
 *
 * Este código está sob a MIT License.
 * 
 */

class Dados
{
	private PDO $conex;
	
	public function __construct($conex, $arg = [])
	{
		$this->conex = $conex;
		$this->config($arg);
	}
	
	protected function config($arg)
	{
		# Configurações adicionais do projeto: implementar nas classes estendidas
	}
	
	public function tabela($t)
	{
		$ok = $this->query("SHOW TABLES LIKE '$t';");
		if (!is_array($ok)) {
			return false;
		}
		$stmt = $this->sqlExec("DESCRIBE $t");
		$data = $stmt ? $stmt->fetchAll() : false;
		return $data ? array_column($data,'Field') : false;
	}

	public function cabecalhos($t)
	{
		if ($f = $this->tab($t)) {
			return implode(',',$f);
		}
		return false;
	}

	public function rotulos($t,$key,$value,$cond = null,$v = false)
	{
		if (!$cols = $this->selectAll($t,"$key,$value",$cond,$v)) {
			return null;
		}
		foreach ($cols as $row) {
			$n[$row[$key]] = $row[$value];
		}
		return $n ?? null;
	}
	
	public function inserir($t,$vs)
	{
		[$f,$v] = $this->fieldsValues($vs);
		$h      = implode(',',array_fill(0,count($v),'?'));
		$stmt   = $this->sqlExec("INSERT INTO $t ($f) VALUES ($h);",$v);
		return $stmt ? $this->conex->lastInsertId() : false;
	}

	private function fieldsValues($in)
	{
		return [
			0 => implode(',',array_keys($in)),
			1 => array_values($in)
		];
	}
	
	public function campo($t,$f,$cond = null,$v = false)
	{
		$stmt = $this->sqlExec("SELECT $f FROM $t $cond;",$v);
		return $stmt ? $stmt->fetchColumn() : false;
	}

	public function linha($t,$f = '*',$cond = null,$v = false)
	{
		$stmt = $this->sqlExec("SELECT $f FROM $t $cond;",$v);
		return $stmt ? $stmt->fetch() : false;
	}

	public function coluna($t,$f,$cond = null,$v = false)
	{
		$stmt = $this->sqlExec("SELECT $f FROM $t $cond;",$v);
		$data = $stmt ? $stmt->fetchAll() : false;
		return $data ? array_column($data,$f) : false;
	}

	public function todos($t,$f = '*',$cond = null,$v = false)
	{
		$stmt = $this->sqlExec("SELECT $f FROM $t $cond;",$v);
		return $stmt ? $stmt->fetchAll() : false;
	}

	public function quantos($t,$f = '*',$cond = null,$v = false)
	{
		return $this->selectThing($t,$f,'COUNT',$cond,$v);
	}

	public function somar($t,$f = '*',$cond = null,$v = false)
	{
		return $this->selectThing($t,$f,'SUM',$cond,$v);
	}

	private function selectThing($t,$f,$op,$cond = null,$v = false)
	{
		$field = "$op($f)";
		$stmt = $this->sqlExec("SELECT $field FROM $t $cond;",$v);
		$n = $stmt ? $stmt->fetch() : false;
		return (isset($n[$field])) ? $n[$field] : false;
	}

	public function atualizar($t,$a,$c,$cvs = [])
	{
		[$f,$fvs] = $this->parameterfy($a);
		$vs = array_merge($fvs,$cvs);
		$stmt = $this->sqlExec("UPDATE $t SET $f WHERE $c;",$vs);
		return $stmt ? $stmt->rowCount() : false;
	}
	
	private function parameterfy($array)
	{
		foreach ($array as $field => $value) {
			$sets[] = "$field=?";
			$values[] = trim($value,' \'');
		}
		return [implode(',',$sets),$values];
	}

	public function consultar($q, $tudo = true)
	{
		if ($stmt = $this->sqlExec($q)) {
			if ($affected = $stmt->rowCount()) {
				return $all ? $stmt->fetchAll() : $stmt->fetch();
			}
			return true;
		}
		return false;
	}
	
	public function excluir($t,$c,$v = [])
	{
		$stmt = $this->sqlExec("DELETE FROM $t WHERE $c;",$v);
		return $stmt ? $stmt->rowCount() : false;
	}
	
	private function sqlExec($sql,$v = false)
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
