<?php


class acl
{
    private $CI = '';
    private $is_valid = false;
    private $key = '';
    
    public function __construct()
    {
        $this->CI = get_instance();
    }
    
    /* PUBLIC FUNCTIONS */

    public function userPermissions($user = false)
    {
        if(!$session_data = $this->CI->session->userdata('logged_in'))
            return array();

        if(!$user){
            if(isset($session_data['permissions']))
                return $session_data['permissions'];

            $user = $session_data['id'];
        }

        $this->CI->load->model('permisos_model');
        $data = $this->CI->permisos_model->getUserPermissions($user);
        return $data;
    }
    
    public function userPermission($permission)
    {
        $permissions = $this->userPermissions();
        
        $result = [];
        foreach($permissions as $perm)
        {
            if($perm[key($permission)] == reset($permission) && $perm['value'] == 1)
            {
                $result[] = $perm;
            }
        }

        return $result;
    }


    public function permissions()
    {
        //getPermisions
        $this->CI->load->model('permisos_model');
        $permissions = $this->CI->permisos_model->getPermisions();
        
        return $this->recursiveChildKeys($permissions);
    }

    public function profiles(){
        $this->CI->load->model('permisos_model');
        $data = $this->CI->permisos_model->getProfiles();

        return $data;
    }
    
    public function getChilds($key, $permissions = false)
    {
        $permissions || $permissions = $this->userPermissions();

        if(!isset($permissions[$key]))
            return [];

        $parent = $permissions[$key];

        $childs = [];
        foreach($permissions as $permission)
        {
            if($permission['parent'] == $parent['id'] && $permission['value'] == 1)
            {
                $childs[] = $permission;
            }
        }

        return $childs;
    }

    public function saveUserPermissions($params)
    {
        // echo '<pre>';print_r($params);exit;
        if(!is_array($params['usuarios']) && !is_array($params['permisos']))
                return false;

        $this->CI->load->model('permisos_model');


        foreach($params['usuarios'] as $user)
        {
            $user = (array)$user;
            $data_permissions = $this->CI->permisos_model->getUserPermissions($user['usuario_id']);
            if(count($data_permissions) > 0)
            {
                $this->CI->permisos_model->deleteUserPermissions($user['usuario_id']);
            }

            foreach($params['permisos'] as $permission)
            {
                $permission = (array)$permission;                

                // crea el registro solo si se le da permisos
                if($permission['value'] == 1)
                {
                    $user_param = [
                        'id_usuario' => $user['usuario_id'],
                        'id_permiso' => $permission['id'],
                        'valor' => $permission['value']
                    ];
                    $this->CI->permisos_model->insertUserPermission($user_param);
                }
                
            }
        }

        return true;
    }

    public function saveUserProfile($data)
    {
        $this->CI->load->model('permisos_model');

        foreach($data['usuarios'] as $user)
        {
            $user = (array)$user;
            $this->CI->permisos_model->insertUserProfile($user['usuario_id'], $data['perfil']);
        }

        return true;
    }
    
    /*
     * USO
     * 
     * $this->acl->hasPermission('permiso', $opciones, $permisos_usuario );                
     * 
     * $this->acl->hasPermission('tablero_civil');                                                             devuelve true o false
     * $this->acl->hasPermission('tablero_civil', 'back');                                                     redirecciona a la pagina anterior
     * $this->acl->hasPermission('tablero_civil', array('redirect' => 'back'));                                redirecciona a pa pagina anterior opcional
     * $this->acl->hasPermission('tablero_civil', array('redirect' => array('home_controller', 'index')));     redirecciona a un controlador
     * $this->acl->hasPermission('bloque_informacion', array('view' => 'dir/view.php'));                       carga una vista
     * $this->acl->hasPermission('cobranza', array('html' => '<h1>Titulo</h1>'));                              imprime un texto
     * $this->acl->hasPermission('cobranza', array('script' => 'path/to/script.js'));                          imprime un script js metodo corto
     * $this->acl->hasPermission('cobranza', array('script' => array('src' => ['path/to/script.js'],           imprime un o mas scripts js
     *                                                                'params' => ['foo' => '"foo"'            enviar params a scripts js
     *                                                                             'var' => '"var"'] )));
     * $this->acl->hasPermission('tablero_civil', 'childs');                                                   retorna array con hijos de permiso 
     */
    public function hasPermission($key = false, $options = false, $permissions = array())
    {
        if(!$key)
            return false;
        
        $this->key = $key;
        
        $permissions || $permissions = $this->userPermissions();
        $key = strtolower($key);

        if( array_key_exists($key, $permissions) ){
            $this->is_valid = ( $permissions[$key]['value'] === 1 ) ? true : false;
        }else{
            $this->is_valid = false;
        }
        
        $this->options($options);
        
        return $this->is_valid;
    }
    
    /*
     * USO
     * 
     * $this->acl->nextIfOne('permiso', ['controller', 'method', ['param1', 20, 'param3']])  
     * 
     * $this->acl->nextIfOne('cobranza_cortes', ['cobranza_controller', 'totalesCorte', [11, 20]])        redirige a un controller si solo tiene un permiso hijo del permiso asignado "cobranza_competencias"
     * $this->acl->nextIfOne('cobranza_cortes', ['cobranza_controller', 'totalesCorte', 'param1'])        opcion con un solo parametro
     */
    public function nextIfOne($key = false, $options = false)
    {
        if(!$key)
            return false;

        $permissions = $this->userPermissions();
        $parent = $permissions[$key];
        
        $childs = $this->getChilds($key, $permissions);

        if(count($childs) == 1)
        {
            $this->is_valid = false;
            $this->redirect($options);
        }
    }

    public function setProfile($obj)
    {

        $permissions = [];
        foreach($obj['permissions'] as $permission)
        {

            if($permission->value == 1)
            {
                $permissions[$permission->key]['id']     = $permission->id;
                $permissions[$permission->key]['key']    = $permission->key;
                $permissions[$permission->key]['name']   = $permission->name;
                $permissions[$permission->key]['parent']  = isset($permission->parent) ? $permission->parent: null;
                $permissions[$permission->key]['value']  = $permission->value;
            }
        }

        if($obj['profile'] < 1)
        {
            // crear nuevo
            $this->CI->load->model('permisos_model');
            $this->CI->permisos_model->setProfile($obj['name'], $permissions);
        }else{
            // modificar
            $this->CI->load->model('permisos_model');
            $this->CI->permisos_model->updateProfile($obj['profile'], $permissions);
        }

        return $obj;
    }
    
    /* PRIVATE FUNCTIONS */
    
    private function options($options)
    {
        if(!$options)
            return false;
        
        if(is_array($options)) {
            // redireccionar
            if(isset($options['redirect']))
                $this->redirect($options['redirect']);
            
            // cargar vista
            if(isset($options['view']))
                $this->loadView($options['view']);

            // cargar html
            if(isset($options['html']))
                $this->html($options['html']);

            // cargar script js
            if(isset($options['scripts']))
                $this->scripts($options['scripts']);

            // cargar redirecciona si solo tiene un permiso asociado a otro superior
            //if(isset($options['nextIfOne']))
                //$this->nextIfOne($options['nextIfOne']);
            
        }else{
            switch($options)
            {
                case 'back': $this->back(); break;
                case 'childs': $this->is_valid = $this->getChilds($this->key); break;
                // agregar mas opciones
            }
        }
    }
    
    private function recursiveChildKeys($array, $parent = NULL)
    {
        $obj = [];
        foreach($array as $value) // devuelve un array con los permisos hijos
        {
            if($value->id_permiso == $parent)
            {
                $obj[$value->id]['id'] = $value->id;
                $obj[$value->id]['key'] = $value->llave;
                $obj[$value->id]['name'] = $value->nombre;
                $obj[$value->id]['child'] = $this->recursiveChildKeys($array, $value->id);
            }
        }
        
        return $obj;
    }
    
    private function back()
    {
        if(!$this->is_valid)
        {
            echo 'Redireccionando...<script>window.history.back();</script>';
            exit;
        }
    }
    
    private function redirect($redirect)
    {
        if($this->is_valid)
            return false;

        if(is_array($redirect))
        {
            switch(count($redirect))
            {
                case 1: // opcion 1: si solo se especifica el controlador, redirecciona al index
                    redirect($redirect[0], 'index');
                    break;
                case 2: // opcion 2: si se especifica el controlador y metodo
                    redirect($redirect[0], $redirect[1]);
                    break;
                case 3: // opcion 3: se especifica controlador, metodo y parametros
                    $params = '';
                    if(is_array($redirect[2])) // parametros enviados como array ej. ['id' => 1, 'param2' => 'param']
                    {
                        foreach($redirect[2] as $param)
                        {
                            $params.= '/'.$param;
                        }
                    }else{ // parametros enviados como string ej id=1&param2=param
                        $params = '/'.$redirect[2];
                    }
                    redirect($redirect[0].'/'.$redirect[1].$params);
                    break;
            }
        }else{
            switch ($redirect)
            {
                case 'back': $this->back(); break;
                // agregar default redireccionar enlace
            }
        }
    }
    
    private function loadView($view)
    {
        if($this->is_valid)
            $this->CI->load->view($view);
    }
    
    private function html($html)
    {
        if($this->is_valid)
            echo $html;
    }
    
    private function scripts($scripts)
    {
        if(!$this->is_valid)
            return false;
        
        if(!is_array($scripts))
        {
            echo '<script src="'.base_url().$scripts.'"></script>';
        }else{
            
            if($scripts['params']) // si trae parametros, creará un script con los parametros antes de crear el script que se está llamando
            {
                $params_script = '<script type="text/javascript">';
                foreach($scripts['params'] as $key => $param)
                {
                    $params_script .= 'var '.$key.' = '.$param.';';
                }
                $params_script .= '</script>';
            }

            echo $params_script;
            
            foreach($scripts['src'] as $script)
            {
                echo '<script src="'.base_url().$script.'"></script>';
            }
        }
    }    
}