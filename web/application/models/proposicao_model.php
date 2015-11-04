<?php 

class Proposicao_model extends MY_Model {

    const TABLE_NAME = 'proposicoes';

    var $nome;
    var $descricao;
    var $id;
    var $camara_id;

    function __construct($data = array())
    {
        $this->TABLE_NAME = self::TABLE_NAME;
        parent::__construct($data);
    }
    
    public function get_all()
    {
        $query = $this->db->get(self::TABLE_NAME);
        return $this->get_self_results($query);
    }

    public function get_by_camara_id($idProposicao)
    {
        $query = $this->db->get_where(self::TABLE_NAME, array('camara_id' => $idProposicao));
        return $this->get_first_self_result($query);
    }

    public function list_from_xml_camara($content)
    {
        $models = [];
        if($content == null) 
        {
            return $models;
        }

        $xml = new SimpleXMLElement($content);
        foreach ($xml->xpath('//proposicao') as $item) {
            $model = new Proposicao_model();
            $model->nome = (string) $item->nomeProposicao;
            $model->descricao = (string) $item->Ementa;
            $model->camara_id = (int) $item->idProposicao;

            array_push($models, $model);
        }
        
        return $models;
    }

    public function get_from_xml_camara($content)
    {
        $models = $this->list_from_xml_camara($content);
        if(empty($models))
        {
            return null;
        }

        return array_pop($models);
    }
}