<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * User model.
 */
class User_model extends MY_Model
{

    /**
     * Table name.
     */
    const TABLE_NAME = 'users';
    const ACTIVITY_TABLE_NAME = 'activity';

    // Roles
    const ROLE_DEFAULT = 0;
    const ROLE_ADMIN = 1;
    const ROLE_COLABORADOR = 2;

    // Status
    const STATUS_DISABLE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_WAITING = 2;
    const STATUS_NO_ACCOUNT = 3;

    // Other
    const NO_NAME = "----";

    // Table fields
    public $id;
    public $email;
    public $name = null;
    public $password = null;
    public $avatar_id;
    public $alias;
    public $bio;
    public $city;
    public $roles = self::ROLE_COLABORADOR;
    public $confirmation;
    public $created_at = 0;
    public $updated_at = 0;
    public $status = self::STATUS_DISABLE;


    private $role_desc = 
    [
        0 => "Usuário",
        1 => "Admin",
        2 => "Colaborador",
    ];

    public function __construct($data = array())
    {
        $this->TABLE_NAME = self::TABLE_NAME;
        parent::__construct($data);
    }

    /**
     * Return a string with user denomination
     * @return string
     */
    public function get_name()
    {
        $name = trim($this->name);
        empty($name) and $name = $this->alias;
        empty($name) and $name = self::NO_NAME;
        return $name;
    }

    public function get_name_or_email()
    {
        $name = $this->get_name();
        return $name == self::NO_NAME ? $this->email : $this->name;
    }

    /**
     * Returns user url
     */
    public function get_url()
    {
        return site_url("user/view/" . $this->id);
    }

    /**
     * Returns user avatar media model
     */
    public function get_avatar($style = null)
    {
        if (!is_null($this->avatar_id)) {
            $this->load->model('Media_model');
            $avatar = $this->Media_model->get_by_id($this->avatar_id);
            if ($path = $avatar->get_path($style)) {
                return $path;
            }

        }

        return $this->config->item('default_avatar');
    }

    /**
     * Returns all activities
     */
    public function get_activities()
    {
        if (isset($this->id)) {
            $this->load->model('Activity_model');
            $this->Activity_model->db->order_by('id DESC');
            return $this->Activity_model->get_by_owner($this->id);
        }
        return false;
    }

    /**
     * Checks if user is within a specific role.
     *
     * @param int $role
     * @return boolean
     */
    public function is($role)
    {
        return $role == $this->roles;
    }

    /**
     * Create a new user
     */
    public function create($name)
    {
        if (empty($this->email) ||
            empty($this->password)) {
            return false;
        }

        $this->set_alias(false);

        return $this->insert();
    }

    /**
     * Inserts the current user on database.
     *
     * @return boolean
     */
    public function insert()
    {
        if ($this->email_exists($this->email)) {
            return false;
        }

        $this->created_at = gmdate("Y-m-d H:i:s");
        $this->updated_at = gmdate("Y-m-d H:i:s");

        $res = parent::insert();

        return $res;
    }

    /**
     * Updates the current user to database.
     *
     * @return boolean
     */
    public function update()
    {
        if (empty($this->email)) {
            return false;
        }

        $this->updated_at = gmdate("Y-m-d H:i:s");

        $res = parent::update();

        return $res;
    }

    /**
     * Gets all users.
     *
     * @return array
     */
    public function get_all()
    {
        $query = $this->db->get(self::TABLE_NAME);
        return $this->get_self_results($query);
    }

    /**
     * Gets an user by its e-mail.
     *
     * @param string $email
     * @return User_model|null
     */
    public function get_by_email($email)
    {
        $query = $this->db->get_where(self::TABLE_NAME, array('email' => $email));
        return $this->get_first_self_result($query);
    }

    /**
     * Gets an user by its alias.
     *
     * @param string $alias
     * @return User_model|null
     */
    public function get_by_alias($alias)
    {
        $query = $this->db->get_where(self::TABLE_NAME, array('alias' => $alias));
        return $this->get_first_self_result($query);
    }

    /**
     * Encrypts the given password using database ENCODE function.
     *
     * @param string $password
     * @return string
     */
    public function encrypt_password($password)
    {
        $sql = "SELECT ENCODE(?, ?) AS `password`";
        $query = $this->db->query($sql, array($password, $this->config->item('encode_code_word')));
        return $query->num_rows() > 0 ? $query->row()->password : null;
    }

    /**
     * reset user password
     *
     * @return string
     */
    public function reset_password()
    {
        if (!isset($this->id)) {
            return false;
        }

        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $password = substr(str_shuffle($chars), 0, 8);

        log_message('info', "Password changed: $password");

        $this->password = $this->encrypt_password($password);
        $this->update();

        return $password;
    }

    /**
     * Check if an active account already exists with email
     */
    public function email_exists($email)
    {
        $users = new User_model();

        $users->db->where("LOWER(email) = LOWER('{$email}')");
        $query = $users->db->get(self::TABLE_NAME);
        $users = $query->result();
        return count($users);
    }

    /**
     * Set user confirmation code
     *
     * @return string
     */
    public function set_confirmation()
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $confirmation = substr(str_shuffle($chars), 0, 24);

        $this->confirmation = $confirmation;
        $this->status = self::STATUS_WAITING;

        return $confirmation;
    }

    /**
     * Check if confirmation is OK
     *
     * @return user
     */
    public function check_confirmation($confirmation)
    {
        if (empty($confirmation)) {
            return false;
        }

        $query = $this->db->get_where(self::TABLE_NAME, array('confirmation' => $confirmation));
        if ($query->num_rows() > 0) {
            $user = $this->get_first_self_result($query);
            $user->confirmation = null;
            $user->status = self::STATUS_ACTIVE;
            $user->update();
            return $user;
        } else {
            return false;
        }
    }

    /**
     * Add a new avatar image to user profile
     */
    public function add_avatar($path)
    {
        $this->load->model('Media_model');

        // removing old avatar
        if (!is_null($this->avatar)) {
            $media_old = $this->Media_model->get_by_id($this->avatar_id);
            if ($media_old) {
                $media_old->delete();
            }

        }

        //saving new avatar
        $media = new Media_model();
        if ($media->insert($path)) {
            $this->avatar_id = $media->id;
            return $this->update();
        } else {
            return false;
        }

    }

    /**
     * Search users from string
     * @param $key string to search
     * @return array of user values ('id' => user_id, 'text' => text responding to $key)
     */
    public function search_user($key)
    {

        $where = "(name LIKE '%$key%' OR email LIKE '%$key%')";

        $query = $this->db->where($where)->get(self::TABLE_NAME);

        $users = $this->get_self_results($query);

        $res = array();
        foreach ($users as $user) {
            if (stripos($user->name, $key) !== false) {
                $res[$user->id] = array('id' => $user->id, 'text' => $user->name);
            } else {
                if (stripos($user->email, $key) !== false) {
                    $res[$user->id] = array('id' => $user->id, 'text' => $user->email);
                }

            }
        }

        return array_values($res);
    }

    /**
     * Format object name into url alias
     * THIS FUNCTION SAVES THE NEW ALIAS IF ALIAS WAS EMPTY
     *
     * @return string
     */
    protected function set_alias($save = true)
    {
        $alias = $this->alias;
        if (empty($alias)) {
            $max = 30;
            $table = array(
                'Š' => 'S', 'š' => 's', 'Đ' => 'Dj', 'đ' => 'dj', 'Ž' => 'Z', 'ž' => 'z', 'Č' => 'C', 'č' => 'c', 'Ć' => 'C', 'ć' => 'c',
                'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
                'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O',
                'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss',
                'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e',
                'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o',
                'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'ý' => 'y', 'þ' => 'b',
                'ÿ' => 'y', 'Ŕ' => 'R', 'ŕ' => 'r',
            );
            $base = substr(preg_replace("/[^a-zA-Z0-9]+/", "_", strtr($this->get_name(), $table)), 0, $max);
            $i = 1;
            $query = $this->db->get_where(self::TABLE_NAME, array('alias' => $base));
            while ($query->num_rows() > 0) {
                $alias = $base . '_' . $i++;
                $query = $this->db->get_where(self::TABLE_NAME, array('alias' => $alias));
            }
            $this->alias = $alias;
            if ($save) {
                $this->update();
            }

        }
        return $alias;
    }


    public function is_active()
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    public function pode_autorizar(&$errors)
    {
        $errors = [];
        if(!$this->is(self::ROLE_DEFAULT))
        {
            $errors[] = 'Usuário com permissão inválida';
        }


        return empty($errors);
    }

    public function autorizar()
    {
        $errors = [];
        if(!$this->pode_autorizar($user, $errors))
        {
            throw new Exception(join(',', $errors));
        }

        $this->roles = self::ROLE_COLABORADOR;
    }

    public function pode_desautorizar(&$errors)
    {
        $errors = [];
        if(!$this->is(self::ROLE_COLABORADOR))
        {
            $errors[] = 'Usuário com permissão inválida';
        }


        return empty($errors);
    }

    public function desautorizar()
    {
        $errors = [];
        if(!$this->pode_desautorizar($user, $errors))
        {
            throw new Exception(join(',', $errors));
        }

        $this->roles = self::ROLE_DEFAULT;
    }

    public function get_role_desc()
    {
        return $this->role_desc[$this->roles];
    }

}

/* End of file user_model.php */
/* Location: ./application/models/user_model.php */
