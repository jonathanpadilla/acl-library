<?php

$CI = '';

function get_db_cv(){
        // Cargamos La referencia al controlador General
        $CI = get_instance();
        // Cargamos el Modelo 
        // $CI->load->model('civil_model');

    }

function aclTribunalPermissions($corte, $mesCorte, $tribunales = [])
{
	get_db_cv();
	global $CI;
	$objMesCorte = [];

	foreach($mesCorte as $tribunal)
	{
		if($corte == 0)
		{
			if(in_array($tribunal->cod_tribunal,$tribunales))
			{
				$objMesCorte[] = $tribunal;
			}
		}else{
			$rs = array_filter($CI->acl->hasPermission('cobranza_cortes_'.$corte, 'childs'), function($v) use ($tribunal){
			$arr = explode('_', $v['key']);
			if(end($arr) == $tribunal->cod_tribunal && $v['value'] == 1)
					return $v;
			});

			if(count($rs)>0)
				$objMesCorte[] = $tribunal;
		}
	}

	return $objMesCorte;
}

function aclGetParamFirstChild($parent)
{
	get_db_cv();
	global $CI;

	$childs = $CI->acl->getChilds($parent);

	if(count($childs) <= 0)
		return false;

	$child = explode('_', $childs[0]['key']);
	return end($child);
}

function aclGetParamsChild($parent)
{
	get_db_cv();
	global $CI;

	$childs = $CI->acl->getChilds($parent);

	$params = [];
	foreach($childs as $child)
	{
		$key = explode('_', $child['key']);
		$params[] = end($key);
	}

	return $params;
}

function aclGetTribunales($format = 'object', $permisos = [])
{
	get_db_cv();
	global $CI;

	$array_tribunales = [];

	$cortes = $CI->acl->getChilds('cobranza_cortes');

	foreach($cortes as $c)
	{
		$tribunales = $CI->acl->getChilds($c['key'], $permisos);
		$array_tribunales = array_merge($array_tribunales, $tribunales);
	}

	switch ($format) {
		case 'object':
			return $array_tribunales;
			break;

		case 'cod':
			return array_map(function($obj){
				$arr = explode('_', $obj['key']);
				return (int)end($arr);
			}, $array_tribunales);
			break;
	}
}

function aclPermissionViewCorte($cobranza_cortes, $cobranza_controller, $totalesCorte)
{
	get_db_cv();
	global $CI;

	// redirecciona a totales corte si el usuario tiene acceso a la vista global
	if($CI->acl->hasPermission('cobranza_cortes_0')){
		// echo 'redireccionar';
		redirect('cobranza_controller/totalesCorte/0');
		exit;
	}

	// redirecciona a la pagina especificada si solo tiene acceso a una sola corte
	$CI->acl->nextIfOne('cobranza_cortes', ['cobranza_controller', 'totalesCorte', aclGetParamFirstChild('cobranza_cortes')]);
}