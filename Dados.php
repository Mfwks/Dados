<?php

namespace Mfwks\Legacy;

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
	public static function tabela($t)
	{
		$ok = self::consultar("SHOW TABLES LIKE '$t';");
		if (!is_array($ok)) {
			return false;
		}
		$stmt = self::sqlExec("DESCRIBE $t");
		$data = $stmt ? $stmt->fetchAll() : false;
		return $data ? array_column($data,'Field') : false;
	}

	public static function cabecalhos($t)
	{
		if ($f = self::tabela($t)) {
			return implode(',',$f);
		}
		return false;
	}

	public static function rotulos($t,$key,$value,$cond = null,$v = false)
	{
		if (!$cols = self::todos($t,"$key,$value",$cond,$v)) {
			return null;
		}
		foreach ($cols as $row) {
			$n[$row[$key]] = $row[$value];
		}
		return $n ?? null;
	}
	
	public static function inserir($t,$vs)
	{
		global $conex;
		[$f,$v] = self::fieldsValues($vs);
		$h      = implode(',',array_fill(0,count($v),'?'));
		$stmt   = self::sqlExec("INSERT INTO $t ($f) VALUES ($h);",$v);
		return $stmt ? $conex->lastInsertId() : false;
	}

	private static function fieldsValues($in)
	{
		return [
			0 => implode(',',array_keys($in)),
			1 => array_values($in)
		];
	}
	
	public static function campo($t,$f,$cond = null,$v = false)
	{
		$stmt = self::sqlExec("SELECT $f FROM $t $cond;",$v);
		return $stmt ? $stmt->fetchColumn() : false;
	}

	public static function linha($t,$f = '*',$cond = null,$v = false)
	{
		$stmt = self::sqlExec("SELECT $f FROM $t $cond;",$v);
		return $stmt ? $stmt->fetch() : false;
	}

	public static function coluna($t,$f,$cond = null,$v = false)
	{
		$stmt = self::sqlExec("SELECT $f FROM $t $cond;",$v);
		$data = $stmt ? $stmt->fetchAll() : false;
		return $data ? array_column($data,$f) : false;
	}

	public static function todos($t,$f = '*',$cond = null,$v = false)
	{
		$stmt = self::sqlExec("SELECT $f FROM $t $cond;",$v);
		return $stmt ? $stmt->fetchAll() : false;
	}

	public static function quantos($t,$f = '*',$cond = null,$v = false)
	{
		return self::selectThing($t,$f,'COUNT',$cond,$v);
	}

	public static function somar($t,$f = '*',$cond = null,$v = false)
	{
		return self::selectThing($t,$f,'SUM',$cond,$v);
	}

	private static function selectThing($t,$f,$op,$cond = null,$v = false)
	{
		$field = "$op($f)";
		$stmt = self::sqlExec("SELECT $field FROM $t $cond;",$v);
		$n = $stmt ? $stmt->fetch() : false;
		return (isset($n[$field])) ? $n[$field] : false;
	}

	public static function atualizar($t,$a,$c,$cvs = [])
	{
		[$f,$fvs] = self::parameterfy($a);
		$vs = array_merge($fvs,$cvs);
		$stmt = self::sqlExec("UPDATE $t SET $f WHERE $c;",$vs);
		return $stmt ? $stmt->rowCount() : false;
	}
	
	private static function parameterfy($array)
	{
		foreach ($array as $field => $value) {
			$sets[] = "$field=?";
			$values[] = trim($value,' \'');
		}
		return [implode(',',$sets),$values];
	}

	public static function consultar($q, $tudo = true)
	{
		if ($stmt = self::sqlExec($q)) {
			if ($affected = $stmt->rowCount()) {
				return $all ? $stmt->fetchAll() : $stmt->fetch();
			}
			return true;
		}
		return false;
	}
	
	public static function excluir($t,$c,$v = [])
	{
		$stmt = self::sqlExec("DELETE FROM $t WHERE $c;",$v);
		return $stmt ? $stmt->rowCount() : false;
	}
	
	private static function sqlExec($sql,$v = false)
	{
		global $conex;
		$stmt = $conex->prepare($sql);
		try {
			$made = $v ? $stmt->execute($v) : $stmt->execute();
			return $made ? $stmt : false;
		} catch (PDOException $e) {
			exit('Falha na requisição ao banco de dados: ' . $e->getMessage());
		}
	}
}
