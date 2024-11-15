<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../../config/database.php';
require_once '../../models/Pessoa.php';
require_once '../../models/Cartao.php';

$database = new Database();
$db = $database->getConnection();

$pessoa = new Pessoa($db);

// Obter o método HTTP da requisição
$method = $_SERVER['REQUEST_METHOD'];

// Pegar o ID da URL
$id = isset($_GET['id']) ? $_GET['id'] : null;

switch ($method) {
    case 'GET':
        // Listar todas as pessoas
        $stmt = $pessoa->listar();
        $num = $stmt->rowCount();

        if ($num > 0) {
            $pessoas_arr = array();
            $pessoas_arr["records"] = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);

                $pessoa_item = array(
                    "ID_Pessoa" => $ID_Pessoa,
                    "Nome_Pessoa" => $Nome_Pessoa,
                    "Telefone" => $Telefone,
                    "Email" => $Email,
                    "ID_Cartao" => $ID_Cartao
                );

                array_push($pessoas_arr["records"], $pessoa_item);
            }

            echo json_encode($pessoas_arr);
        } else {
            echo json_encode(array("message" => "Nenhuma pessoa encontrada."));
        }
        break;

    case 'POST':
        // Criar uma nova pessoa
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->Nome_Pessoa) && !empty($data->Telefone)) {
            $pessoa->Nome_Pessoa = $data->Nome_Pessoa;
            $pessoa->Telefone = $data->Telefone;
            $pessoa->Email = isset($data->Email) ? $data->Email : null;
            $id_cartao = isset($data->ID_Cartao) ? $data->ID_Cartao : null; // Obter o ID do Cartão

            if ($pessoa->criar()) {
                // Após criar a pessoa, associar o cartão
                if ($id_cartao) {
                    $cartao = new Cartao($db);
                    if ($cartao->associarPessoa($id_cartao, $pessoa->ID_Pessoa)) {
                        echo json_encode(array("message" => "Pessoa criada com sucesso e cartão associado."));
                    } else {
                        echo json_encode(array("message" => "Pessoa criada, mas falha ao associar o cartão."));
                    }
                } else {
                    echo json_encode(array("message" => "Pessoa criada com sucesso, mas sem cartão associado."));
                }
            } else {
                echo json_encode(array("message" => "Erro ao criar pessoa."));
            }
        } else {
            echo json_encode(array("message" => "Dados incompletos."));
        }
        break;

    case 'PUT':
        // Atualizar uma pessoa existente
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->ID_Pessoa) && !empty($data->Nome_Pessoa) && !empty($data->Telefone)) {
            // Atribuindo os dados do JSON
            $pessoa->ID_Pessoa = $data->ID_Pessoa;
            $pessoa->Nome_Pessoa = $data->Nome_Pessoa;
            $pessoa->Telefone = $data->Telefone;
            $pessoa->Email = $data->Email;

            // Verifica se o ID_Cartao foi enviado
            if (isset($data->ID_Cartao)) {
                $pessoa->ID_Cartao = $data->ID_Cartao;
            }

            // Chamando o método para atualizar o banco de dados
            if ($pessoa->atualizar(isset($data->ID_Cartao))) {
                echo json_encode(array("message" => "Pessoa atualizada com sucesso."));
            } else {
                echo json_encode(array("message" => "Falha ao atualizar a pessoa."));
            }
        } else {
            echo json_encode(array("message" => "Dados incompletos."));
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"));

        // Verifica se o ID_Pessoa foi enviado no JSON
        if (!empty($data->ID_Pessoa)) {
            $pessoa->ID_Pessoa = $data->ID_Pessoa;

            if ($pessoa->deletar()) {
                echo json_encode(array("message" => "Pessoa deletada com sucesso."));
            } else {
                echo json_encode(array("message" => "Falha ao deletar a pessoa."));
            }
        } else {
            echo json_encode(array("message" => "ID da pessoa não informado no JSON."));
        }
        break;

    default:
        echo json_encode(array("message" => "Método não suportado."));
        break;
}