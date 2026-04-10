<?php
    require __DIR__ . '/../vendor/autoload.php';

    //configurar o cabeçalho para responder JSON
    header('Content-Type: application/json');

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');

    try {
        $dotenv->load();
    } catch (Exception $e) {
        die(json_encode(['error' => 'Arquivo .env não encontrado.']));
    }

    //configurar API
    $apikey = $_ENV['HF_API_KEY'];
    $modelUrl =  "https://api-inference.huggingface.co/models/mistralai/Mistral-7B-Instruct-v0.3";
    $json = file_get_contents("php://input");
    $userMessage = $data['message'] ?? '';


    if(empty($userMessage)){
        echo json_encode(['error'=> 'Mensagem vazia ou inválida.']);
        exit;
    }

    //garantir que existe o historico
    $memoryPath = __DIR__ . '/../memory/chat_history.json';
    if (!is_dir(__DIR__ . '/../memory')) {
        mkdir(__DIR__ . '/../memory', 0777, true);
    }

    $history = [];
    if (file_exists($memoryPath)){
        $history = json_decode(file_get_contents($memoryPath), true) ?? [];
    }
    // adicionar nova mensagem do usuario ao historico
    $history[] = ['role' => 'user', 'content' => $userMessage];
    //chamada para IA
    try{
        //definir o comportamento
        $systemText = 'Você é um mentor de Desenvolvimento Web especialista.'.
                        'Ajude o aluno com PHP, HTML, CSS e JS'.
                        'Sempre mostre exemplos de códigos formatados com Markdown e
                        explique a lógica de forma simples e didática';
        
        $fullPrompt = "<s>[INST] $systemText \n Histórico: " . json_encode($history) . "[/INST]";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $modelUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "Authorization: Bearer $apikey",
            "Content-Type: application/json"
        ]));

        $payload = [
            'inputs' => $fullPrompt,
            'parameters' => [
                'max_new_tokens' => 500,
                'return_full_text' => false
            ]
        ];
            


        //Mesclar o prompt do sistema com o historico guardado
        $messages = array_merge([$systemPrompt], $history);

        $response = $client->chat()->create([
            'model'=> 'deepseek-reasoner',
            'messages' => $messages, // 5. Corrigido: Faltava vírgula (,)
            'temperature' => 0.7
        ]); // 6. Corrigido: Faltava ponto e vírgula (;)

        $botReply = $response ->choices[0]->message->content;

        //atualiza e salva o historico
        $history[] = ['role' => 'assistant', 'content' =>$botReply];

        //ultimas 10 msgs para n estourar o limite de tokes da API
        if (count($history) >10) {
            $history = array_slice($history, -10);
        }

        file_put_contents($memoryPath, json_encode($history, JSON_PRETTY_PRINT));

        echo json_encode(['reply' => $botReply]);

    } catch(Exception $e) {
        echo json_encode(['error' => 'Erro na comunicação com a API: ' . $e->getMessage()]);
    }

?>