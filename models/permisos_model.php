<?php


class Permisos_model extends CI_Model
{
    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
    
    public function getPermisions()
    {
        $otherdb = $this->load->database('pjud', TRUE);
        $query = $otherdb->query('select id, id_permiso, llave, nombre from permisos order by id asc;');
        return $query->result();
    }

    public function getProfiles()
    {
        $otherdb = $this->load->database('pjud', TRUE);

        $query = $otherdb->query("select id, nombre, creado, modificado, permissions from perfiles_v2");
        return $query->result();
    }

    public function setProfile($name, $permissions)
    {
        $otherdb = $this->load->database('pjud', TRUE);
        $otherdb->query("insert into perfiles_v2 (nombre,creado, modificado,permissions) values ('".$name."', '".date("Y-m-d H:i:s")."', '".date("Y-m-d H:i:s")."', '".json_encode($permissions)."')");
    }

    public function updateProfile($id, $permissions)
    {
        $otherdb = $this->load->database('pjud', TRUE);
        $otherdb->query("update perfiles_v2 set modificado = '".date("Y-m-d H:i:s")."', permissions = '".json_encode($permissions)."' where id = ".$id);
    }
    
    public function getUserPermissions($user = 0)
    {
        $otherdb = $this->load->database('pjud', TRUE);
   
        $query = $otherdb->query('select p.id, p.llave, p.nombre, p.id_permiso, pu.valor, pu.id as id_permiso_usuario
                                    from permisos_usuarios pu
                                    join permisos p on pu.id_permiso = p.id
                                    where pu.id_usuario = '.$user);
        
        if($query->num_rows() > 0)
        {
            $permissions = array();
            foreach($query->result() as $value)
                $permissions[$value->llave] = array('id' => $value->id, 'id_perm_usu' => $value->id_permiso_usuario, 'key' => $value->llave, 'value' => $value->valor, 'name' => $value->nombre, 'parent' => $value->id_permiso );
            
            return $permissions;
            
        }
            
        return array();
    }

    public function getUserPermission($user = 0, $permission = 0)
    {
        $otherdb = $this->load->database('pjud', TRUE);
        $query = $otherdb->query('select pu.id, p.llave, p.nombre, p.id_permiso, pu.valor
                                    from permisos_usuarios pu
                                    join permisos p on pu.id_permiso = p.id
                                    where pu.id_usuario = '.$user.'
                                    and pu.id_permiso = '.$permission);

        if($query->num_rows() > 0)
            return $query->row();
        return [];

    }

    public function insertUserPermission($params)
    {
        $otherdb = $this->load->database('pjud', TRUE);
        $otherdb->query("insert into permisos_usuarios (id_usuario, id_permiso, valor, creado, modificado) values (".$params['id_usuario'].", ".$params['id_permiso'].", ".$params['valor'].", '".date("Y-m-d H:i:s")."', '".date("Y-m-d H:i:s")."')");
    }
    
    // public function updateUserPermission($params, $id)
    public function deleteUserPermission($permission, $usuario)
    {
        $otherdb = $this->load->database('pjud', TRUE);
        $otherdb->query('DELETE FROM permisos_usuarios WHERE id_usuario = '.$usuario.' and id_permiso = '.$permission['id']);
        // $otherdb->query('update permisos_usuarios set '.$set.'modificado = \''.date("Y-m-d H:i:s").'\' where id = '.$id);
    }

    public function deleteUserPermissions($usuario)
    {
        $otherdb = $this->load->database('pjud', TRUE);
        $otherdb->query('DELETE FROM permisos_usuarios WHERE id_usuario = '.$usuario);
        // $otherdb->query('update permisos_usuarios set '.$set.'modificado = \''.date("Y-m-d H:i:s").'\' where id = '.$id);
    }

    public function insertUserProfile($user, $profile)
    {
        if($profile < 1)
        {
            $profile = 'null';
        }

        $otherdb = $this->load->database('pjud', TRUE);
        $otherdb->query("update usuarios set id_perfil = ".$profile." where usuario_id = ".$user);
    }
}