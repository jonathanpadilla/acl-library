<?php

class ExampleController extends controller {

  	public function index()
  	{
  		/*
  			USO DE LA LIBRERIA 
  		*/
  	

  		// devuelve true o false
  		$this->acl->hasPermission('seccion_usuarios');

  		// vuelve a la pagina anterior si no tiene permisos para esta sección
    	$this->acl->hasPermission('seccion_usuarios', 'back');

  		// redirecciona a un sitio especifico si no tiene acceso a "seccion_usuarios"
  		$this->acl->hasPermission('seccion_usuarios', array('redirect' => array('InicioController', 'index')));

  		// carga una vista especificada si el usuario tiene acceso a "seccion_usuarios"
  		$this->acl->hasPermission('seccion_usuarios', array('view' => 'dir/view.php'));

  		// crea contenido html si tiene permisos
  		$this->acl->hasPermission('seccion_usuarios', array('html' => '<h1>Titulo</h1>')); 

  		// carga un archivo js si tiene permisos
  		$this->acl->hasPermission('seccion_usuarios', array('script' => 'path/to/script.js'));
  		// script con parámetros
  		$this->acl->hasPermission('seccion_usuarios', array('script' => array(
  				'src' => ['path/to/script.js'],
     			'params' => ['foo' => "foo", 'var' => "var"])));

  		// retorna un array de objetos con los sub permisos de otro
  		$this->acl->hasPermission('seccion_usuarios', 'childs');
  	}
}